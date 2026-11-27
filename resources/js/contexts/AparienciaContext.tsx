import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { api } from '@/lib/api';
import type { Modo, Preferencias, Tema } from '@/types';

/*
 * Temas dinámicos por segmento de usuario (Niños, Jóvenes, Adultos) combinados con
 * modo Día/Noche (automático según el horario o manual) y herramientas de accesibilidad
 * (tamaño de fuente y alto contraste). Se guardan en el navegador y, si hay sesión, en el perfil.
 */

export const TEMAS: { valor: Tema; nombre: string; descripcion: string }[] = [
    { valor: 'ninos', nombre: 'Niños', descripcion: 'Colores alegres y formas redondeadas' },
    { valor: 'jovenes', nombre: 'Jóvenes', descripcion: 'Estilo moderno y vibrante' },
    { valor: 'adultos', nombre: 'Adultos', descripcion: 'Estilo institucional y sobrio' },
];

export const MODOS: { valor: Modo; nombre: string }[] = [
    { valor: 'auto', nombre: 'Automático (según horario)' },
    { valor: 'dia', nombre: 'Día' },
    { valor: 'noche', nombre: 'Noche' },
];

/** Horario del modo automático: de noche entre las 19:00 y las 06:59. */
export const HORARIO_NOCHE = { desde: 19, hasta: 7 };

export const FUENTE_MIN = 85;
export const FUENTE_MAX = 150;
export const FUENTE_PASO = 5;

const PREDETERMINADAS: Preferencias = { tema: 'adultos', modo: 'auto', fuente: 100, contraste: false };
const CLAVE = 'apariencia';

function esDeNoche(fecha = new Date()): boolean {
    const h = fecha.getHours();

    return h >= HORARIO_NOCHE.desde || h < HORARIO_NOCHE.hasta;
}

function normalizar(p: Partial<Preferencias> | null | undefined): Preferencias {
    const tema = TEMAS.some((t) => t.valor === p?.tema) ? (p!.tema as Tema) : PREDETERMINADAS.tema;
    const modo = MODOS.some((m) => m.valor === p?.modo) ? (p!.modo as Modo) : PREDETERMINADAS.modo;
    const fuente = Math.min(FUENTE_MAX, Math.max(FUENTE_MIN, Math.round(Number(p?.fuente ?? 100)) || 100));

    return { tema, modo, fuente, contraste: Boolean(p?.contraste) };
}

function leerGuardadas(): Preferencias {
    try {
        return normalizar(JSON.parse(localStorage.getItem(CLAVE) ?? 'null'));
    } catch {
        return PREDETERMINADAS;
    }
}

interface ValorApariencia extends Preferencias {
    /** Modo realmente aplicado ahora (resuelve "auto" según la hora). */
    modoEfectivo: 'dia' | 'noche';
    setTema: (tema: Tema) => void;
    setModo: (modo: Modo) => void;
    setFuente: (porcentaje: number) => void;
    aumentarFuente: () => void;
    reducirFuente: () => void;
    setContraste: (activo: boolean) => void;
    alternarContraste: () => void;
    restablecer: () => void;
}

const AparienciaContext = createContext<ValorApariencia | null>(null);

export function AparienciaProvider({ children }: { children: ReactNode }) {
    const { usuario } = useAuth();
    const [preferencias, setPreferencias] = useState<Preferencias>(leerGuardadas);
    const [ahora, setAhora] = useState(() => new Date());
    const usuarioSincronizado = useRef<number | null>(null);
    const cambioDelUsuario = useRef(false);

    // Reloj para el modo automático (revisa cada minuto si ya es de día o de noche).
    useEffect(() => {
        const intervalo = window.setInterval(() => setAhora(new Date()), 60_000);

        return () => window.clearInterval(intervalo);
    }, []);

    const modoEfectivo: 'dia' | 'noche' =
        preferencias.modo === 'auto' ? (esDeNoche(ahora) ? 'noche' : 'dia') : preferencias.modo;

    // Aplica la apariencia al documento.
    useEffect(() => {
        const raiz = document.documentElement;
        raiz.dataset.tema = preferencias.tema;
        raiz.classList.toggle('dark', modoEfectivo === 'noche');
        raiz.style.colorScheme = modoEfectivo === 'noche' ? 'dark' : 'light';
        raiz.style.fontSize = `${preferencias.fuente}%`;

        if (preferencias.contraste) {
            raiz.dataset.contraste = 'alto';
        } else {
            delete raiz.dataset.contraste;
        }

        try {
            localStorage.setItem(CLAVE, JSON.stringify(preferencias));
        } catch {
            // Sin almacenamiento: la preferencia dura mientras la pestaña esté abierta.
        }
    }, [preferencias, modoEfectivo]);

    // Al iniciar sesión se adoptan las preferencias guardadas en el perfil del usuario.
    useEffect(() => {
        if (!usuario) {
            usuarioSincronizado.current = null;

            return;
        }

        if (usuarioSincronizado.current !== usuario.id) {
            usuarioSincronizado.current = usuario.id;

            if (usuario.preferencias) {
                cambioDelUsuario.current = false;
                setPreferencias(normalizar(usuario.preferencias));
            }
        }
    }, [usuario]);

    // Los cambios hechos por el usuario autenticado se guardan en su perfil (PUT /api/perfil/preferencias).
    useEffect(() => {
        if (!usuario || !cambioDelUsuario.current) {
            return;
        }

        const temporizador = window.setTimeout(() => {
            api.put('perfil/preferencias', preferencias).catch(() => {
                // No es crítico: la preferencia queda guardada en el navegador.
            });
        }, 800);

        return () => window.clearTimeout(temporizador);
    }, [preferencias, usuario]);

    const actualizar = useCallback((cambios: Partial<Preferencias>) => {
        cambioDelUsuario.current = true;
        setPreferencias((actual) => normalizar({ ...actual, ...cambios }));
    }, []);

    const valor = useMemo<ValorApariencia>(
        () => ({
            ...preferencias,
            modoEfectivo,
            setTema: (tema) => actualizar({ tema }),
            setModo: (modo) => actualizar({ modo }),
            setFuente: (fuente) => actualizar({ fuente }),
            aumentarFuente: () => actualizar({ fuente: preferencias.fuente + FUENTE_PASO }),
            reducirFuente: () => actualizar({ fuente: preferencias.fuente - FUENTE_PASO }),
            setContraste: (contraste) => actualizar({ contraste }),
            alternarContraste: () => actualizar({ contraste: !preferencias.contraste }),
            restablecer: () => actualizar(PREDETERMINADAS),
        }),
        [preferencias, modoEfectivo, actualizar],
    );

    return <AparienciaContext.Provider value={valor}>{children}</AparienciaContext.Provider>;
}

export function useApariencia(): ValorApariencia {
    const contexto = useContext(AparienciaContext);

    if (!contexto) {
        throw new Error('useApariencia debe usarse dentro de <AparienciaProvider>.');
    }

    return contexto;
}

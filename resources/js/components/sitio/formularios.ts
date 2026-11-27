import { useCallback, useState } from 'react';
import { api, erroresValidacion } from '@/lib/api';
import type { Etiquetas, ReglasFormulario } from '@/lib/validacion';
import type { DatosAntispam } from '@/types';
import type { Catalogo, FichaGraduado } from '@/components/sitio/tipos';

/*
 * Reglas de validación del cliente de los formularios públicos. Son las mismas que aplican los
 * controladores de la API (app/Http/Controllers/Sitio/*): el cliente avisa antes de enviar y el
 * servidor vuelve a validar (errores 422 que useFormulario muestra junto a cada campo).
 */

/** Año máximo de graduación aceptado por el servidor (año actual + 1). */
export const ANIO_MAXIMO_GRADUACION = new Date().getFullYear() + 1;

/** Teléfono del formulario de contacto: solo números, espacios y + ( ) - (igual que el servidor). */
export const REGLA_TELEFONO = 'regex:^[0-9 +()-]+$';

const claves = (catalogo: Catalogo) => Object.keys(catalogo).join(',');

/** Reglas de la ficha del graduado (GraduadoController::reglas). */
export function reglasGraduado(situaciones: Catalogo, modalidades: Catalogo): ReglasFormulario {
    return {
        nombre: ['requerido', 'max:150'],
        ci: ['requerido', 'max:20'],
        registro: ['max:20'],
        anio_graduacion: ['requerido', 'entero', `entre:1970,${ANIO_MAXIMO_GRADUACION}`],
        modalidad_titulacion: Object.keys(modalidades).length ? [`en:${claves(modalidades)}`] : [],
        situacion_laboral: Object.keys(situaciones).length ? ['requerido', `en:${claves(situaciones)}`] : ['requerido'],
        empresa: ['max:150'],
        cargo: ['max:120'],
        ciudad: ['max:80'],
        telefono: ['max:30'],
        linkedin: ['url', 'max:255'],
    };
}

/** Nombres de los campos en los mensajes de error. */
export const ETIQUETAS_GRADUADO: Etiquetas = {
    nombre: 'nombre completo',
    ci: 'carnet de identidad',
    registro: 'registro universitario',
    anio_graduacion: 'año de graduación',
    modalidad_titulacion: 'modalidad de titulación',
    situacion_laboral: 'situación laboral',
    empresa: 'empresa',
    cargo: 'cargo',
    ciudad: 'ciudad',
    telefono: 'teléfono',
    linkedin: 'perfil de LinkedIn',
    email: 'correo electrónico',
    password: 'contraseña',
    password_confirmation: 'confirmación de la contraseña',
    correo: 'correo de contacto',
};

/** Valores (texto) de los campos compartidos del graduado. */
export interface ValoresGraduado {
    nombre: string;
    ci: string;
    registro: string;
    anio_graduacion: string;
    modalidad_titulacion: string;
    situacion_laboral: string;
    empresa: string;
    cargo: string;
    ciudad: string;
    telefono: string;
    linkedin: string;
}

export type CampoGraduado = keyof ValoresGraduado;

/** Convierte la ficha de la API en valores de formulario (los null pasan a texto vacío). */
export function valoresGraduado(g?: Partial<FichaGraduado> | null): ValoresGraduado {
    const texto = (v: unknown) => (v === null || v === undefined ? '' : String(v));

    return {
        nombre: texto(g?.nombre),
        ci: texto(g?.ci),
        registro: texto(g?.registro),
        anio_graduacion: texto(g?.anio_graduacion),
        modalidad_titulacion: texto(g?.modalidad_titulacion),
        situacion_laboral: texto(g?.situacion_laboral),
        empresa: texto(g?.empresa),
        cargo: texto(g?.cargo),
        ciudad: texto(g?.ciudad),
        telefono: texto(g?.telefono),
        linkedin: texto(g?.linkedin),
    };
}

/**
 * Token antispam de un formulario público. Tras un envío exitoso la API entrega uno nuevo
 * (el anterior queda consumido); si el servidor rechaza el token (vencido o inválido) se pide
 * otro volviendo a consultar la página.
 */
export function useAntispam(inicial: DatosAntispam | null | undefined, rutaPagina: string) {
    const [antispam, setAntispam] = useState<DatosAntispam | null>(inicial ?? null);

    const renovar = useCallback(async () => {
        try {
            const { data } = await api.get<{ antispam?: DatosAntispam }>(rutaPagina);

            if (data.antispam) {
                setAntispam(data.antispam);
            }
        } catch {
            // Sin conexión: el visitante puede recargar la página.
        }
    }, [rutaPagina]);

    return { antispam, setAntispam, renovar };
}

/**
 * Revisa un error de envío. Tras un 422 el token sigue siendo válido, salvo que el servidor lo rechace
 * por vencido o inválido: entonces se pide uno nuevo (con su pregunta). Si solo se envió demasiado
 * rápido basta con reintentar. Si la respuesta de la pregunta fue incorrecta se limpia el campo.
 */
export function revisarErrorAntispam(error: unknown, acciones: { renovar: () => void; limpiarRespuesta?: () => void }): void {
    const errores = erroresValidacion(error);

    if (errores.antispam && !/r[aá]pido/i.test(errores.antispam)) {
        acciones.renovar();
        acciones.limpiarRespuesta?.();
    } else if (errores.antispam_respuesta) {
        acciones.limpiarRespuesta?.();
    }
}

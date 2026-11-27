import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useSearchParams } from 'react-router';
import { useDebounce } from '@/hooks/useDebounce';

export type CambiarParametros = (cambios: Record<string, string | null | undefined>, conservarPagina?: boolean) => void;

/**
 * Filtros sincronizados con la URL (?q=&estado=&page=): se pueden compartir, recargar
 * y navegar con atrás/adelante. Al cambiar un filtro se vuelve a la primera página.
 */
export function useParametrosUrl() {
    const [params, setParams] = useSearchParams();
    const valores = useMemo<Record<string, string>>(() => Object.fromEntries(params.entries()), [params]);

    const cambiar = useCallback<CambiarParametros>(
        (cambios, conservarPagina = false) => {
            setParams(
                (previos) => {
                    const nuevos = new URLSearchParams(previos);

                    for (const [clave, valor] of Object.entries(cambios)) {
                        if (valor === null || valor === undefined || valor === '') {
                            nuevos.delete(clave);
                        } else {
                            nuevos.set(clave, valor);
                        }
                    }

                    if (!conservarPagina && !('page' in cambios)) {
                        nuevos.delete('page');
                    }

                    return nuevos;
                },
                { replace: true },
            );
        },
        [setParams],
    );

    const reemplazar = useCallback((nuevos: Record<string, string>) => setParams(nuevos, { replace: true }), [setParams]);

    return { params, valores, cambiar, reemplazar };
}

/**
 * Campo de texto ligado a un parámetro de la URL con espera (debounce): la búsqueda se aplica
 * cuando el usuario deja de escribir. Si la URL cambia desde fuera (Limpiar, atrás), el texto se actualiza.
 */
export function useTextoUrl(clave: string, actual: string, cambiar: CambiarParametros, espera = 400) {
    const [texto, setTexto] = useState(actual);
    const demorado = useDebounce(texto, espera);
    const enviado = useRef(actual);
    // `cambiar` cambia de identidad con cada cambio de la URL: se lee desde una referencia para que
    // el efecto solo reaccione al texto escrito (y no reaplique un texto viejo al cambiar otro filtro).
    const cambiarRef = useRef(cambiar);
    cambiarRef.current = cambiar;

    useEffect(() => {
        const limpio = demorado.trim();

        if (limpio === enviado.current) {
            return;
        }

        enviado.current = limpio;
        cambiarRef.current({ [clave]: limpio || null });
    }, [demorado, clave]);

    useEffect(() => {
        if (actual !== enviado.current) {
            enviado.current = actual;
            setTexto(actual);
        }
    }, [actual]);

    return [texto, setTexto] as const;
}

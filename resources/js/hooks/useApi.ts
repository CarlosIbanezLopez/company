import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';
import { api, estadoError, mensajeError } from '@/lib/api';

export interface EstadoApi<T> {
    datos: T | null;
    cargando: boolean;
    /** Mensaje de error en español (null si no hubo error). */
    error: string | null;
    /** Código HTTP del error (404, 403...) o null. */
    estado: number | null;
    /** Vuelve a pedir los datos (p. ej. después de guardar). */
    recargar: () => void;
    setDatos: React.Dispatch<React.SetStateAction<T | null>>;
}

/**
 * Obtiene datos de la API (GET) y vuelve a pedirlos cuando cambian la ruta o los parámetros.
 *   const { datos, cargando, error, estado } = useApi<RespuestaNoticias>('noticias', { page, q });
 * Pase null como ruta para no pedir nada todavía.
 */
export function useApi<T>(ruta: string | null, params?: Record<string, unknown>): EstadoApi<T> {
    const [datos, setDatos] = useState<T | null>(null);
    const [cargando, setCargando] = useState<boolean>(ruta !== null);
    const [error, setError] = useState<string | null>(null);
    const [estado, setEstado] = useState<number | null>(null);
    const [version, setVersion] = useState(0);
    const clave = JSON.stringify(params ?? {});
    const paramsRef = useRef(params);
    paramsRef.current = params;

    useEffect(() => {
        if (ruta === null) {
            setCargando(false);

            return;
        }

        const control = new AbortController();
        setCargando(true);
        setError(null);
        setEstado(null);

        // Quita parámetros vacíos para no ensuciar la URL de la API.
        const limpios = Object.fromEntries(
            Object.entries(paramsRef.current ?? {}).filter(([, v]) => v !== '' && v !== null && v !== undefined),
        );

        api.get<T>(ruta, { params: limpios, signal: control.signal })
            .then((respuesta) => setDatos(respuesta.data))
            .catch((e: unknown) => {
                if (axios.isCancel(e)) {
                    return;
                }

                setError(mensajeError(e));
                setEstado(estadoError(e));
            })
            .finally(() => {
                if (!control.signal.aborted) {
                    setCargando(false);
                }
            });

        return () => control.abort();
    }, [ruta, clave, version]);

    const recargar = useCallback(() => setVersion((v) => v + 1), []);

    return { datos, cargando, error, estado, recargar, setDatos };
}

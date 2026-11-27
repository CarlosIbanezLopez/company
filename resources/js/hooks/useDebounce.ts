import { useEffect, useState } from 'react';

/** Devuelve el valor después de que deja de cambiar durante `espera` ms (búsquedas mientras se escribe). */
export function useDebounce<T>(valor: T, espera = 300): T {
    const [demorado, setDemorado] = useState(valor);

    useEffect(() => {
        const temporizador = setTimeout(() => setDemorado(valor), espera);

        return () => clearTimeout(temporizador);
    }, [valor, espera]);

    return demorado;
}

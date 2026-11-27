import { useEffect } from 'react';
import { config } from '@/lib/config';

/** Título de la pestaña del navegador: useTitulo('Noticias') -> "Noticias - Administración de Empresas". */
export function useTitulo(titulo?: string | null): void {
    useEffect(() => {
        document.title = titulo ? `${titulo} - ${config.nombre}` : config.nombre;
    }, [titulo]);
}

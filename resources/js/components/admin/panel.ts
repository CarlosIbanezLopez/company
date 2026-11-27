import { useOutletContext } from 'react-router';
import type { Usuario } from '@/types';

/*
 * Contexto que el AdminLayout comparte con las páginas del panel (vía <Outlet context>)
 * y utilidades de redirección después de iniciar sesión.
 */
export interface ContextoPanel {
    /** Vuelve a pedir GET /api/menu (p. ej. después de editar el menú o un rol). */
    recargarMenu: () => void;
}

const SIN_PANEL: ContextoPanel = { recargarMenu: () => undefined };

/** Contexto del panel; fuera del AdminLayout (p. ej. /cuenta en el sitio) devuelve funciones vacías. */
export function usePanel(): ContextoPanel {
    const contexto = useOutletContext<ContextoPanel | undefined>();

    return contexto && typeof contexto.recargarMenu === 'function' ? contexto : SIN_PANEL;
}

/* ------------------------------------------------------------------ Redirección tras el login */

/** Ruta interna segura para ?redirigir= (evita redirecciones abiertas a otros dominios). */
export function destinoSeguro(valor: string | null | undefined): string | null {
    if (!valor || !valor.startsWith('/') || valor.startsWith('//') || valor.startsWith('/\\')) {
        return null;
    }

    if (/^\/(login|olvide-contrasena|restablecer-contrasena)(\/|\?|$)/.test(valor)) {
        return null;
    }

    return valor;
}

/** Página de inicio según el tipo de cuenta. */
export function destinoPorDefecto(usuario: Pick<Usuario, 'panel' | 'es_graduado'>): string {
    if (usuario.panel) {
        return '/admin';
    }

    return usuario.es_graduado ? '/graduados/mi-ficha' : '/';
}

export const esRutaPanel = (ruta: string): boolean => ruta === '/admin' || ruta.startsWith('/admin/') || ruta.startsWith('/admin?');

/**
 * Destino pendiente después del login. Si la guardia de invitados envía primero al usuario a /admin,
 * el AdminLayout lo consume al montarse y completa la redirección a la página solicitada.
 */
let destinoPendiente: string | null = null;

export function guardarDestinoPendiente(ruta: string | null): void {
    destinoPendiente = ruta;
}

export function tomarDestinoPendiente(): string | null {
    const ruta = destinoPendiente;
    destinoPendiente = null;

    return ruta;
}

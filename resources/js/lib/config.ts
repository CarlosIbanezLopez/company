/*
 * Configuración de la SPA inyectada por Laravel en la vista app.blade.php.
 * Permite desplegar en la raíz del dominio o en un subdirectorio del servidor de la materia.
 */
const inyectada = window.APP_CONFIG ?? {};

const base = (inyectada.base ?? '').replace(/\/+$/, '');

export const config = {
    nombre: inyectada.nombre ?? 'Administración de Empresas',
    /** Ruta base de la SPA ('' en la raíz). */
    base,
    /** URL absoluta de la API RESTful, sin barra final. */
    api: (inyectada.api ?? `${window.location.origin}${base}/api`).replace(/\/+$/, ''),
};

/** URL absoluta de una ruta de la SPA (para enlaces fuera de React Router, p. ej. compartir). */
export function urlSitio(ruta = '/'): string {
    return `${window.location.origin}${config.base}${ruta.startsWith('/') ? ruta : `/${ruta}`}`;
}

/** URL absoluta de un endpoint de la API (para enlaces directos como descargas públicas). */
export function urlApi(ruta: string): string {
    return `${config.api}/${ruta.replace(/^\/+/, '')}`;
}

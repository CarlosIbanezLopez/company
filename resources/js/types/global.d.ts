export {};

declare global {
    interface Window {
        /** Configuración inyectada por resources/views/app.blade.php. */
        APP_CONFIG?: {
            nombre?: string;
            /** Ruta base de la SPA (vacía en la raíz o p. ej. /inf513/grupo23sa/proyecto2/public). */
            base?: string;
            /** URL absoluta de la API RESTful (p. ej. https://.../api). */
            api?: string;
        };
    }
}

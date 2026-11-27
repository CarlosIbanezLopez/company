import axios, { AxiosError } from 'axios';
import type { AxiosRequestConfig } from 'axios';
import { config } from '@/lib/config';

/*
 * Cliente HTTP de la SPA (Capa 1 → Capa 2). Todas las llamadas a la API RESTful pasan por aquí:
 *   api.get('noticias', { params: { page: 2 } })
 *   api.post('contacto', datos)
 * Las rutas se escriben SIN barra inicial (son relativas a la URL de la API).
 */
const CLAVE_TOKEN = 'token_sesion';

export const tokenSesion = {
    obtener(): string | null {
        try {
            return localStorage.getItem(CLAVE_TOKEN);
        } catch {
            return null;
        }
    },
    guardar(token: string): void {
        try {
            localStorage.setItem(CLAVE_TOKEN, token);
        } catch {
            // El navegador no permite almacenamiento: la sesión dura lo que la pestaña.
        }
    },
    borrar(): void {
        try {
            localStorage.removeItem(CLAVE_TOKEN);
        } catch {
            // Sin almacenamiento disponible.
        }
    },
};

/** Evento global que se emite cuando la API responde 401 a una solicitud autenticada. */
export const EVENTO_SESION_EXPIRADA = 'sesion-expirada';

export const api = axios.create({
    baseURL: `${config.api}/`,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    timeout: 30000,
});

api.interceptors.request.use((peticion) => {
    // Las rutas relativas no deben empezar con "/" para respetar el subdirectorio de la API.
    if (peticion.url && !/^https?:\/\//i.test(peticion.url)) {
        peticion.url = peticion.url.replace(/^\/+/, '');
    }

    const token = tokenSesion.obtener();

    if (token) {
        peticion.headers.set('Authorization', `Bearer ${token}`);
    }

    return peticion;
});

api.interceptors.response.use(
    (respuesta) => respuesta,
    (error: AxiosError) => {
        if (error.response?.status === 401 && tokenSesion.obtener()) {
            tokenSesion.borrar();
            window.dispatchEvent(new CustomEvent(EVENTO_SESION_EXPIRADA));
        }

        return Promise.reject(error);
    },
);

type CuerpoError = { message?: string; errors?: Record<string, string[] | string> };

/** Código HTTP de un error de Axios (o null si no hubo respuesta). */
export function estadoError(error: unknown): number | null {
    return axios.isAxiosError(error) ? (error.response?.status ?? null) : null;
}

/** Mensaje en español para mostrar al usuario a partir de cualquier error de la API. */
export function mensajeError(error: unknown): string {
    if (axios.isCancel(error)) {
        return 'La solicitud fue cancelada.';
    }

    if (axios.isAxiosError(error)) {
        const cuerpo = error.response?.data as CuerpoError | undefined;

        if (!error.response) {
            return error.code === 'ECONNABORTED'
                ? 'El servidor tardó demasiado en responder. Intente nuevamente.'
                : 'No se pudo conectar con el servidor. Verifique su conexión a internet.';
        }

        if (error.response.status === 422) {
            return cuerpo?.message || 'Revise los datos ingresados.';
        }

        if (cuerpo?.message && error.response.status < 500) {
            return cuerpo.message;
        }

        switch (error.response.status) {
            case 401:
                return 'Debe iniciar sesión para continuar.';
            case 403:
                return 'No tiene permiso para realizar esta acción.';
            case 404:
                return 'El recurso solicitado no existe.';
            case 413:
                return 'El archivo es demasiado grande.';
            case 429:
                return 'Demasiadas solicitudes. Espere un momento e intente de nuevo.';
            default:
                return 'Ocurrió un error en el servidor. Intente nuevamente más tarde.';
        }
    }

    return 'Ocurrió un error inesperado.';
}

/** Primer mensaje de validación (422) por campo: { email: 'El campo correo es obligatorio.' }. */
export function erroresValidacion(error: unknown): Record<string, string> {
    if (!axios.isAxiosError(error) || error.response?.status !== 422) {
        return {};
    }

    const errores = (error.response.data as CuerpoError | undefined)?.errors ?? {};

    return Object.fromEntries(
        Object.entries(errores).map(([campo, mensajes]) => [
            // "permisos.0" -> "permisos" para mostrarlo junto al campo.
            campo.replace(/\.\d+$/, ''),
            Array.isArray(mensajes) ? (mensajes[0] ?? '') : String(mensajes),
        ]),
    );
}

/**
 * Convierte un objeto en FormData (para formularios con archivos).
 * Booleanos -> "1"/"0", arreglos -> campo[] (vacío -> campo=""), null -> "".
 * Para actualizar con archivos use metodo "PUT": se envía como POST con _method=PUT.
 */
export function construirFormData(datos: Record<string, unknown>, metodo?: 'PUT' | 'PATCH' | 'DELETE'): FormData {
    const formulario = new FormData();

    for (const [clave, valor] of Object.entries(datos)) {
        if (valor instanceof File || valor instanceof Blob) {
            formulario.append(clave, valor);
        } else if (Array.isArray(valor)) {
            if (valor.length === 0) {
                formulario.append(clave, '');
            }

            for (const elemento of valor) {
                formulario.append(`${clave}[]`, elemento instanceof Blob ? elemento : String(elemento));
            }
        } else if (typeof valor === 'boolean') {
            formulario.append(clave, valor ? '1' : '0');
        } else if (valor === null || valor === undefined) {
            formulario.append(clave, '');
        } else if (typeof valor === 'object') {
            formulario.append(clave, JSON.stringify(valor));
        } else {
            formulario.append(clave, String(valor));
        }
    }

    if (metodo) {
        formulario.append('_method', metodo);
    }

    return formulario;
}

/** ¿El objeto contiene algún archivo? (decide entre JSON y multipart). */
export function contieneArchivos(datos: Record<string, unknown>): boolean {
    return Object.values(datos).some(
        (v) => v instanceof File || v instanceof Blob || (Array.isArray(v) && v.some((x) => x instanceof File)),
    );
}

/**
 * Descarga un archivo de la API (CSV, PDF...) enviando el token de sesión.
 * Ejemplo: descargarArchivo('admin/graduados/exportar', { q: 'ana' }).
 */
export async function descargarArchivo(ruta: string, params?: Record<string, unknown>, nombrePorDefecto = 'descarga'): Promise<void> {
    const opciones: AxiosRequestConfig = { params, responseType: 'blob' };
    const respuesta = await api.get<Blob>(ruta, opciones);
    const disposicion = String(respuesta.headers['content-disposition'] ?? '');
    const coincidencia = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(disposicion);
    const nombre = coincidencia ? decodeURIComponent(coincidencia[1]) : nombrePorDefecto;
    const enlace = document.createElement('a');

    enlace.href = URL.createObjectURL(respuesta.data);
    enlace.download = nombre;
    document.body.appendChild(enlace);
    enlace.click();
    enlace.remove();
    setTimeout(() => URL.revokeObjectURL(enlace.href), 1000);
}

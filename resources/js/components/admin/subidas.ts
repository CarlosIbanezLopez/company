import type { AxiosProgressEvent, AxiosRequestConfig } from 'axios';

/** Opciones de Axios para informar el avance (0-100) de una subida de archivos multipart. */
export function conProgreso(alProgresar: (porcentaje: number | null) => void): AxiosRequestConfig {
    return {
        onUploadProgress: (e: AxiosProgressEvent) => alProgresar(e.total ? Math.round((e.loaded / e.total) * 100) : null),
        // Las subidas grandes pueden tardar más que el tiempo de espera por defecto (30 s).
        timeout: 120000,
    };
}

const tamanoLegible = (bytes: number) =>
    bytes >= 1024 * 1024 ? `${(bytes / 1024 / 1024).toLocaleString('es-BO', { maximumFractionDigits: 1 })} MB` : `${Math.max(1, Math.ceil(bytes / 1024))} KB`;

export { tamanoLegible };

import type { ReglasCliente } from '@/types';

/*
 * Validación en el cliente con mensajes en español (la misma validación se repite en el servidor).
 *
 * Reglas disponibles (como texto):
 *   requerido | email | url | numerico | entero | fecha | hora | telefono | imagen | confirmado
 *   max:N   (caracteres en texto, valor en números, KB en archivos)
 *   min:N   (caracteres en texto, valor en números)
 *   entre:A,B (valor numérico entre A y B)
 *   mimes:pdf,docx (extensiones permitidas de archivo)
 *   en:a,b,c (valor debe estar en la lista)
 *   igual:otroCampo (debe coincidir con otro campo)
 *   regex:patron (expresión regular; mensaje genérico)
 *
 * Uso:
 *   const errores = validar(datos, { correo: ['requerido', 'email', 'max:150'] }, { correo: 'correo electrónico' });
 */
export type Regla = string;
export type ReglasFormulario = Record<string, Regla[]>;
export type Etiquetas = Record<string, string>;

const vacio = (v: unknown): boolean =>
    v === null ||
    v === undefined ||
    (typeof v === 'string' && v.trim() === '') ||
    (Array.isArray(v) && v.length === 0) ||
    v === false;

const esArchivo = (v: unknown): v is File => typeof File !== 'undefined' && v instanceof File;

const esNumero = (v: unknown): boolean => v !== '' && v !== null && !Number.isNaN(Number(v));

const REGEX_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
const REGEX_URL = /^https?:\/\/[^\s/$.?#].[^\s]*$/i;
const REGEX_FECHA = /^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?$/;
const REGEX_HORA = /^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/;
const REGEX_TELEFONO = /^[0-9+\-\s()]{6,30}$/;
const EXT_IMAGEN = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

function extension(archivo: File): string {
    return (archivo.name.split('.').pop() ?? '').toLowerCase();
}

/** Valida un valor contra sus reglas. Devuelve el primer mensaje de error o null. */
export function validarCampo(valor: unknown, reglas: Regla[], etiqueta: string, datos: Record<string, unknown> = {}, nombre = ''): string | null {
    const requerido = reglas.includes('requerido');

    if (vacio(valor) && valor !== false) {
        return requerido ? `El campo ${etiqueta} es obligatorio.` : null;
    }

    if (valor === false) {
        // Un checkbox requerido debe estar marcado (p. ej. aceptar términos).
        return requerido && reglas.includes('aceptado') ? `Debe aceptar ${etiqueta}.` : null;
    }

    const esNumerico = reglas.includes('numerico') || reglas.includes('entero');

    for (const regla of reglas) {
        const [nombreRegla, parametro = ''] = regla.split(/:(.*)/s);

        switch (nombreRegla) {
            case 'email':
                if (!REGEX_EMAIL.test(String(valor).trim())) {
                    return `El campo ${etiqueta} debe ser un correo electrónico válido.`;
                }
                break;
            case 'url':
                if (!REGEX_URL.test(String(valor).trim())) {
                    return `El campo ${etiqueta} debe ser una dirección web válida (que empiece con http:// o https://).`;
                }
                break;
            case 'numerico':
                if (!esNumero(valor)) {
                    return `El campo ${etiqueta} debe ser un número.`;
                }
                break;
            case 'entero':
                if (!esNumero(valor) || !Number.isInteger(Number(valor))) {
                    return `El campo ${etiqueta} debe ser un número entero.`;
                }
                break;
            case 'fecha':
                if (!REGEX_FECHA.test(String(valor)) || Number.isNaN(new Date(String(valor).replace(' ', 'T')).getTime())) {
                    return `El campo ${etiqueta} no es una fecha válida.`;
                }
                break;
            case 'hora':
                if (!REGEX_HORA.test(String(valor))) {
                    return `El campo ${etiqueta} debe tener el formato HH:MM.`;
                }
                break;
            case 'telefono':
                if (!REGEX_TELEFONO.test(String(valor))) {
                    return `El campo ${etiqueta} solo puede contener números, espacios y los signos + - ( ).`;
                }
                break;
            case 'max': {
                const n = Number(parametro);

                if (esArchivo(valor)) {
                    if (valor.size / 1024 > n) {
                        return `El archivo ${etiqueta} no debe pesar más de ${n >= 1024 ? `${(n / 1024).toLocaleString('es-BO')} MB` : `${n} KB`}.`;
                    }
                } else if (esNumerico) {
                    if (Number(valor) > n) {
                        return `El campo ${etiqueta} no debe ser mayor que ${n}.`;
                    }
                } else if (Array.isArray(valor)) {
                    if (valor.length > n) {
                        return `El campo ${etiqueta} no debe tener más de ${n} elementos.`;
                    }
                } else if (String(valor).length > n) {
                    return `El campo ${etiqueta} no debe tener más de ${n} caracteres.`;
                }
                break;
            }
            case 'min': {
                const n = Number(parametro);

                if (esNumerico) {
                    if (Number(valor) < n) {
                        return `El campo ${etiqueta} debe ser al menos ${n}.`;
                    }
                } else if (!esArchivo(valor) && !Array.isArray(valor) && String(valor).length < n) {
                    return `El campo ${etiqueta} debe tener al menos ${n} caracteres.`;
                }
                break;
            }
            case 'entre': {
                const [a, b] = parametro.split(',').map(Number);

                if (Number(valor) < a || Number(valor) > b) {
                    return `El campo ${etiqueta} debe estar entre ${a} y ${b}.`;
                }
                break;
            }
            case 'imagen':
                if (esArchivo(valor) && !EXT_IMAGEN.includes(extension(valor)) && !valor.type.startsWith('image/')) {
                    return `El archivo ${etiqueta} debe ser una imagen (JPG, PNG, GIF o WEBP).`;
                }
                break;
            case 'mimes': {
                const permitidas = parametro.split(',').map((e) => e.trim().toLowerCase());

                if (esArchivo(valor) && !permitidas.includes(extension(valor))) {
                    return `El archivo ${etiqueta} debe ser de tipo: ${permitidas.join(', ')}.`;
                }
                break;
            }
            case 'en':
                if (!parametro.split(',').includes(String(valor))) {
                    return `El valor seleccionado en ${etiqueta} no es válido.`;
                }
                break;
            case 'confirmado':
                if (String(valor) !== String(datos[`${nombre}_confirmation`] ?? '')) {
                    return `La confirmación de ${etiqueta} no coincide.`;
                }
                break;
            case 'igual':
                if (String(valor) !== String(datos[parametro] ?? '')) {
                    return `El campo ${etiqueta} no coincide.`;
                }
                break;
            case 'regex':
                if (!new RegExp(parametro).test(String(valor))) {
                    return `El formato del campo ${etiqueta} no es válido.`;
                }
                break;
            default:
                break;
        }
    }

    return null;
}

/** Valida todos los campos. Devuelve { campo: 'mensaje' } solo con los campos inválidos. */
export function validar(datos: Record<string, unknown>, reglas: ReglasFormulario, etiquetas: Etiquetas = {}): Record<string, string> {
    const errores: Record<string, string> = {};

    for (const [campo, lista] of Object.entries(reglas)) {
        const etiqueta = etiquetas[campo] ?? campo.replaceAll('_', ' ');
        const mensaje = validarCampo(datos[campo], lista, etiqueta, datos, campo);

        if (mensaje) {
            errores[campo] = mensaje;
        }
    }

    return errores;
}

/**
 * Convierte las reglas que envía el servidor para cada campo del CRUD (derivadas de las reglas de Laravel)
 * en reglas del validador del cliente. Así el cliente valida exactamente lo mismo que el servidor.
 */
export function reglasDesdeServidor(r: ReglasCliente | undefined, tipo = 'text', esEdicion = false): Regla[] {
    if (!r) {
        return [];
    }

    const reglas: Regla[] = [];

    // En edición los archivos/contraseñas ya existentes no son obligatorios.
    if (r.requerido && !(esEdicion && ['image', 'file', 'password'].includes(tipo))) {
        reglas.push('requerido');
    }
    if (r.email || tipo === 'email') reglas.push('email');
    if (r.url || tipo === 'url') reglas.push('url');
    if (r.entero) reglas.push('entero');
    else if (r.numerico) reglas.push('numerico');
    if (r.fecha) reglas.push('fecha');
    if (r.hora) reglas.push('hora');
    if (r.imagen) reglas.push('imagen');
    if (r.mimes?.length) reglas.push(`mimes:${r.mimes.join(',')}`);
    if (r.max !== undefined && r.max !== null) reglas.push(`max:${r.max}`);
    if (r.min !== undefined && r.min !== null) reglas.push(`min:${r.min}`);
    if (r.entre) reglas.push(`entre:${r.entre[0]},${r.entre[1]}`);
    if (r.enLista?.length && !['multiselect', 'checkboxes', 'matriz'].includes(tipo)) reglas.push(`en:${r.enLista.join(',')}`);
    if (r.confirmado) reglas.push('confirmado');

    return reglas;
}

import { usePage } from '@inertiajs/vue3';

/**
 * Antepone la carpeta base de la aplicación (por si se publica en un subdirectorio).
 */
export function u(ruta: string): string {
    const base = (usePage().props.base as string | undefined) ?? '';

    if (/^https?:\/\//.test(ruta)) {
        return ruta;
    }

    return base + (ruta.startsWith('/') ? ruta : `/${ruta}`);
}

const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

/** Formatea una fecha ISO (YYYY-MM-DD...) como 05 oct 2026. */
export function fecha(valor: string | null | undefined, conHora = false): string {
    if (!valor) {
        return '—';
    }

    const [d, t] = valor.replace('T', ' ').split(' ');
    const [y, m, dia] = d.split('-');

    if (!y || !m || !dia) {
        return valor;
    }

    const texto = `${dia.padStart(2, '0')} ${meses[Number(m) - 1]} ${y}`;

    return conHora && t ? `${texto} ${t.slice(0, 5)}` : texto;
}

export function hora(valor: string | null | undefined): string {
    return valor ? valor.slice(0, 5) : '';
}

export function numero(valor: number | null | undefined): string {
    return new Intl.NumberFormat('es-BO').format(valor ?? 0);
}

/** Obtiene un valor anidado con notación de puntos: "categoria.nombre". */
export function valorDe(objeto: Record<string, unknown>, clave: string): unknown {
    return clave.split('.').reduce<unknown>((acc, parte) => (acc && typeof acc === 'object' ? (acc as Record<string, unknown>)[parte] : undefined), objeto);
}

/** Convierte una URL de YouTube en su URL de inserción, si corresponde. */
export function youtubeEmbed(url: string): string | null {
    const m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([\w-]{11})/);

    return m ? `https://www.youtube.com/embed/${m[1]}` : null;
}

export function etiquetaEstado(valor: string): string {
    return valor.replaceAll('_', ' ');
}

/*
 * Utilidades de formato para mostrar datos de la API en español (Bolivia).
 */

const meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

/** Formatea una fecha ISO (YYYY-MM-DD o YYYY-MM-DDTHH:mm...) como "05 oct 2026" (con hora opcional). */
export function fecha(valor: string | null | undefined, conHora = false): string {
    if (!valor) {
        return '—';
    }

    // Fechas con zona horaria (serializadas por Eloquent en UTC): se muestran en hora local.
    if (/T\d{2}:\d{2}/.test(valor) && /(Z|[+-]\d{2}:?\d{2})$/.test(valor)) {
        const d = new Date(valor);

        if (!Number.isNaN(d.getTime())) {
            const texto = `${String(d.getDate()).padStart(2, '0')} ${meses[d.getMonth()]} ${d.getFullYear()}`;

            return conHora ? `${texto} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}` : texto;
        }
    }

    const [d, t] = valor.replace('T', ' ').split(' ');
    const [y, m, dia] = d.split('-');

    if (!y || !m || !dia) {
        return valor;
    }

    const texto = `${dia.slice(0, 2).padStart(2, '0')} ${meses[Number(m) - 1]} ${y}`;

    return conHora && t ? `${texto} ${t.slice(0, 5)}` : texto;
}

/** "2026-10-05 14:30:00" -> "05 oct 2026 14:30". */
export function fechaHora(valor: string | null | undefined): string {
    return fecha(valor, true);
}

/** "14:30:00" -> "14:30". */
export function hora(valor: string | null | undefined): string {
    return valor ? valor.slice(0, 5) : '';
}

export function numero(valor: number | string | null | undefined): string {
    return new Intl.NumberFormat('es-BO').format(Number(valor ?? 0));
}

/** Obtiene un valor anidado con notación de puntos: valorDe(fila, 'categoria.nombre'). */
export function valorDe(objeto: Record<string, unknown> | null | undefined, clave: string): unknown {
    return clave
        .split('.')
        .reduce<unknown>((acc, parte) => (acc && typeof acc === 'object' ? (acc as Record<string, unknown>)[parte] : undefined), objeto);
}

/** Convierte una URL de YouTube en su URL de inserción, si corresponde. */
export function youtubeEmbed(url: string | null | undefined): string | null {
    const m = url?.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/shorts\/)([\w-]{11})/);

    return m ? `https://www.youtube.com/embed/${m[1]}` : null;
}

/** "en_revision" -> "en revision". */
export function etiquetaEstado(valor: string | null | undefined): string {
    return (valor ?? '').replaceAll('_', ' ');
}

/** Hace corto un texto largo: "Lorem ipsum…". */
export function recortar(texto: string | null | undefined, largo = 160): string {
    const limpio = (texto ?? '').replace(/<[^>]+>/g, '').trim();

    return limpio.length > largo ? `${limpio.slice(0, largo - 1).trimEnd()}…` : limpio;
}

/** Concordancia de número: plural(3, 'visita', 'visitas') -> "3 visitas". */
export function plural(n: number, singular: string, pluralTexto = `${singular}s`): string {
    return `${numero(n)} ${n === 1 ? singular : pluralTexto}`;
}

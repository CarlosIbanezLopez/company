/*
 * Fechas locales (zona horaria del navegador) en formato YYYY-MM-DD para filtros de período.
 * No se usa toISOString() porque convierte a UTC y en Bolivia (UTC-4) cambiaría de día por la noche.
 */
const dos = (n: number) => String(n).padStart(2, '0');

export function fechaLocal(d: Date = new Date()): string {
    return `${d.getFullYear()}-${dos(d.getMonth() + 1)}-${dos(d.getDate())}`;
}

/** Fecha de hace `dias` días (0 = hoy). */
export function haceDias(dias: number): string {
    const d = new Date();
    d.setDate(d.getDate() - dias);

    return fechaLocal(d);
}

/** "2026-10-05" -> "05/10". */
export function diaMes(valor: string | number | null | undefined): string {
    const texto = String(valor ?? '');

    return /^\d{4}-\d{2}-\d{2}/.test(texto) ? `${texto.slice(8, 10)}/${texto.slice(5, 7)}` : texto;
}

const DIAS = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

/** "2026-10-05" -> "lun 05 oct 2026" (para tooltips de gráficos). */
export function fechaConDia(valor: string | number | null | undefined): string {
    const texto = String(valor ?? '');
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(texto);

    if (!m) {
        return texto;
    }

    const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));

    return `${DIAS[d.getDay()]} ${m[3]} ${MESES[d.getMonth()]} ${m[1]}`;
}

/** 7 -> "07:00". */
export function horaDelDia(valor: string | number | null | undefined): string {
    return `${dos(Number(valor ?? 0))}:00`;
}

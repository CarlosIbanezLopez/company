import { Fragment, useMemo } from 'react';

interface Parte {
    texto: string;
    coincide: boolean;
}

/** Quita acentos y pasa a minúsculas: "Gestión" → "gestion". */
export function normalizar(texto: string): string {
    return texto.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
}

/**
 * Divide un texto en partes que coinciden o no con la búsqueda, sin distinguir mayúsculas ni acentos
 * ("gestion" resalta "Gestión"), conservando el texto original.
 */
export function dividirCoincidencias(texto: string, consulta: string, minimo = 2): Parte[] {
    const buscado = normalizar(consulta.trim());

    if (!texto || buscado.length < minimo) {
        return [{ texto, coincide: false }];
    }

    // Texto normalizado y, por cada carácter normalizado, su posición en el texto original.
    let normal = '';
    const posiciones: number[] = [];

    for (let i = 0; i < texto.length; i++) {
        const caracter = normalizar(texto[i]);

        for (let k = 0; k < caracter.length; k++) {
            normal += caracter[k];
            posiciones.push(i);
        }
    }

    const partes: Parte[] = [];
    let cursor = 0;
    let desde = 0;

    for (;;) {
        const encontrado = normal.indexOf(buscado, desde);

        if (encontrado === -1) {
            break;
        }

        const inicio = posiciones[encontrado];
        const fin = posiciones[encontrado + buscado.length - 1] + 1;

        if (inicio > cursor) {
            partes.push({ texto: texto.slice(cursor, inicio), coincide: false });
        }

        partes.push({ texto: texto.slice(inicio, fin), coincide: true });
        cursor = fin;
        desde = encontrado + buscado.length;
    }

    if (cursor < texto.length) {
        partes.push({ texto: texto.slice(cursor), coincide: false });
    }

    return partes;
}

/** Muestra el texto con las coincidencias de la búsqueda resaltadas (<mark>). */
export function Resaltado({ texto, consulta, minimo = 2 }: { texto: string | null | undefined; consulta: string; minimo?: number }) {
    const partes = useMemo(() => dividirCoincidencias(texto ?? '', consulta, minimo), [texto, consulta, minimo]);

    return (
        <>
            {partes.map((parte, i) =>
                parte.coincide ? (
                    <mark key={i} className="rounded-sm bg-aviso/40 px-0.5 text-inherit">
                        {parte.texto}
                    </mark>
                ) : (
                    <Fragment key={i}>{parte.texto}</Fragment>
                ),
            )}
        </>
    );
}

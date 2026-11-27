/*
 * Conversión mínima de Markdown a HTML para la vista previa del editor del panel.
 * Primero se escapa todo el texto (no se permite HTML del usuario) y luego se aplican
 * encabezados, listas, citas, negrita, cursiva, código y enlaces/imágenes con URL segura.
 * El HTML definitivo del sitio lo genera el servidor (App\Support\Markdown).
 */
const escapar = (texto: string): string =>
    texto.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');

const urlSegura = (url: string): boolean => /^(https?:\/\/|\/|#|mailto:)/i.test(url);

function enLinea(texto: string): string {
    let t = escapar(texto);

    t = t.replace(/`([^`]+)`/g, '<code>$1</code>');
    t = t.replace(/!\[([^\]]*)\]\(([^)\s]+)\)/g, (_, alt: string, url: string) => (urlSegura(url) ? `<img src="${url}" alt="${alt}">` : alt));
    t = t.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (_, enlace: string, url: string) =>
        urlSegura(url) ? `<a href="${url}" target="_blank" rel="noopener noreferrer">${enlace}</a>` : enlace,
    );
    t = t.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    t = t.replace(/(^|[^*\w])\*([^*\s][^*]*?)\*(?!\w)/g, '$1<em>$2</em>');
    t = t.replace(/(^|[^\w])_([^_\s][^_]*?)_(?!\w)/g, '$1<em>$2</em>');

    return t;
}

const ES_TITULO = /^(#{1,6})\s+(.*)$/;
const ES_SEPARADOR = /^\s*([-*_])(\s*\1){2,}\s*$/;
const ES_CITA = /^>\s?/;
const ES_VINETA = /^\s*[-*+]\s+/;
const ES_NUMERADA = /^\s*\d+[.)]\s+/;

export function markdownAHtml(markdown: string | null | undefined): string {
    const lineas = String(markdown ?? '').replace(/\r\n?/g, '\n').split('\n');
    const html: string[] = [];
    let i = 0;

    while (i < lineas.length) {
        const linea = lineas[i];

        if (!linea.trim()) {
            i++;
            continue;
        }

        const titulo = ES_TITULO.exec(linea);

        if (titulo) {
            // El h1 de la página ya existe: los títulos del contenido empiezan en h2.
            const nivel = Math.min(4, Math.max(2, titulo[1].length));
            html.push(`<h${nivel}>${enLinea(titulo[2])}</h${nivel}>`);
            i++;
            continue;
        }

        if (ES_SEPARADOR.test(linea)) {
            html.push('<hr>');
            i++;
            continue;
        }

        if (ES_CITA.test(linea)) {
            const partes: string[] = [];

            while (i < lineas.length && ES_CITA.test(lineas[i])) {
                partes.push(enLinea(lineas[i].replace(ES_CITA, '')));
                i++;
            }

            html.push(`<blockquote>${partes.join('<br>')}</blockquote>`);
            continue;
        }

        if (ES_VINETA.test(linea) || ES_NUMERADA.test(linea)) {
            const numerada = ES_NUMERADA.test(linea);
            const patron = numerada ? ES_NUMERADA : ES_VINETA;
            const elementos: string[] = [];

            while (i < lineas.length && patron.test(lineas[i])) {
                elementos.push(`<li>${enLinea(lineas[i].replace(patron, ''))}</li>`);
                i++;
            }

            html.push(numerada ? `<ol>${elementos.join('')}</ol>` : `<ul>${elementos.join('')}</ul>`);
            continue;
        }

        const parrafo: string[] = [];

        do {
            parrafo.push(enLinea(lineas[i]));
            i++;
        } while (
            i < lineas.length &&
            lineas[i].trim() &&
            !ES_TITULO.test(lineas[i]) &&
            !ES_CITA.test(lineas[i]) &&
            !ES_VINETA.test(lineas[i]) &&
            !ES_NUMERADA.test(lineas[i])
        );

        html.push(`<p>${parrafo.join('<br>')}</p>`);
    }

    return html.join('');
}

import type { ReactNode } from 'react';
import { Link } from 'react-router';

/*
 * Enlaces del portal. Las URL que entrega la API pueden ser rutas de la SPA ("/noticias/abc"),
 * que se abren con React Router, o direcciones externas / archivos ("https://..."), que se abren
 * con un enlace normal en una pestaña nueva.
 */

/** ¿Es una dirección absoluta (otro sitio, archivo del servidor)? */
export function esExterna(url: string): boolean {
    return /^([a-z][a-z0-9+.-]*:)?\/\//i.test(url);
}

/** Ruta de la SPA para React Router, o null si la URL es externa (mailto:, tel:, https://...). */
export function rutaInterna(url: string | null | undefined): string | null {
    const valor = (url ?? '').trim();

    if (valor === '' || esExterna(valor) || /^(mailto|tel):/i.test(valor)) {
        return null;
    }

    return valor.startsWith('/') ? valor : `/${valor}`;
}

/** Texto para lectores de pantalla que avisa que el enlace abre otra pestaña. */
export function AvisoPestana() {
    return <span className="sr-only"> (se abre en una pestaña nueva)</span>;
}

interface PropsEnlace {
    href: string;
    className?: string;
    title?: string;
    children: ReactNode;
}

/** Enlace a otro sitio o a un archivo: se abre en una pestaña nueva y lo anuncia. */
export function EnlaceExterno({ href, className, title, children }: PropsEnlace) {
    return (
        <a href={href} target="_blank" rel="noopener noreferrer" className={className} title={title}>
            {children}
            <AvisoPestana />
        </a>
    );
}

/**
 * Elige el tipo de enlace según la URL: ruta de la SPA → <Link>, externa → pestaña nueva,
 * mailto/tel → enlace simple; sin URL dibuja un contenedor sin enlace.
 */
export function EnlaceFlexible({ href, className, title, children }: Omit<PropsEnlace, 'href'> & { href: string | null | undefined }) {
    if (!href) {
        return <div className={className}>{children}</div>;
    }

    if (/^(mailto|tel):/i.test(href)) {
        return (
            <a href={href} className={className} title={title}>
                {children}
            </a>
        );
    }

    const ruta = rutaInterna(href);

    if (ruta === null) {
        return (
            <EnlaceExterno href={href} className={className} title={title}>
                {children}
            </EnlaceExterno>
        );
    }

    return (
        <Link to={ruta} className={className} title={title}>
            {children}
        </Link>
    );
}

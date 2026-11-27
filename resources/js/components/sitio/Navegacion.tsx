import { ChevronDown } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import type { KeyboardEvent, ReactNode } from 'react';
import { Link, useLocation } from 'react-router';
import { cn } from '@/lib/utils';
import type { DatosSitio } from '@/types';

export interface EnlaceNav {
    titulo: string;
    url: string;
}

export interface GrupoNav {
    titulo: string;
    /** Grupo que es un enlace directo (sin submenú). */
    url?: string;
    items?: EnlaceNav[];
}

/**
 * Menú del portal: secciones fijas más las páginas institucionales y académicas que se
 * administran desde el panel (llegan en GET /api/sitio → menu).
 */
export function construirMenu(menu: DatosSitio['menu']): GrupoNav[] {
    return [
        { titulo: 'Inicio', url: '/' },
        {
            titulo: 'La Carrera',
            items: [
                ...menu.institucional.map((p) => ({ titulo: p.titulo, url: `/institucional/${p.slug}` })),
                { titulo: 'Autoridades y docentes', url: '/plantel-docente' },
            ],
        },
        {
            titulo: 'Académica',
            items: [
                { titulo: 'Malla curricular', url: '/malla-curricular' },
                ...menu.academico.map((p) => ({ titulo: p.titulo, url: `/academico/${p.slug}` })),
                { titulo: 'Calendario académico', url: '/calendario-academico' },
                { titulo: 'Preguntas frecuentes', url: '/preguntas-frecuentes' },
            ],
        },
        {
            titulo: 'Investigación',
            items: [
                { titulo: 'Líneas y proyectos', url: '/investigacion' },
                { titulo: 'Proyectos de Investigación en Aula (PIA)', url: '/investigacion?tipo=pia' },
                { titulo: 'Semilleros de investigación', url: '/investigacion?tipo=semillero' },
                { titulo: 'Producción académica', url: '/investigacion?tipo=produccion' },
                { titulo: 'Biblioteca digital', url: '/biblioteca' },
            ],
        },
        {
            titulo: 'Vinculación',
            items: [
                { titulo: 'Convenios y relaciones', url: '/vinculacion' },
                { titulo: 'Empresa Junior INNOVA', url: '/innova' },
            ],
        },
        {
            titulo: 'INNOVAR',
            items: [
                { titulo: 'Revista INNOVAR', url: '/revista-innovar' },
                { titulo: 'Normas para autores', url: '/revista-innovar/normas-para-autores' },
            ],
        },
        { titulo: 'Alumni', url: '/graduados' },
        {
            titulo: 'Noticias',
            items: [
                { titulo: 'Noticias y comunicados', url: '/noticias' },
                { titulo: 'Eventos académicos', url: '/eventos' },
                { titulo: 'Galería multimedia', url: '/galeria' },
            ],
        },
        { titulo: 'Contacto', url: '/contacto' },
    ];
}

/** Qué tan bien corresponde un enlace del menú a la ubicación actual (-1 = no corresponde). */
function puntaje(url: string, pathname: string, params: URLSearchParams): number {
    const [ruta, consulta = ''] = url.split('?');
    let base: number;

    if (pathname === ruta) {
        base = 100;
    } else if (ruta !== '/' && pathname.startsWith(`${ruta}/`)) {
        base = 50;
    } else {
        return -1;
    }

    let coincidencias = 0;

    for (const [clave, valor] of new URLSearchParams(consulta)) {
        if (params.get(clave) !== valor) {
            return -1;
        }

        coincidencias++;
    }

    return base + coincidencias;
}

/** URL del menú que mejor representa la página actual (se marca con aria-current="page"). */
export function enlaceActivo(grupos: GrupoNav[], pathname: string, search: string): string | null {
    const params = new URLSearchParams(search);
    let mejor: string | null = null;
    let maximo = -1;

    for (const grupo of grupos) {
        for (const url of [grupo.url, ...(grupo.items ?? []).map((i) => i.url)]) {
            if (!url) {
                continue;
            }

            const p = puntaje(url, pathname, params);

            if (p > maximo) {
                maximo = p;
                mejor = url;
            }
        }
    }

    return mejor;
}

const grupoDe = (grupos: GrupoNav[], activa: string | null) => grupos.find((g) => g.items?.some((i) => i.url === activa))?.titulo ?? null;

/** Mueve el foco entre los enlaces de un submenú (flechas, Inicio y Fin). */
function enfocarEnlace(idLista: string, destino: 'inicio' | 'fin' | 1 | -1): void {
    const enlaces = Array.from(document.getElementById(idLista)?.querySelectorAll<HTMLAnchorElement>('a') ?? []);

    if (enlaces.length === 0) {
        return;
    }

    const actual = enlaces.indexOf(document.activeElement as HTMLAnchorElement);
    let siguiente: number;

    if (destino === 'inicio') {
        siguiente = 0;
    } else if (destino === 'fin') {
        siguiente = enlaces.length - 1;
    } else if (actual === -1) {
        siguiente = destino === 1 ? 0 : enlaces.length - 1;
    } else {
        siguiente = (actual + destino + enlaces.length) % enlaces.length;
    }

    enlaces[siguiente].focus();
}

const CLASE_ENLACE =
    'flex h-12 items-center border-b-2 border-transparent px-2.5 text-sm font-medium whitespace-nowrap transition-colors hover:border-marca hover:text-primary';

/**
 * Barra de navegación de escritorio (desde xl) con submenús desplegables accesibles:
 * se abren al pasar el mouse, con clic o con las flechas del teclado; Escape los cierra
 * y se cierran al navegar, al hacer clic fuera o al salir el foco del grupo.
 */
export function NavegacionPrincipal({ grupos, activa }: { grupos: GrupoNav[]; activa: string | null }) {
    const base = useId();
    const ubicacion = useLocation();
    const [abierto, setAbierto] = useState<string | null>(null);
    const [clave, setClave] = useState(ubicacion.key);
    const abiertoConPuntero = useRef(false);
    const barra = useRef<HTMLUListElement>(null);

    // Al navegar se cierra cualquier submenú abierto.
    if (clave !== ubicacion.key) {
        setClave(ubicacion.key);
        setAbierto(null);
    }

    useEffect(() => {
        if (abierto === null) {
            return;
        }

        const alClic = (e: MouseEvent) => {
            if (!barra.current?.contains(e.target as Node)) {
                setAbierto(null);
            }
        };

        document.addEventListener('mousedown', alClic);

        return () => document.removeEventListener('mousedown', alClic);
    }, [abierto]);

    const grupoActivo = grupoDe(grupos, activa);

    function alTeclearBoton(e: KeyboardEvent<HTMLButtonElement>, titulo: string, idLista: string) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            setAbierto(titulo);
            const destino = e.key === 'ArrowDown' ? 'inicio' : 'fin';
            requestAnimationFrame(() => enfocarEnlace(idLista, destino));
        } else if (e.key === 'Escape') {
            setAbierto(null);
        }
    }

    function alTeclearLista(e: KeyboardEvent<HTMLUListElement>, idLista: string, idBoton: string) {
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                enfocarEnlace(idLista, 1);
                break;
            case 'ArrowUp':
                e.preventDefault();
                enfocarEnlace(idLista, -1);
                break;
            case 'Home':
                e.preventDefault();
                enfocarEnlace(idLista, 'inicio');
                break;
            case 'End':
                e.preventDefault();
                enfocarEnlace(idLista, 'fin');
                break;
            case 'Escape':
                e.preventDefault();
                setAbierto(null);
                document.getElementById(idBoton)?.focus();
                break;
            default:
                break;
        }
    }

    return (
        <nav aria-label="Navegación principal" className="hidden border-t xl:block">
            <ul ref={barra} className="mx-auto flex max-w-7xl items-stretch justify-between px-4 sm:px-6">
                {grupos.map((grupo, i) => {
                    if (grupo.url) {
                        return (
                            <li key={grupo.titulo} className="flex">
                                <Link
                                    to={grupo.url}
                                    aria-current={activa === grupo.url ? 'page' : undefined}
                                    className={cn(CLASE_ENLACE, activa === grupo.url && 'border-marca text-primary')}
                                >
                                    {grupo.titulo}
                                </Link>
                            </li>
                        );
                    }

                    const idLista = `${base}-submenu-${i}`;
                    const idBoton = `${base}-boton-${i}`;
                    const estaAbierto = abierto === grupo.titulo;

                    return (
                        <li
                            key={grupo.titulo}
                            className="relative flex"
                            onPointerEnter={(e) => {
                                if (e.pointerType === 'mouse') {
                                    abiertoConPuntero.current = true;
                                    setAbierto(grupo.titulo);
                                }
                            }}
                            onPointerLeave={(e) => {
                                if (e.pointerType === 'mouse') {
                                    abiertoConPuntero.current = false;
                                    setAbierto((a) => (a === grupo.titulo ? null : a));
                                }
                            }}
                            onBlur={(e) => {
                                if (!e.currentTarget.contains(e.relatedTarget as Node | null)) {
                                    setAbierto((a) => (a === grupo.titulo ? null : a));
                                }
                            }}
                        >
                            <button
                                id={idBoton}
                                type="button"
                                aria-expanded={estaAbierto}
                                aria-controls={idLista}
                                onClick={() => {
                                    // Si se abrió al pasar el mouse, el clic no debe cerrarlo.
                                    if (abiertoConPuntero.current && estaAbierto) {
                                        abiertoConPuntero.current = false;

                                        return;
                                    }

                                    setAbierto(estaAbierto ? null : grupo.titulo);
                                }}
                                onKeyDown={(e) => alTeclearBoton(e, grupo.titulo, idLista)}
                                className={cn(CLASE_ENLACE, 'gap-1', (estaAbierto || grupoActivo === grupo.titulo) && 'border-marca text-primary')}
                            >
                                {grupo.titulo}
                                <ChevronDown className={cn('size-3.5 transition-transform', estaAbierto && 'rotate-180')} aria-hidden="true" />
                            </button>
                            <ul
                                id={idLista}
                                hidden={!estaAbierto}
                                onKeyDown={(e) => alTeclearLista(e, idLista, idBoton)}
                                className={cn(
                                    'absolute top-full z-50 min-w-64 rounded-lg border border-t-2 border-t-marca bg-popover py-2 text-popover-foreground shadow-lg',
                                    // Los últimos grupos se despliegan hacia la izquierda para no salirse de la pantalla.
                                    i >= grupos.length - 3 ? 'right-0' : 'left-0',
                                )}
                            >
                                {grupo.items?.map((item) => (
                                    <li key={item.url}>
                                        <Link
                                            to={item.url}
                                            aria-current={activa === item.url ? 'page' : undefined}
                                            className={cn(
                                                'block border-l-2 border-transparent px-4 py-2 text-sm hover:border-primary hover:bg-accent focus-visible:bg-accent',
                                                activa === item.url && 'border-marca font-semibold text-primary',
                                            )}
                                        >
                                            {item.titulo}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}

interface PropsMovil {
    id: string;
    grupos: GrupoNav[];
    activa: string | null;
    abierto: boolean;
    /** Contenido al final del menú (opciones de la cuenta). */
    children?: ReactNode;
}

/** Menú de navegación para pantallas pequeñas: grupos en acordeón (aria-expanded) y opciones de la cuenta. */
export function NavegacionMovil({ id, grupos, activa, abierto, children }: PropsMovil) {
    const base = useId();
    const grupoActual = grupoDe(grupos, activa);
    const [desplegado, setDesplegado] = useState<string | null>(grupoActual);
    const [abiertoPrevio, setAbiertoPrevio] = useState(abierto);

    // Al abrir el menú se despliega el grupo de la página actual.
    if (abierto !== abiertoPrevio) {
        setAbiertoPrevio(abierto);

        if (abierto) {
            setDesplegado(grupoActual);
        }
    }

    return (
        <nav id={id} aria-label="Navegación móvil" hidden={!abierto} className="max-h-[75vh] overflow-y-auto border-t bg-card xl:hidden">
            <ul>
                {grupos.map((grupo, i) => {
                    if (grupo.url) {
                        return (
                            <li key={grupo.titulo} className="border-b">
                                <Link
                                    to={grupo.url}
                                    aria-current={activa === grupo.url ? 'page' : undefined}
                                    className={cn('block px-4 py-3 text-sm font-semibold sm:px-6', activa === grupo.url && 'text-primary')}
                                >
                                    {grupo.titulo}
                                </Link>
                            </li>
                        );
                    }

                    const idPanel = `${base}-grupo-${i}`;
                    const visible = desplegado === grupo.titulo;

                    return (
                        <li key={grupo.titulo} className="border-b">
                            <button
                                type="button"
                                aria-expanded={visible}
                                aria-controls={idPanel}
                                onClick={() => setDesplegado(visible ? null : grupo.titulo)}
                                className={cn('flex w-full items-center justify-between px-4 py-3 text-left text-sm font-semibold sm:px-6', grupoActual === grupo.titulo && 'text-primary')}
                            >
                                {grupo.titulo}
                                <ChevronDown className={cn('size-4 transition-transform', visible && 'rotate-180')} aria-hidden="true" />
                            </button>
                            <ul id={idPanel} hidden={!visible} className="bg-muted/50 pb-2">
                                {grupo.items?.map((item) => (
                                    <li key={item.url}>
                                        <Link
                                            to={item.url}
                                            aria-current={activa === item.url ? 'page' : undefined}
                                            className={cn('block border-l-2 border-marca px-6 py-2 text-sm sm:px-8', activa === item.url && 'font-semibold text-primary')}
                                        >
                                            {item.titulo}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </li>
                    );
                })}
            </ul>
            {children}
        </nav>
    );
}

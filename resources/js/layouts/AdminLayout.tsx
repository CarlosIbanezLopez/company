import { ChevronRight, Globe, Menu as IconoMenu, PanelLeft, X } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, Outlet, useLocation, useNavigate } from 'react-router';
import { MenuLateral, ubicacionEnMenu } from '@/components/admin/MenuLateral';
import type { UbicacionMenu } from '@/components/admin/MenuLateral';
import { MenuUsuario } from '@/components/admin/MenuUsuario';
import { tomarDestinoPendiente } from '@/components/admin/panel';
import type { ContextoPanel } from '@/components/admin/panel';
import ContadorVisitas from '@/components/ContadorVisitas';
import { BotonesAccesibilidad, PanelApariencia } from '@/components/PanelApariencia';
import { useAuth } from '@/contexts/AuthContext';
import { useSitio } from '@/contexts/SitioContext';
import { useApi } from '@/hooks/useApi';
import { urlSitio } from '@/lib/config';
import { cn } from '@/lib/utils';
import type { GrupoMenu } from '@/types';

/*
 * Layout del panel de administración (Capa 1): barra lateral con el menú dinámico de la base de datos
 * (GET /api/menu según los permisos del rol), barra superior con el usuario, apariencia y accesibilidad,
 * contenido de la página (<Outlet>) y pie con el contador de visitas de la página actual.
 */
const CLAVE_COLAPSO = 'panel_menu_colapsado';

function leerColapso(): boolean {
    try {
        return localStorage.getItem(CLAVE_COLAPSO) === '1';
    } catch {
        return false;
    }
}

/** Texto final de las migas según lo que sigue a la ruta del menú (/crear, /5/editar, /5/fotos). */
function sufijoRuta(resto: string): string | null {
    if (/^\/crear\/?$/.test(resto)) {
        return 'Nuevo registro';
    }

    if (/^\/\d+\/editar\/?$/.test(resto)) {
        return 'Editar';
    }

    if (/^\/\d+\/fotos\/?$/.test(resto)) {
        return 'Fotografías';
    }

    return null;
}

/** Migas de pan: Panel › Grupo › Opción › Acción. */
function Migas({ ubicacion, ruta }: { ubicacion: UbicacionMenu | null; ruta: string }) {
    const partes: { texto: string; to?: string }[] = [];

    if (ruta === '/admin' || ruta === '/admin/') {
        partes.push({ texto: 'Inicio' });
    } else if (ruta === '/admin/cuenta') {
        partes.push({ texto: 'Mi cuenta' });
    } else if (ubicacion) {
        if (ubicacion.item) {
            partes.push({ texto: ubicacion.grupo.titulo });
        }

        const titulo = (ubicacion.item ?? ubicacion.grupo).titulo;
        const sufijo = sufijoRuta(ruta.slice(ubicacion.url.length));

        if (sufijo) {
            partes.push({ texto: titulo, to: ubicacion.url }, { texto: sufijo });
        } else {
            partes.push({ texto: titulo });
        }
    }

    return (
        <nav aria-label="Ruta de navegación" className="min-w-0">
            <ol className="flex min-w-0 items-center gap-1.5 text-sm">
                <li className="hidden shrink-0 sm:block">
                    <Link to="/admin" className="text-muted-foreground hover:text-foreground">
                        Panel
                    </Link>
                </li>
                {partes.map((parte, i) => {
                    const ultima = i === partes.length - 1;

                    return (
                        <li key={`${parte.texto}-${i}`} className={cn('min-w-0 items-center gap-1.5', ultima ? 'flex' : 'hidden md:flex')}>
                            <ChevronRight className={cn('size-3.5 shrink-0 text-muted-foreground', ultima ? 'hidden sm:block' : 'block')} aria-hidden="true" />
                            {parte.to ? (
                                <Link to={parte.to} className="truncate text-muted-foreground hover:text-foreground">
                                    {parte.texto}
                                </Link>
                            ) : (
                                <span className={cn('truncate', ultima ? 'font-semibold' : 'text-muted-foreground')} aria-current={ultima ? 'page' : undefined}>
                                    {parte.texto}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}

export default function AdminLayout() {
    const { usuario } = useAuth();
    const { sitio } = useSitio();
    const { pathname } = useLocation();
    const navigate = useNavigate();
    const { datos, cargando, error, recargar } = useApi<{ menu: GrupoMenu[] }>('menu');
    const menu = datos?.menu ?? null;
    const ubicacion = useMemo(() => ubicacionEnMenu(menu, pathname), [menu, pathname]);
    const [colapsado, setColapsado] = useState(leerColapso);
    const [movil, setMovil] = useState(false);
    const botonMovil = useRef<HTMLButtonElement>(null);
    const cajon = useRef<HTMLDivElement>(null);

    // Después del login, completa la redirección a la página que se había solicitado (?redirigir=).
    useEffect(() => {
        const destino = tomarDestinoPendiente();

        if (destino && destino !== pathname) {
            void navigate(destino, { replace: true });
        }
        // Solo al montar el panel (las dependencias se omiten a propósito).
    }, []);

    // Si cambian los permisos del usuario (p. ej. editó su rol), el menú se vuelve a pedir.
    const firma = usuario?.permisos.join('|') ?? '';
    const firmaAnterior = useRef(firma);

    useEffect(() => {
        if (firmaAnterior.current !== firma) {
            firmaAnterior.current = firma;
            recargar();
        }
    }, [firma, recargar]);

    useEffect(() => {
        try {
            localStorage.setItem(CLAVE_COLAPSO, colapsado ? '1' : '0');
        } catch {
            // Sin almacenamiento: la preferencia dura mientras la pestaña esté abierta.
        }
    }, [colapsado]);

    // El menú móvil se cierra al navegar.
    useEffect(() => {
        setMovil(false);
    }, [pathname]);

    // Menú móvil: Escape para cerrar, foco atrapado dentro y sin desplazar la página de fondo.
    useEffect(() => {
        if (!movil) {
            return;
        }

        const enfocables = () =>
            Array.from(cajon.current?.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])') ?? []).filter((el) => !el.hidden && el.offsetParent !== null);

        requestAnimationFrame(() => enfocables()[0]?.focus());

        const alTeclear = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                setMovil(false);
            } else if (e.key === 'Tab') {
                const lista = enfocables();

                if (!lista.length) {
                    return;
                }

                const primero = lista[0];
                const ultimo = lista[lista.length - 1];

                if (e.shiftKey && document.activeElement === primero) {
                    e.preventDefault();
                    ultimo.focus();
                } else if (!e.shiftKey && document.activeElement === ultimo) {
                    e.preventDefault();
                    primero.focus();
                }
            }
        };

        const desbordamiento = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', alTeclear);
        const boton = botonMovil.current;

        return () => {
            document.removeEventListener('keydown', alTeclear);
            document.body.style.overflow = desbordamiento;
            boton?.focus();
        };
    }, [movil]);

    const alternarColapso = useCallback(() => setColapsado((c) => !c), []);
    const contexto = useMemo<ContextoPanel>(() => ({ recargarMenu: recargar }), [recargar]);

    const propsMenu = { menu, cargando, error, onReintentar: recargar, ubicacion };

    return (
        <div className="flex min-h-svh bg-background text-foreground">
            <a
                href="#contenido"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[300] focus:rounded-lg focus:bg-primary focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-foreground"
            >
                Saltar al contenido
            </a>

            {/* Barra lateral de escritorio */}
            <div
                className={cn(
                    'sticky top-0 hidden h-svh shrink-0 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-[width] duration-200 lg:flex print:hidden',
                    colapsado ? 'w-16' : 'w-64',
                )}
            >
                <MenuLateral {...propsMenu} colapsado={colapsado} onExpandir={() => setColapsado(false)} onAlternarColapso={alternarColapso} />
            </div>

            {/* Menú móvil */}
            {movil && (
                <div className="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menú del panel">
                    <div className="absolute inset-0 bg-black/50" aria-hidden="true" onClick={() => setMovil(false)} />
                    <div ref={cajon} className="absolute inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground shadow-xl">
                        <MenuLateral {...propsMenu} />
                        <button
                            type="button"
                            className="absolute top-3.5 right-3 inline-flex size-9 items-center justify-center rounded-lg border border-sidebar-border bg-sidebar hover:bg-sidebar-accent"
                            onClick={() => setMovil(false)}
                            aria-label="Cerrar menú"
                        >
                            <X className="size-4" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            )}

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="sticky top-0 z-40 flex h-14 shrink-0 items-center gap-2 border-b bg-background/95 px-3 backdrop-blur md:px-4 print:hidden">
                    <button
                        ref={botonMovil}
                        type="button"
                        className="boton-icono size-9 lg:hidden"
                        aria-label="Abrir menú del panel"
                        aria-expanded={movil}
                        onClick={() => setMovil(true)}
                    >
                        <IconoMenu className="size-4" aria-hidden="true" />
                    </button>
                    <button
                        type="button"
                        className="boton-icono hidden size-9 lg:inline-flex"
                        aria-label={colapsado ? 'Expandir menú lateral' : 'Contraer menú lateral'}
                        title={colapsado ? 'Expandir menú lateral' : 'Contraer menú lateral'}
                        onClick={alternarColapso}
                    >
                        <PanelLeft className="size-4" aria-hidden="true" />
                    </button>

                    <Migas ubicacion={ubicacion} ruta={pathname} />

                    <div className="ml-auto flex shrink-0 items-center gap-1.5">
                        <div className="hidden xl:block">
                            <BotonesAccesibilidad />
                        </div>
                        <PanelApariencia />
                        <a
                            href={urlSitio('/')}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="hidden h-8 items-center gap-1.5 rounded-md border px-2.5 text-xs font-medium transition-colors hover:border-primary hover:text-primary sm:inline-flex"
                        >
                            <Globe className="size-4" aria-hidden="true" /> Ver sitio
                            <span className="sr-only"> (se abre en una pestaña nueva)</span>
                        </a>
                        <MenuUsuario />
                    </div>
                </header>

                <main id="contenido" tabIndex={-1} className="min-w-0 flex-1 px-4 py-5 outline-none md:px-6 md:py-6">
                    <Outlet context={contexto} />
                </main>

                <footer className="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 border-t px-4 py-3 text-xs text-muted-foreground md:px-6 print:hidden">
                    <span>Panel de administración · Carrera de {sitio.carrera}</span>
                    <ContadorVisitas />
                </footer>
            </div>
        </div>
    );
}

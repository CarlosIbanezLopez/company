import { Mail, Menu, Phone, Search, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { MouseEvent } from 'react';
import { Link, Outlet, useLocation } from 'react-router';
import { BotonesAccesibilidad, PanelApariencia } from '@/components/PanelApariencia';
import BuscadorPredictivo from '@/components/sitio/BuscadorPredictivo';
import { MenuUsuario, SesionMovil } from '@/components/sitio/MenuUsuario';
import { construirMenu, enlaceActivo, NavegacionMovil, NavegacionPrincipal } from '@/components/sitio/Navegacion';
import { PieSitio } from '@/components/sitio/PieSitio';
import { useSitio } from '@/contexts/SitioContext';

/** Botones de la barra superior (accesibilidad y apariencia) con los colores de la franja. */
const CLASE_BOTON_FRANJA = 'border-franja-foreground/30 text-franja-foreground hover:bg-franja-foreground/10 disabled:opacity-50';

/**
 * Plantilla del portal público (Capa 1): barra institucional con accesibilidad, apariencia y cuenta;
 * encabezado con búsqueda predictiva y navegación dinámica; contenido de la página; pie con contacto,
 * redes y contador de visitas.
 */
export default function SitioLayout() {
    const { sitio } = useSitio();
    const ubicacion = useLocation();
    const [menuMovil, setMenuMovil] = useState(false);
    const [buscadorMovil, setBuscadorMovil] = useState(false);
    const [clave, setClave] = useState(ubicacion.key);
    const botonMenu = useRef<HTMLButtonElement>(null);

    // Al navegar se cierran el menú y el buscador móviles.
    if (clave !== ubicacion.key) {
        setClave(ubicacion.key);
        setMenuMovil(false);
        setBuscadorMovil(false);
    }

    const grupos = useMemo(() => construirMenu(sitio.menu), [sitio.menu]);
    const activa = enlaceActivo(grupos, ubicacion.pathname, ubicacion.search);

    // Escape cierra el menú o el buscador móvil (si el buscador no usó la tecla para cerrar sus sugerencias).
    useEffect(() => {
        if (!menuMovil && !buscadorMovil) {
            return;
        }

        const alTeclear = (e: KeyboardEvent) => {
            if (e.key !== 'Escape' || e.defaultPrevented) {
                return;
            }

            if (menuMovil) {
                setMenuMovil(false);
                botonMenu.current?.focus();
            }

            setBuscadorMovil(false);
        };

        document.addEventListener('keydown', alTeclear);

        return () => document.removeEventListener('keydown', alTeclear);
    }, [menuMovil, buscadorMovil]);

    const saltarAlContenido = (e: MouseEvent<HTMLAnchorElement>) => {
        e.preventDefault();
        const principal = document.getElementById('contenido');
        principal?.focus();
        principal?.scrollIntoView();
    };

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <a
                href="#contenido"
                onClick={saltarAlContenido}
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-lg focus:bg-primary focus:px-4 focus:py-3 focus:text-sm focus:font-semibold focus:text-primary-foreground focus:shadow-lg"
            >
                Saltar al contenido
            </a>
            <AnuncioNavegacion />

            {/* Barra superior institucional */}
            <div className="border-t-4 border-marca bg-franja text-xs text-franja-foreground">
                <div className="mx-auto flex max-w-7xl items-center gap-3 px-4 py-1.5 sm:px-6">
                    <p className="hidden min-w-0 flex-1 truncate md:block">{[sitio.universidad, sitio.facultad].filter(Boolean).join(' · ')}</p>
                    <div className="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
                        {sitio.contacto.telefonos && (
                            <span className="hidden items-center gap-1.5 xl:inline-flex">
                                <Phone className="size-3.5" aria-hidden="true" />
                                {sitio.contacto.telefonos}
                            </span>
                        )}
                        {sitio.contacto.correo && (
                            <a href={`mailto:${sitio.contacto.correo}`} className="hidden items-center gap-1.5 hover:underline lg:inline-flex">
                                <Mail className="size-3.5" aria-hidden="true" />
                                {sitio.contacto.correo}
                            </a>
                        )}
                        <MenuUsuario />
                        <span className="hidden h-5 w-px bg-franja-foreground/25 sm:block" aria-hidden="true" />
                        <div className="hidden sm:block">
                            <BotonesAccesibilidad className={CLASE_BOTON_FRANJA} />
                        </div>
                        <PanelApariencia className={CLASE_BOTON_FRANJA} />
                    </div>
                </div>
            </div>

            {/* Encabezado: identidad, búsqueda predictiva y navegación principal */}
            <header className="sticky top-0 z-40 border-b bg-card/95 backdrop-blur supports-[backdrop-filter]:bg-card/85">
                <div className="mx-auto flex max-w-7xl items-center gap-3 px-4 py-2.5 sm:gap-4 sm:px-6">
                    <Link to="/" className="flex min-w-0 items-center gap-3 rounded-lg md:shrink-0">
                        <span
                            className="relative flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-primary text-sm font-black tracking-tight text-primary-foreground"
                            aria-hidden="true"
                        >
                            AE
                            <span className="absolute inset-x-0 bottom-0 h-1 bg-marca" />
                        </span>
                        <span className="min-w-0 leading-tight">
                            <span className="block text-[11px] font-semibold tracking-[0.16em] text-muted-foreground uppercase">Carrera de</span>
                            <span className="block truncate text-sm font-bold sm:text-base">{sitio.carrera}</span>
                        </span>
                    </Link>

                    <BuscadorPredictivo className="ml-auto hidden min-w-0 flex-1 md:block md:max-w-xl" />

                    <div className="ml-auto flex shrink-0 items-center gap-2 md:ml-0">
                        <button
                            type="button"
                            className="boton-icono md:hidden"
                            aria-expanded={buscadorMovil}
                            aria-controls="buscador-movil"
                            aria-label={buscadorMovil ? 'Cerrar la búsqueda' : 'Buscar en el sitio'}
                            onClick={() => {
                                setBuscadorMovil((v) => !v);
                                setMenuMovil(false);
                            }}
                        >
                            {buscadorMovil ? <X className="size-4" aria-hidden="true" /> : <Search className="size-4" aria-hidden="true" />}
                        </button>
                        <button
                            ref={botonMenu}
                            type="button"
                            className="boton-icono xl:hidden"
                            aria-expanded={menuMovil}
                            aria-controls="menu-movil"
                            aria-label={menuMovil ? 'Cerrar el menú' : 'Abrir el menú'}
                            onClick={() => {
                                setMenuMovil((v) => !v);
                                setBuscadorMovil(false);
                            }}
                        >
                            {menuMovil ? <X className="size-5" aria-hidden="true" /> : <Menu className="size-5" aria-hidden="true" />}
                        </button>
                    </div>
                </div>

                <NavegacionPrincipal grupos={grupos} activa={activa} />

                <div id="buscador-movil" hidden={!buscadorMovil} className="border-t bg-muted/60 px-4 py-3 sm:px-6 md:hidden">
                    {buscadorMovil && <BuscadorPredictivo autoFocus onNavegar={() => setBuscadorMovil(false)} />}
                </div>

                <NavegacionMovil id="menu-movil" grupos={grupos} activa={activa} abierto={menuMovil}>
                    <SesionMovil />
                </NavegacionMovil>
            </header>

            <main id="contenido" tabIndex={-1} className="flex-1 focus:outline-none">
                <Outlet />
            </main>

            <PieSitio />
        </div>
    );
}

/**
 * Anuncia a los lectores de pantalla el título de la nueva página después de cada navegación
 * (en una SPA el navegador no lo hace). Espera a que la página termine de fijar su título.
 */
function AnuncioNavegacion() {
    const { pathname } = useLocation();
    const [texto, setTexto] = useState('');
    const previa = useRef(pathname);

    useEffect(() => {
        if (previa.current === pathname) {
            return;
        }

        previa.current = pathname;
        const anunciar = () => setTexto(`Página: ${document.title}`);
        let temporizador = window.setTimeout(anunciar, 600);
        const titulo = document.querySelector('title');
        const observador = new MutationObserver(() => {
            window.clearTimeout(temporizador);
            temporizador = window.setTimeout(anunciar, 400);
        });

        if (titulo) {
            observador.observe(titulo, { childList: true, characterData: true, subtree: true });
        }

        const limite = window.setTimeout(() => observador.disconnect(), 4000);

        return () => {
            window.clearTimeout(temporizador);
            window.clearTimeout(limite);
            observador.disconnect();
        };
    }, [pathname]);

    return (
        <p className="sr-only" role="status" aria-live="polite" aria-atomic="true">
            {texto}
        </p>
    );
}

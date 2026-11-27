import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import { Link, Outlet } from 'react-router';
import { LogoCarrera } from '@/components/admin/Logo';
import ContadorVisitas from '@/components/ContadorVisitas';
import { BotonesAccesibilidad, PanelApariencia } from '@/components/PanelApariencia';
import { useSitio } from '@/contexts/SitioContext';

/*
 * Layout de autenticación (inicio de sesión y recuperación de contraseña): tarjeta centrada con la
 * identidad de la Carrera, enlace de regreso al sitio, apariencia/accesibilidad y pie con el contador de visitas.
 */
export default function AuthLayout() {
    const { sitio } = useSitio();

    return (
        <div className="flex min-h-svh flex-col bg-muted/60 text-foreground">
            <a
                href="#contenido"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[300] focus:rounded-lg focus:bg-primary focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-foreground"
            >
                Saltar al contenido
            </a>

            <header className="mx-auto flex w-full max-w-5xl items-center justify-between gap-3 px-4 py-4">
                <Link to="/" className="inline-flex items-center gap-2 rounded-lg text-sm font-medium text-muted-foreground hover:text-primary">
                    <ArrowLeft className="size-4" aria-hidden="true" /> Volver al sitio
                </Link>
                <div className="flex items-center gap-1.5">
                    <div className="hidden sm:block">
                        <BotonesAccesibilidad />
                    </div>
                    <PanelApariencia />
                </div>
            </header>

            <main id="contenido" tabIndex={-1} className="flex flex-1 items-center justify-center px-4 pb-10 outline-none">
                <div className="tarjeta grid w-full max-w-4xl overflow-hidden shadow-xl lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
                    <aside className="relative hidden flex-col justify-between gap-10 bg-franja p-10 text-franja-foreground lg:flex" aria-label="Identidad de la Carrera">
                        <span className="absolute inset-y-0 right-0 w-1.5 bg-marca" aria-hidden="true" />
                        <Link to="/" className="flex items-center gap-3 rounded-lg">
                            <LogoCarrera variante="claro" className="size-11 text-sm" />
                            <span className="leading-tight">
                                <span className="block text-xs tracking-[0.16em] uppercase opacity-75">Carrera de</span>
                                <span className="font-bold">{sitio.carrera}</span>
                            </span>
                        </Link>
                        <div>
                            <p className="text-3xl font-bold tracking-tight text-balance">Portal institucional</p>
                            <p className="mt-3 max-w-sm text-sm leading-relaxed opacity-80">
                                Acceso para administradores, encargados de módulo y graduados de la Carrera de {sitio.carrera}
                                {sitio.facultad ? ` · ${sitio.facultad}` : ''}.
                            </p>
                        </div>
                        <p className="text-xs opacity-70">{sitio.universidad}</p>
                    </aside>

                    <div className="min-w-0 p-6 sm:p-10">
                        <Link to="/" className="mb-8 flex items-center gap-3 rounded-lg lg:hidden">
                            <LogoCarrera />
                            <span className="leading-tight">
                                <span className="block text-[11px] tracking-[0.16em] text-muted-foreground uppercase">Carrera de</span>
                                <span className="text-sm font-bold">{sitio.carrera}</span>
                            </span>
                        </Link>
                        <Outlet />
                    </div>
                </div>
            </main>

            <footer className="border-t bg-background px-4 py-3 text-xs text-muted-foreground">
                <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-2">
                    <span>
                        © {new Date().getFullYear()} {sitio.universidad ?? sitio.carrera}
                    </span>
                    <ContadorVisitas />
                </div>
            </footer>
        </div>
    );
}

/** Título de las páginas de autenticación (franja de marca, h1 y descripción). */
export function EncabezadoAuth({ titulo, descripcion }: { titulo: string; descripcion?: ReactNode }) {
    return (
        <div className="mb-8">
            <span className="mb-4 block h-1 w-12 bg-marca" aria-hidden="true" />
            <h1 className="text-2xl font-bold tracking-tight">{titulo}</h1>
            {descripcion && <p className="mt-2 text-sm text-muted-foreground">{descripcion}</p>}
        </div>
    );
}

/** Aviso de estado (éxito o información) de las páginas de autenticación. */
export function AvisoAuth({ tono = 'exito', children }: { tono?: 'exito' | 'info'; children: ReactNode }) {
    return (
        <div
            role="status"
            className={
                tono === 'exito'
                    ? 'mb-6 rounded-lg border border-exito/40 bg-exito/10 px-4 py-3 text-sm text-foreground'
                    : 'mb-6 rounded-lg border border-primary/30 bg-primary/5 px-4 py-3 text-sm text-foreground'
            }
        >
            {children}
        </div>
    );
}

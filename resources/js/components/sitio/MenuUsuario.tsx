import { ChevronDown, GraduationCap, LayoutDashboard, LogIn, LogOut, UserCog, UserRound } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useCallback, useEffect, useId, useRef, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router';
import { toast } from 'sonner';
import { useAuth } from '@/contexts/AuthContext';
import { cn } from '@/lib/utils';
import type { Usuario } from '@/types';

/** Páginas que requieren sesión: al cerrar la sesión en ellas se vuelve al inicio. */
const PROTEGIDAS = ['/graduados/mi-ficha', '/cuenta'];

interface EnlaceCuenta {
    titulo: string;
    url: string;
    icono: LucideIcon;
}

/** Opciones de la cuenta según el rol del usuario (navegación dinámica). */
export function enlacesCuenta(usuario: Usuario): EnlaceCuenta[] {
    const enlaces: EnlaceCuenta[] = [];

    if (usuario.panel) {
        enlaces.push({ titulo: 'Panel de administración', url: '/admin', icono: LayoutDashboard });
    }

    if (usuario.es_graduado) {
        enlaces.push({ titulo: 'Mi ficha de graduado', url: '/graduados/mi-ficha', icono: GraduationCap });
    }

    enlaces.push({ titulo: 'Mi cuenta', url: '/cuenta', icono: UserCog });

    return enlaces;
}

/** Cierra la sesión (revoca el token en la API) y avisa al usuario. */
export function useCerrarSesion() {
    const { cerrarSesion } = useAuth();
    const navigate = useNavigate();
    const { pathname } = useLocation();

    return useCallback(async () => {
        // En una página que requiere sesión primero se termina de ir al inicio (la navegación es
        // asíncrona); si no, la guarda de sesión enviaría al formulario de ingreso.
        if (PROTEGIDAS.some((ruta) => pathname === ruta || pathname.startsWith(`${ruta}/`))) {
            await navigate('/', { replace: true });
        }

        await cerrarSesion();
        toast.success('Sesión cerrada correctamente.');
    }, [cerrarSesion, navigate, pathname]);
}

const primerNombre = (nombre: string) => nombre.trim().split(/\s+/)[0] ?? nombre;

const CLASE_BOTON =
    'inline-flex h-8 items-center gap-1.5 rounded-md border border-franja-foreground/30 px-2.5 font-medium whitespace-nowrap transition-colors hover:bg-franja-foreground/10';

/**
 * Acceso a la cuenta en la barra superior: "Ingresar" para visitantes; para usuarios con sesión,
 * su acceso principal (panel o ficha de graduado) y un menú con todas sus opciones y "Cerrar sesión".
 */
export function MenuUsuario() {
    const { usuario, cargando } = useAuth();
    const salir = useCerrarSesion();
    const { key } = useLocation();
    const id = useId();
    const [abierto, setAbierto] = useState(false);
    const [clave, setClave] = useState(key);
    const contenedor = useRef<HTMLDivElement>(null);
    const boton = useRef<HTMLButtonElement>(null);

    if (clave !== key) {
        setClave(key);
        setAbierto(false);
    }

    useEffect(() => {
        if (!abierto) {
            return;
        }

        const alClic = (e: MouseEvent) => {
            if (!contenedor.current?.contains(e.target as Node)) {
                setAbierto(false);
            }
        };
        const alTeclear = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                setAbierto(false);
                boton.current?.focus();
            }
        };

        document.addEventListener('mousedown', alClic);
        document.addEventListener('keydown', alTeclear);

        return () => {
            document.removeEventListener('mousedown', alClic);
            document.removeEventListener('keydown', alTeclear);
        };
    }, [abierto]);

    if (cargando) {
        return <span className="inline-block h-8 w-24 animate-pulse rounded-md bg-franja-foreground/15" aria-hidden="true" />;
    }

    if (!usuario) {
        return (
            <Link to="/login" className={CLASE_BOTON}>
                <LogIn className="size-3.5" aria-hidden="true" /> Ingresar
            </Link>
        );
    }

    const enlaces = enlacesCuenta(usuario);
    const principal = enlaces[0].url !== '/cuenta' ? enlaces[0] : null;
    const IconoPrincipal = principal?.icono;

    return (
        <div className="flex items-center gap-2">
            {principal && IconoPrincipal && (
                <Link to={principal.url} className={cn(CLASE_BOTON, 'hidden md:inline-flex')}>
                    <IconoPrincipal className="size-3.5" aria-hidden="true" /> {principal.titulo}
                </Link>
            )}
            <div ref={contenedor} className="relative">
                <button
                    ref={boton}
                    type="button"
                    className={CLASE_BOTON}
                    aria-expanded={abierto}
                    aria-controls={`${id}-cuenta`}
                    aria-label={`Cuenta de ${usuario.name}`}
                    title={`Cuenta de ${usuario.name}`}
                    onClick={() => setAbierto((v) => !v)}
                >
                    <UserRound className="size-3.5" aria-hidden="true" />
                    <span className="hidden max-w-28 truncate sm:inline">{primerNombre(usuario.name)}</span>
                    <ChevronDown className={cn('size-3 transition-transform', abierto && 'rotate-180')} aria-hidden="true" />
                </button>
                <div
                    id={`${id}-cuenta`}
                    hidden={!abierto}
                    className="absolute top-full right-0 z-[60] mt-2 w-64 rounded-lg border border-t-4 border-t-marca bg-popover p-2 text-sm text-popover-foreground shadow-xl"
                >
                    <div className="border-b px-2 pt-1 pb-2">
                        <p className="truncate font-semibold">{usuario.name}</p>
                        <p className="truncate text-xs text-muted-foreground">{usuario.email}</p>
                        {usuario.rol && <p className="insignia mt-1.5 rounded-lg">{usuario.rol}</p>}
                    </div>
                    <ul className="mt-1">
                        {enlaces.map((enlace) => {
                            const IconoEnlace = enlace.icono;

                            return (
                                <li key={enlace.url}>
                                    <Link to={enlace.url} className="flex items-center gap-2 rounded-lg px-2 py-2 hover:bg-accent focus-visible:bg-accent">
                                        <IconoEnlace className="size-4 text-primary" aria-hidden="true" /> {enlace.titulo}
                                    </Link>
                                </li>
                            );
                        })}
                        <li className="mt-1 border-t pt-1">
                            <button
                                type="button"
                                className="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-destructive hover:bg-destructive/10"
                                onClick={() => {
                                    setAbierto(false);
                                    void salir();
                                }}
                            >
                                <LogOut className="size-4" aria-hidden="true" /> Cerrar sesión
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    );
}

/** Opciones de la cuenta al final del menú móvil. */
export function SesionMovil() {
    const { usuario, cargando } = useAuth();
    const salir = useCerrarSesion();

    if (cargando) {
        return null;
    }

    return (
        <div className="bg-muted/40 px-4 py-4 sm:px-6">
            <p className="mb-2 text-xs font-bold tracking-wider text-muted-foreground uppercase">{usuario ? `Sesión de ${usuario.name}` : 'Acceso al sistema'}</p>
            {usuario ? (
                <ul className="grid gap-1 text-sm">
                    {enlacesCuenta(usuario).map((enlace) => {
                        const IconoEnlace = enlace.icono;

                        return (
                            <li key={enlace.url}>
                                <Link to={enlace.url} className="flex items-center gap-2 rounded-lg px-2 py-2 font-medium hover:bg-accent">
                                    <IconoEnlace className="size-4 text-primary" aria-hidden="true" /> {enlace.titulo}
                                </Link>
                            </li>
                        );
                    })}
                    <li>
                        <button type="button" className="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left font-medium text-destructive hover:bg-destructive/10" onClick={() => void salir()}>
                            <LogOut className="size-4" aria-hidden="true" /> Cerrar sesión
                        </button>
                    </li>
                </ul>
            ) : (
                <Link to="/login" className="boton w-full">
                    <LogIn className="size-4" aria-hidden="true" /> Ingresar
                </Link>
            )}
        </div>
    );
}

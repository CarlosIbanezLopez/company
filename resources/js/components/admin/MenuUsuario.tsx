import { ChevronDown, Globe, LoaderCircle, LogOut, UserCog } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import { Link } from 'react-router';
import { useSalir } from '@/components/admin/useSalir';
import { useAuth } from '@/contexts/AuthContext';
import { urlSitio } from '@/lib/config';
import { cn } from '@/lib/utils';

/** Iniciales para el avatar: "Ana María Pérez" -> "AM". */
export function iniciales(nombre: string | null | undefined): string {
    const partes = (nombre ?? '').trim().split(/\s+/).filter(Boolean);

    return (partes.length > 1 ? `${partes[0][0]}${partes[1][0]}` : (partes[0]?.slice(0, 2) ?? '?')).toUpperCase();
}

export function Avatar({ nombre, className }: { nombre: string | null | undefined; className?: string }) {
    return (
        <span aria-hidden="true" className={cn('flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground', className)}>
            {iniciales(nombre)}
        </span>
    );
}

const claseOpcion = 'flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-accent hover:text-accent-foreground';

/** Usuario de la barra superior: nombre y rol, con menú "Mi cuenta", "Ver sitio" y "Cerrar sesión". */
export function MenuUsuario() {
    const { usuario } = useAuth();
    const salir = useSalir();
    const [abierto, setAbierto] = useState(false);
    const [saliendo, setSaliendo] = useState(false);
    const contenedor = useRef<HTMLDivElement>(null);
    const boton = useRef<HTMLButtonElement>(null);
    const id = useId();

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

    if (!usuario) {
        return null;
    }

    const cerrarSesion = async () => {
        setSaliendo(true);
        setAbierto(false);
        await salir();
    };

    return (
        <div ref={contenedor} className="relative">
            <button
                ref={boton}
                type="button"
                aria-expanded={abierto}
                aria-controls={`${id}-menu`}
                aria-label={`Cuenta de ${usuario.name}${usuario.rol ? ` (${usuario.rol})` : ''}`}
                onClick={() => setAbierto((v) => !v)}
                className="flex items-center gap-2 rounded-lg border border-transparent px-1.5 py-1 transition-colors hover:border-input"
            >
                <Avatar nombre={usuario.name} />
                <span className="hidden min-w-0 text-left leading-tight md:block">
                    <span className="block max-w-44 truncate text-sm font-semibold">{usuario.name}</span>
                    <span className="block max-w-44 truncate text-xs text-muted-foreground">{usuario.rol ?? 'Sin rol asignado'}</span>
                </span>
                {saliendo ? (
                    <LoaderCircle className="size-4 animate-spin text-muted-foreground" aria-hidden="true" />
                ) : (
                    <ChevronDown className={cn('size-4 text-muted-foreground transition-transform', abierto && 'rotate-180')} aria-hidden="true" />
                )}
            </button>

            {abierto && (
                <div id={`${id}-menu`} className="absolute top-full right-0 z-50 mt-2 w-72 max-w-[calc(100vw-1.5rem)] rounded-lg border border-t-4 border-t-marca bg-popover p-1 text-popover-foreground shadow-xl">
                    <div className="flex items-center gap-3 px-3 py-3">
                        <Avatar nombre={usuario.name} className="size-10 text-sm" />
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold">{usuario.name}</p>
                            <p className="truncate text-xs text-muted-foreground">{usuario.email}</p>
                            {usuario.rol && <span className="insignia mt-1 rounded-lg bg-secondary text-secondary-foreground">{usuario.rol}</span>}
                        </div>
                    </div>
                    <div className="my-1 border-t" role="separator" />
                    <Link to="/admin/cuenta" className={claseOpcion} onClick={() => setAbierto(false)}>
                        <UserCog className="size-4 text-muted-foreground" aria-hidden="true" /> Mi cuenta
                    </Link>
                    <a href={urlSitio('/')} target="_blank" rel="noopener noreferrer" className={claseOpcion} onClick={() => setAbierto(false)}>
                        <Globe className="size-4 text-muted-foreground" aria-hidden="true" /> Ver sitio público
                        <span className="sr-only"> (se abre en una pestaña nueva)</span>
                    </a>
                    <div className="my-1 border-t" role="separator" />
                    <button type="button" className={cn(claseOpcion, 'text-destructive hover:text-destructive')} onClick={() => void cerrarSesion()} disabled={saliendo}>
                        <LogOut className="size-4" aria-hidden="true" /> Cerrar sesión
                    </button>
                </div>
            )}
        </div>
    );
}

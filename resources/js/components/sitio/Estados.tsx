import type { ReactNode } from 'react';
import { Cargando, ErrorCarga, Esqueleto } from '@/components/ui/Cargando';
import type { EstadoApi } from '@/hooks/useApi';
import { cn } from '@/lib/utils';

interface PropsBloque<T> {
    consulta: EstadoApi<T>;
    /** Contenido con los datos ya cargados (no usar hooks dentro de esta función). */
    children: (datos: T) => ReactNode;
    textoCarga?: string;
    className?: string;
}

/**
 * Estados de una consulta a la API dentro de una página: carga, error (con reintento; 404 → no encontrado)
 * y contenido. Mientras se actualiza (p. ej. al cambiar un filtro) mantiene el contenido anterior atenuado.
 */
export function BloqueDatos<T>({ consulta, children, textoCarga, className }: PropsBloque<T>) {
    if (consulta.error) {
        return <ErrorCarga mensaje={consulta.error} estado={consulta.estado} onReintentar={consulta.recargar} />;
    }

    if (!consulta.datos) {
        return <Cargando texto={textoCarga} />;
    }

    return (
        <div aria-busy={consulta.cargando || undefined} className={cn('transition-opacity', consulta.cargando && 'opacity-60', className)}>
            {children(consulta.datos)}
        </div>
    );
}

/** Marcador de carga de una página de detalle (la cabecera depende de los datos). */
export function CargandoDetalle({ texto = 'Cargando contenido…' }: { texto?: string }) {
    const barra = 'bg-primary-foreground/20 dark:bg-muted';

    return (
        <div>
            <div className="border-b bg-primary dark:bg-card" aria-hidden="true">
                <div className="mx-auto max-w-7xl space-y-4 px-4 py-10 sm:px-6 md:py-14">
                    <Esqueleto className={cn('h-3 w-40', barra)} />
                    <Esqueleto className={cn('h-9 w-2/3', barra)} />
                    <Esqueleto className={cn('h-4 w-1/2', barra)} />
                </div>
            </div>
            <Cargando texto={texto} />
        </div>
    );
}

/** Botón de filtro (categorías, tipos, gestiones): marca el filtro activo con aria-pressed. */
export function BotonFiltro({ activo, onClick, children, className }: { activo: boolean; onClick: () => void; children: ReactNode; className?: string }) {
    return (
        <button
            type="button"
            aria-pressed={activo}
            onClick={onClick}
            className={cn(
                'rounded-lg border px-3 py-1.5 text-sm transition-colors',
                activo ? 'border-primary bg-primary font-medium text-primary-foreground' : 'bg-card hover:border-primary hover:text-primary',
                className,
            )}
        >
            {children}
        </button>
    );
}

/** Mensaje de lista vacía con el estilo de tarjeta del sitio. */
export function SinResultados({ children, className }: { children: ReactNode; className?: string }) {
    return <p className={cn('rounded-lg border bg-card p-8 text-center text-muted-foreground', className)}>{children}</p>;
}

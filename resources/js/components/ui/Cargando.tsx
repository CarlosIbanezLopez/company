import { CircleAlert, LoaderCircle, Lock, RotateCcw, SearchX } from 'lucide-react';
import { Link } from 'react-router';
import { cn } from '@/lib/utils';

/** Indicador de carga accesible. */
export function Cargando({ texto = 'Cargando…', className }: { texto?: string; className?: string }) {
    return (
        <div role="status" aria-live="polite" className={cn('flex items-center justify-center gap-3 py-16 text-sm text-muted-foreground', className)}>
            <LoaderCircle className="size-5 animate-spin text-primary" aria-hidden="true" />
            <span>{texto}</span>
        </div>
    );
}

/** Bloques grises mientras llega el contenido. */
export function Esqueleto({ className }: { className?: string }) {
    return <div aria-hidden="true" className={cn('animate-pulse rounded-lg bg-muted', className)} />;
}

interface PropsErrorCarga {
    mensaje?: string | null;
    /** Código HTTP: 404 y 403 muestran un mensaje específico. */
    estado?: number | null;
    onReintentar?: () => void;
    className?: string;
}

/** Mensaje de error al cargar datos de la API, con opción de reintentar. */
export function ErrorCarga({ mensaje, estado, onReintentar, className }: PropsErrorCarga) {
    if (estado === 404) {
        return <NoEncontradoBloque className={className} />;
    }

    const prohibido = estado === 403;
    const IconoError = prohibido ? Lock : CircleAlert;

    return (
        <div role="alert" className={cn('mx-auto flex max-w-xl flex-col items-center gap-4 px-4 py-16 text-center', className)}>
            <IconoError className={cn('size-10', prohibido ? 'text-aviso' : 'text-destructive')} aria-hidden="true" />
            <div>
                <p className="text-lg font-semibold">{prohibido ? 'Acceso restringido' : 'No se pudo cargar la información'}</p>
                <p className="mt-1 text-sm text-muted-foreground">
                    {mensaje ?? (prohibido ? 'No tiene permiso para ver esta sección.' : 'Intente nuevamente en unos momentos.')}
                </p>
            </div>
            {onReintentar && !prohibido && (
                <button type="button" className="boton-borde" onClick={onReintentar}>
                    <RotateCcw className="size-4" aria-hidden="true" /> Reintentar
                </button>
            )}
        </div>
    );
}

/** Bloque "no encontrado" reutilizable dentro de cualquier layout. */
export function NoEncontradoBloque({ className, titulo = 'Página no encontrada' }: { className?: string; titulo?: string }) {
    return (
        <div className={cn('mx-auto flex max-w-xl flex-col items-center gap-4 px-4 py-20 text-center', className)}>
            <SearchX className="size-12 text-muted-foreground" aria-hidden="true" />
            <div>
                <p className="text-2xl font-bold">{titulo}</p>
                <p className="mt-2 text-sm text-muted-foreground">La dirección que busca no existe o fue retirada.</p>
            </div>
            <Link to="/" className="boton">
                Volver al inicio
            </Link>
        </div>
    );
}

/** Estado vacío (sin resultados). */
export function Vacio({ texto = 'No hay información para mostrar.', className }: { texto?: string; className?: string }) {
    return <p className={cn('border border-dashed px-4 py-10 text-center text-sm text-muted-foreground rounded-lg', className)}>{texto}</p>;
}

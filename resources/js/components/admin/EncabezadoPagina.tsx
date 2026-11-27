import { ArrowLeft } from 'lucide-react';
import { useId } from 'react';
import type { ReactNode } from 'react';
import { Link } from 'react-router';
import { cn } from '@/lib/utils';

interface PropsEncabezado {
    titulo: ReactNode;
    descripcion?: ReactNode;
    /** Botones a la derecha del título (crear, exportar...). */
    acciones?: ReactNode;
    /** Enlace "volver" sobre el título. */
    volver?: { to: string; texto: string } | null;
    className?: string;
}

/** Encabezado de las páginas del panel: franja de marca, título (h1), descripción y acciones. */
export function EncabezadoPagina({ titulo, descripcion, acciones, volver, className }: PropsEncabezado) {
    return (
        <header className={cn('flex flex-wrap items-end justify-between gap-4 border-b pb-4', className)}>
            <div className="min-w-0">
                {volver && (
                    <Link to={volver.to} className="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary">
                        <ArrowLeft className="size-4" aria-hidden="true" /> {volver.texto}
                    </Link>
                )}
                <span className="mb-2 block h-1 w-10 bg-marca" aria-hidden="true" />
                <h1 className="text-2xl font-bold tracking-tight text-balance">{titulo}</h1>
                {descripcion && <p className="mt-1 text-sm text-muted-foreground">{descripcion}</p>}
            </div>
            {acciones && <div className="flex flex-wrap items-center gap-2 print:hidden">{acciones}</div>}
        </header>
    );
}

interface PropsSeccion {
    titulo: ReactNode;
    /** Texto secundario bajo el título. */
    subtitulo?: ReactNode;
    /** Enlace o botón a la derecha del título. */
    accion?: ReactNode;
    children: ReactNode;
    className?: string;
    /** Clases del cuerpo (por defecto con relleno). */
    cuerpo?: string;
}

/** Tarjeta con encabezado (h2) para agrupar un gráfico, una tabla o una lista. */
export function SeccionPanel({ titulo, subtitulo, accion, children, className, cuerpo = 'p-5' }: PropsSeccion) {
    const id = useId();

    return (
        <section aria-labelledby={id} className={cn('tarjeta flex min-w-0 flex-col', className)}>
            <header className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-b px-5 py-3">
                <div className="min-w-0">
                    <h2 id={id} className="font-bold">
                        {titulo}
                    </h2>
                    {subtitulo && <p className="text-xs text-muted-foreground">{subtitulo}</p>}
                </div>
                {accion && <div className="shrink-0 print:hidden">{accion}</div>}
            </header>
            <div className={cn('min-w-0 flex-1', cuerpo)}>{children}</div>
        </section>
    );
}

/** Enlace "Ver todo →" de las tarjetas del tablero. */
export function EnlaceSeccion({ to, children }: { to: string; children: ReactNode }) {
    return (
        <Link to={to} className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
            {children}
            <span aria-hidden="true">→</span>
        </Link>
    );
}

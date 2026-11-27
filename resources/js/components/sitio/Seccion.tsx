import { useId } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface PropsSeccion {
    titulo?: string;
    subtitulo?: string;
    /** Acciones a la derecha del título (p. ej. "Ver todas", buscador). */
    acciones?: ReactNode;
    className?: string;
    children?: ReactNode;
}

/** Sección de contenido con ancho máximo del sitio, antetítulo en color de marca y título (h2). */
export function Seccion({ titulo, subtitulo, acciones, className, children }: PropsSeccion) {
    const id = useId();

    return (
        <section className={cn('mx-auto max-w-7xl px-4 py-12 sm:px-6', className)} aria-labelledby={titulo ? id : undefined}>
            {titulo && (
                <header className="mb-8 flex flex-wrap items-end justify-between gap-4 border-b pb-4">
                    <div>
                        {subtitulo && <p className="text-xs font-semibold tracking-[0.18em] text-marca uppercase">{subtitulo}</p>}
                        <h2 id={id} className="mt-1 text-2xl font-bold tracking-tight md:text-3xl">
                            {titulo}
                        </h2>
                    </div>
                    {acciones}
                </header>
            )}
            {children}
        </section>
    );
}

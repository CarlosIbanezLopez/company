import { ChevronLeft, ChevronRight, ImageOff, X } from 'lucide-react';
import { useEffect, useId, useRef } from 'react';
import { createPortal } from 'react-dom';
import type { Foto } from '@/components/sitio/tipos';

interface PropsVisor {
    fotos: Foto[];
    indice: number;
    titulo: string;
    onCambiar: (indice: number) => void;
    onCerrar: () => void;
}

const CLASE_BOTON =
    'inline-flex size-11 items-center justify-center rounded-lg border border-franja-foreground/30 text-franja-foreground transition-colors hover:bg-franja-foreground/10';

/**
 * Visor de fotografías a pantalla completa (diálogo modal): flechas del teclado para recorrer,
 * Escape para cerrar, foco atrapado dentro del visor y devuelto a la miniatura al cerrar.
 */
export function VisorFotos({ fotos, indice, titulo, onCambiar, onCerrar }: PropsVisor) {
    const id = useId();
    const caja = useRef<HTMLDivElement>(null);
    const total = fotos.length;
    const foto = fotos[indice];

    const mover = (paso: number) => onCambiar((indice + paso + total) % total);
    const acciones = useRef({ mover, onCerrar });
    acciones.current = { mover, onCerrar };

    useEffect(() => {
        const previo = document.activeElement as HTMLElement | null;
        caja.current?.querySelector<HTMLElement>('[data-cerrar]')?.focus();
        const desbordamiento = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const alTeclear = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                acciones.current.onCerrar();
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                acciones.current.mover(1);
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                acciones.current.mover(-1);
            } else if (e.key === 'Tab') {
                const enfocables = Array.from(caja.current?.querySelectorAll<HTMLElement>('button') ?? []);

                if (enfocables.length === 0) {
                    return;
                }

                const primero = enfocables[0];
                const ultimo = enfocables[enfocables.length - 1];

                if (e.shiftKey && document.activeElement === primero) {
                    e.preventDefault();
                    ultimo.focus();
                } else if (!e.shiftKey && document.activeElement === ultimo) {
                    e.preventDefault();
                    primero.focus();
                }
            }
        };

        document.addEventListener('keydown', alTeclear);

        return () => {
            document.removeEventListener('keydown', alTeclear);
            document.body.style.overflow = desbordamiento;
            previo?.focus?.();
        };
    }, []);

    if (!foto) {
        return null;
    }

    const descripcion = foto.descripcion?.trim() || null;

    return createPortal(
        <div
            ref={caja}
            role="dialog"
            aria-modal="true"
            aria-labelledby={`${id}-titulo`}
            className="fixed inset-0 z-[100] flex items-center justify-center bg-franja/95 p-4 text-franja-foreground"
            onMouseDown={(e) => e.target === e.currentTarget && onCerrar()}
        >
            <h2 id={`${id}-titulo`} className="sr-only">
                {titulo}: fotografía {indice + 1} de {total}
            </h2>

            <button type="button" data-cerrar className={`${CLASE_BOTON} absolute top-4 right-4`} aria-label="Cerrar el visor" onClick={onCerrar}>
                <X className="size-5" aria-hidden="true" />
            </button>

            {total > 1 && (
                <button type="button" className={`${CLASE_BOTON} absolute left-2 z-10 sm:left-4`} aria-label="Fotografía anterior" onClick={() => mover(-1)}>
                    <ChevronLeft className="size-5" aria-hidden="true" />
                </button>
            )}

            <figure className="flex max-h-full max-w-5xl flex-col items-center px-12">
                {foto.imagen_url ? (
                    <img src={foto.imagen_url} alt={descripcion ?? `${titulo}, fotografía ${indice + 1}`} className="max-h-[80vh] w-auto rounded-lg object-contain" />
                ) : (
                    <div className="flex size-64 items-center justify-center rounded-lg border border-franja-foreground/30" role="img" aria-label="Fotografía no disponible">
                        <ImageOff className="size-10 opacity-60" aria-hidden="true" />
                    </div>
                )}
                <figcaption className="mt-3 text-center text-sm" aria-live="polite">
                    {descripcion ? `${descripcion} · ` : ''}
                    {indice + 1} / {total}
                </figcaption>
            </figure>

            {total > 1 && (
                <button type="button" className={`${CLASE_BOTON} absolute right-2 z-10 sm:right-4`} aria-label="Fotografía siguiente" onClick={() => mover(1)}>
                    <ChevronRight className="size-5" aria-hidden="true" />
                </button>
            )}
        </div>,
        document.body,
    );
}

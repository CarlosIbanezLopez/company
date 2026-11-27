import { X } from 'lucide-react';
import { useEffect, useId, useRef } from 'react';
import type { ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { cn } from '@/lib/utils';

interface PropsModal {
    abierto: boolean;
    onCerrar: () => void;
    titulo: string;
    descripcion?: ReactNode;
    children?: ReactNode;
    /** Botones del pie (Cancelar / Confirmar). */
    pie?: ReactNode;
    className?: string;
}

/** Ventana modal accesible: foco atrapado, Escape para cerrar, clic fuera para cerrar. */
export function Modal({ abierto, onCerrar, titulo, descripcion, children, pie, className }: PropsModal) {
    const id = useId();
    const caja = useRef<HTMLDivElement>(null);
    const previo = useRef<HTMLElement | null>(null);

    useEffect(() => {
        if (!abierto) {
            return;
        }

        previo.current = document.activeElement as HTMLElement | null;
        const enfocables = () =>
            Array.from(
                caja.current?.querySelectorAll<HTMLElement>('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])') ?? [],
            ).filter((el) => !el.hasAttribute('disabled'));

        requestAnimationFrame(() => (enfocables()[0] ?? caja.current)?.focus());

        const alTeclear = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                e.stopPropagation();
                onCerrar();
            } else if (e.key === 'Tab') {
                const lista = enfocables();

                if (lista.length === 0) {
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

        document.addEventListener('keydown', alTeclear);
        const desbordamiento = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', alTeclear);
            document.body.style.overflow = desbordamiento;
            previo.current?.focus?.();
        };
    }, [abierto, onCerrar]);

    if (!abierto) {
        return null;
    }

    return createPortal(
        <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" onMouseDown={(e) => e.target === e.currentTarget && onCerrar()}>
            <div
                ref={caja}
                role="dialog"
                aria-modal="true"
                aria-labelledby={`${id}-titulo`}
                aria-describedby={descripcion ? `${id}-desc` : undefined}
                tabIndex={-1}
                className={cn('tarjeta w-full max-w-lg border-t-4 border-t-marca p-6 shadow-xl outline-none', className)}
            >
                <div className="flex items-start justify-between gap-4">
                    <h2 id={`${id}-titulo`} className="text-lg font-bold">
                        {titulo}
                    </h2>
                    <button type="button" className="-mt-1 -mr-2 inline-flex size-8 items-center justify-center rounded-md hover:bg-muted" onClick={onCerrar} aria-label="Cerrar">
                        <X className="size-4" aria-hidden="true" />
                    </button>
                </div>
                {descripcion && (
                    <div id={`${id}-desc`} className="mt-2 text-sm text-muted-foreground">
                        {descripcion}
                    </div>
                )}
                {children && <div className="mt-4">{children}</div>}
                {pie && <div className="mt-6 flex flex-wrap justify-end gap-2">{pie}</div>}
            </div>
        </div>,
        document.body,
    );
}

interface PropsConfirmar {
    abierto: boolean;
    titulo: string;
    mensaje: ReactNode;
    textoConfirmar?: string;
    peligro?: boolean;
    procesando?: boolean;
    onConfirmar: () => void;
    onCancelar: () => void;
}

/** Diálogo de confirmación (p. ej. antes de eliminar). */
export function Confirmar({ abierto, titulo, mensaje, textoConfirmar = 'Confirmar', peligro = true, procesando = false, onConfirmar, onCancelar }: PropsConfirmar) {
    return (
        <Modal
            abierto={abierto}
            onCerrar={onCancelar}
            titulo={titulo}
            descripcion={mensaje}
            pie={
                <>
                    <button type="button" className="boton-borde" onClick={onCancelar} disabled={procesando}>
                        Cancelar
                    </button>
                    <button type="button" className={peligro ? 'boton-peligro' : 'boton'} onClick={onConfirmar} disabled={procesando}>
                        {procesando ? 'Procesando…' : textoConfirmar}
                    </button>
                </>
            }
        />
    );
}

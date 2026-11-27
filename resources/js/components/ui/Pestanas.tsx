import { useId } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export interface Pestana {
    id: string;
    titulo: ReactNode;
}

interface PropsPestanas {
    pestanas: Pestana[];
    activa: string;
    onCambiar: (id: string) => void;
    children: ReactNode;
    className?: string;
}

/** Pestañas accesibles (role="tablist", navegación con flechas). El contenido es el de la pestaña activa. */
export function Pestanas({ pestanas, activa, onCambiar, children, className }: PropsPestanas) {
    const id = useId();

    const alTeclear = (e: React.KeyboardEvent, indice: number) => {
        if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') {
            return;
        }

        e.preventDefault();
        const siguiente = (indice + (e.key === 'ArrowRight' ? 1 : -1) + pestanas.length) % pestanas.length;
        onCambiar(pestanas[siguiente].id);
        document.getElementById(`${id}-${pestanas[siguiente].id}`)?.focus();
    };

    return (
        <div className={className}>
            <div role="tablist" className="flex flex-wrap gap-1 border-b">
                {pestanas.map((p, i) => (
                    <button
                        key={p.id}
                        id={`${id}-${p.id}`}
                        type="button"
                        role="tab"
                        aria-selected={activa === p.id}
                        aria-controls={`${id}-${p.id}-panel`}
                        tabIndex={activa === p.id ? 0 : -1}
                        onClick={() => onCambiar(p.id)}
                        onKeyDown={(e) => alTeclear(e, i)}
                        className={cn(
                            '-mb-px inline-flex items-center gap-2 rounded-t-lg border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
                            activa === p.id ? 'border-marca text-primary' : 'border-transparent text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {p.titulo}
                    </button>
                ))}
            </div>
            <div role="tabpanel" id={`${id}-${activa}-panel`} aria-labelledby={`${id}-${activa}`} className="pt-5">
                {children}
            </div>
        </div>
    );
}

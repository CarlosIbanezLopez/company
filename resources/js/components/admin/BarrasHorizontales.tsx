import type { ReactNode } from 'react';
import { numero } from '@/lib/formato';
import { cn } from '@/lib/utils';

export interface DatoBarra {
    etiqueta: string;
    total: number;
    /** Texto completo para el título emergente (si la etiqueta se recorta). */
    titulo?: string;
    /** Elemento antes de la etiqueta (p. ej. el método HTTP). */
    prefijo?: ReactNode;
    /** Texto secundario a la derecha del valor (p. ej. la última fecha). */
    detalle?: ReactNode;
}

interface PropsBarras {
    datos: DatoBarra[];
    vacio?: string;
    /** Color de las barras (token): por defecto var(--chart-1). */
    color?: string;
    /** Etiquetas en letra monoespaciada (rutas, IP, nombres de recursos). */
    monoespaciado?: boolean;
    className?: string;
}

/**
 * Ranking de una sola serie: etiqueta, valor en texto y barra proporcional (port de BarrasHorizontales.vue).
 * Los valores siempre se leen como texto, así que no depende del color ni de un tooltip.
 */
export function BarrasHorizontales({ datos, vacio = 'Sin datos en el período.', color = 'var(--chart-1)', monoespaciado = false, className }: PropsBarras) {
    if (!datos.length) {
        return <p className="text-sm text-muted-foreground">{vacio}</p>;
    }

    const maximo = Math.max(1, ...datos.map((d) => Number(d.total) || 0));

    return (
        <ul className={cn('space-y-3', className)}>
            {datos.map((d, i) => {
                const total = Number(d.total) || 0;

                return (
                    <li key={`${d.etiqueta}-${i}`} className="group" title={`${d.titulo ?? d.etiqueta}: ${numero(total)}`}>
                        <div className="mb-1 flex items-baseline justify-between gap-3 text-sm">
                            <span className="flex min-w-0 items-baseline gap-2">
                                {d.prefijo}
                                <span className={cn('truncate', monoespaciado && 'font-mono text-xs')}>{d.etiqueta}</span>
                            </span>
                            <span className="flex shrink-0 items-baseline gap-2">
                                {d.detalle && <span className="text-xs text-muted-foreground">{d.detalle}</span>}
                                <span className="font-semibold tabular-nums">{numero(total)}</span>
                            </span>
                        </div>
                        <div className="h-2 overflow-hidden rounded-full bg-muted" aria-hidden="true">
                            <div
                                className="h-full rounded-full transition-opacity group-hover:opacity-80"
                                style={{ width: `${total > 0 ? Math.max(1.5, (total / maximo) * 100) : 0}%`, backgroundColor: color }}
                            />
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}

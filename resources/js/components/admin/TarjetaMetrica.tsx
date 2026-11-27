import type { ReactNode } from 'react';
import Icono from '@/components/Icono';
import { Esqueleto } from '@/components/ui/Cargando';
import { numero } from '@/lib/formato';
import { cn } from '@/lib/utils';

type TonoMetrica = 'normal' | 'exito' | 'peligro' | 'aviso' | 'marca';

const TONO_ICONO: Record<TonoMetrica, string> = {
    normal: 'text-muted-foreground',
    exito: 'text-exito',
    peligro: 'text-destructive',
    aviso: 'text-aviso',
    marca: 'text-marca',
};

interface PropsTarjeta {
    titulo: string;
    valor: number | string | null | undefined;
    /** Nombre de ícono lucide en kebab-case (como en la base de datos) o un nodo. */
    icono?: string | ReactNode;
    detalle?: ReactNode;
    tono?: TonoMetrica;
    className?: string;
}

/** Indicador del tablero: etiqueta, valor destacado e ícono. */
export function TarjetaMetrica({ titulo, valor, icono, detalle, tono = 'normal', className }: PropsTarjeta) {
    return (
        <div className={cn('min-w-0 bg-card p-5', className)}>
            <div className="flex items-start justify-between gap-3">
                <p className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">{titulo}</p>
                {icono && (
                    <span className={cn('shrink-0', TONO_ICONO[tono])}>
                        {typeof icono === 'string' ? <Icono nombre={icono} className="size-4" /> : icono}
                    </span>
                )}
            </div>
            <p className="mt-3 text-3xl font-black tracking-tight">{typeof valor === 'number' ? numero(valor) : (valor ?? '—')}</p>
            {detalle && <p className="mt-1 text-xs text-muted-foreground">{detalle}</p>}
        </div>
    );
}

/** Fila de indicadores separados por líneas finas (como el panel original). */
export function GrillaMetricas({ children, className }: { children: ReactNode; className?: string }) {
    return <div className={cn('grid gap-px overflow-hidden rounded-lg border bg-border', className)}>{children}</div>;
}

/** Marcadores de posición mientras cargan los indicadores. */
export function EsqueletoMetricas({ cantidad = 4, className }: { cantidad?: number; className?: string }) {
    return (
        <GrillaMetricas className={className}>
            {Array.from({ length: cantidad }, (_, i) => (
                <div key={i} className="bg-card p-5">
                    <Esqueleto className="h-3 w-24" />
                    <Esqueleto className="mt-4 h-8 w-16" />
                </div>
            ))}
        </GrillaMetricas>
    );
}

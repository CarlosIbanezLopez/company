import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useSearchParams } from 'react-router';
import { numero } from '@/lib/formato';
import { cn } from '@/lib/utils';
import type { Paginado } from '@/types';

interface PropsPaginacion {
    datos: Pick<Paginado<unknown>, 'current_page' | 'last_page' | 'total' | 'from' | 'to'>;
    /** Si se indica, se usa en lugar de cambiar ?page= en la URL. */
    onCambiar?: (pagina: number) => void;
    className?: string;
}

/** Paginación de resultados de la API. Por defecto actualiza el parámetro ?page= de la URL. */
export function Paginacion({ datos, onCambiar, className }: PropsPaginacion) {
    const [params, setParams] = useSearchParams();

    if (datos.last_page <= 1) {
        return datos.total > 0 ? <p className={cn('text-sm text-muted-foreground', className)}>{resumen(datos)}</p> : null;
    }

    const ir = (pagina: number) => {
        if (onCambiar) {
            onCambiar(pagina);
        } else {
            const nuevos = new URLSearchParams(params);
            nuevos.set('page', String(pagina));
            setParams(nuevos);
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const actual = datos.current_page;
    const paginas = rango(actual, datos.last_page);

    return (
        <nav className={cn('flex flex-wrap items-center justify-between gap-3', className)} aria-label="Paginación">
            <p className="text-sm text-muted-foreground">{resumen(datos)}</p>
            <ul className="flex items-center gap-1">
                <li>
                    <button type="button" className="boton-icono size-9" onClick={() => ir(actual - 1)} disabled={actual <= 1} aria-label="Página anterior">
                        <ChevronLeft className="size-4" aria-hidden="true" />
                    </button>
                </li>
                {paginas.map((p, i) =>
                    p === null ? (
                        <li key={`s${i}`} className="px-1 text-muted-foreground" aria-hidden="true">
                            …
                        </li>
                    ) : (
                        <li key={p}>
                            <button
                                type="button"
                                onClick={() => ir(p)}
                                aria-current={p === actual ? 'page' : undefined}
                                className={cn(
                                    'inline-flex h-9 min-w-9 items-center justify-center rounded-lg border px-2 text-sm',
                                    p === actual ? 'border-primary bg-primary font-semibold text-primary-foreground' : 'bg-card hover:border-primary',
                                )}
                            >
                                {p}
                            </button>
                        </li>
                    ),
                )}
                <li>
                    <button type="button" className="boton-icono size-9" onClick={() => ir(actual + 1)} disabled={actual >= datos.last_page} aria-label="Página siguiente">
                        <ChevronRight className="size-4" aria-hidden="true" />
                    </button>
                </li>
            </ul>
        </nav>
    );
}

function resumen(d: PropsPaginacion['datos']): string {
    return d.from ? `Mostrando ${numero(d.from)}–${numero(d.to ?? d.from)} de ${numero(d.total)}` : `${numero(d.total)} registros`;
}

function rango(actual: number, ultima: number): (number | null)[] {
    const resultado: (number | null)[] = [];

    for (let p = 1; p <= ultima; p++) {
        if (p === 1 || p === ultima || Math.abs(p - actual) <= 1) {
            resultado.push(p);
        } else if (resultado[resultado.length - 1] !== null) {
            resultado.push(null);
        }
    }

    return resultado;
}

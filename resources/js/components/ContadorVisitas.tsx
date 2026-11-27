import { Eye } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useLocation } from 'react-router';
import { api } from '@/lib/api';
import { numero } from '@/lib/formato';
import { cn } from '@/lib/utils';

/**
 * Contador de visitas persistente de la página actual (va en el pie de página de cada vista).
 * En cada navegación registra la visita con POST /api/visitas; la base de datos (trigger)
 * incrementa el contador y la API devuelve el total acumulado de esa página.
 */
export default function ContadorVisitas({ className }: { className?: string }) {
    const { pathname } = useLocation();
    const [total, setTotal] = useState<number | null>(null);
    const ultima = useRef<{ ruta: string; momento: number } | null>(null);

    useEffect(() => {
        // Evita contar dos veces la misma página por renderizados repetidos (modo estricto de React).
        const ahora = Date.now();

        if (ultima.current && ultima.current.ruta === pathname && ahora - ultima.current.momento < 1500) {
            return;
        }

        ultima.current = { ruta: pathname, momento: ahora };
        let vigente = true;

        api.post<{ ruta: string; total: number }>('visitas', { ruta: pathname })
            .then(({ data }) => vigente && setTotal(data.total))
            .catch(() => vigente && setTotal(null));

        return () => {
            vigente = false;
        };
    }, [pathname]);

    return (
        <p className={cn('inline-flex items-center gap-1.5', className)} aria-live="polite">
            <Eye className="size-3.5" aria-hidden="true" />
            <span>
                Visitas a esta página: <strong className="font-semibold tabular-nums">{total === null ? '—' : numero(total)}</strong>
            </span>
        </p>
    );
}

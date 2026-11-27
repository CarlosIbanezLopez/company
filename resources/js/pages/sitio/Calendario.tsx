import { useSearchParams } from 'react-router';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos, BotonFiltro } from '@/components/sitio/Estados';
import type { RespuestaCalendario } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha } from '@/lib/formato';
import { cn } from '@/lib/utils';

/** Color de cada tipo de actividad (tokens del tema activo). */
const COLORES: Record<string, string> = {
    inscripciones: 'bg-chart-2',
    clases: 'bg-primary',
    examenes: 'bg-marca',
    feriado: 'bg-muted-foreground',
    academico: 'bg-chart-5',
    titulacion: 'bg-chart-4',
};

/** Fecha local de hoy en formato AAAA-MM-DD (para marcar las actividades ya pasadas). */
function hoyLocal(): string {
    const d = new Date();

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/** Calendario académico por gestión (RF-05). */
export default function Calendario() {
    useTitulo('Calendario académico');
    const [params, setParams] = useSearchParams();
    const gestion = params.get('gestion') ?? '';
    const consulta = useApi<RespuestaCalendario>('calendario-academico', { gestion });
    const hoy = hoyLocal();

    const elegir = (g: string) => {
        const nuevos = new URLSearchParams(params);
        nuevos.set('gestion', g);
        setParams(nuevos, { preventScrollReset: true });
    };

    return (
        <>
            <Encabezado
                titulo="Calendario académico"
                descripcion="Fechas importantes de la gestión: inscripciones, clases, exámenes y titulación."
                migas={[{ titulo: 'Académica' }, { titulo: 'Calendario académico' }]}
            />

            <div className="mx-auto max-w-5xl px-4 py-12 sm:px-6">
                <BloqueDatos consulta={consulta}>
                    {(datos) => (
                        <>
                            <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
                                {datos.gestiones.length > 0 && (
                                    <div className="flex flex-wrap gap-2" role="group" aria-label="Gestión">
                                        {datos.gestiones.map((g) => (
                                            <BotonFiltro
                                                key={g}
                                                activo={String(g) === String(datos.gestion ?? '')}
                                                onClick={() => elegir(String(g))}
                                                className="px-4 py-2 font-semibold"
                                            >
                                                Gestión {g}
                                            </BotonFiltro>
                                        ))}
                                    </div>
                                )}
                                <ul className="flex flex-wrap gap-3 text-xs" aria-label="Tipos de actividad">
                                    {Object.entries(datos.tipos).map(([clave, nombre]) => (
                                        <li key={clave} className="inline-flex items-center gap-1.5">
                                            <span className={cn('size-3 rounded-full', COLORES[clave] ?? 'bg-primary')} aria-hidden="true" />
                                            {nombre}
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            {datos.actividades.length > 0 ? (
                                <ol className="border-l-2 border-border">
                                    {datos.actividades.map((a) => {
                                        const pasada = (a.fecha_fin ?? a.fecha_inicio) < hoy;

                                        return (
                                            <li key={a.id} className="relative pb-6 pl-8">
                                                <span
                                                    className={cn('absolute top-1.5 -left-[9px] size-4 rounded-full border-2 border-background', COLORES[a.tipo] ?? 'bg-primary')}
                                                    aria-hidden="true"
                                                />
                                                <div className={cn('rounded-lg border bg-card p-4', pasada && 'opacity-60')}>
                                                    <p className="text-xs font-bold tracking-wider text-muted-foreground uppercase">
                                                        {fecha(a.fecha_inicio)}
                                                        {a.fecha_fin && <> — {fecha(a.fecha_fin)}</>}
                                                        <span className="ml-2 text-marca">{datos.tipos[a.tipo] ?? a.tipo}</span>
                                                        {pasada && <span className="sr-only"> (actividad finalizada)</span>}
                                                    </p>
                                                    <h2 className="mt-1 text-lg font-semibold">{a.titulo}</h2>
                                                    {a.descripcion && <p className="mt-1 text-sm text-muted-foreground">{a.descripcion}</p>}
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ol>
                            ) : (
                                <p className="text-muted-foreground">No hay actividades registradas para esta gestión.</p>
                            )}
                        </>
                    )}
                </BloqueDatos>
            </div>
        </>
    );
}

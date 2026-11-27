import { Clock, MapPin } from 'lucide-react';
import { Link, useSearchParams } from 'react-router';
import { Paginacion } from '@/components/ui/Paginacion';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos, SinResultados } from '@/components/sitio/Estados';
import { Imagen } from '@/components/sitio/Imagen';
import { Seccion } from '@/components/sitio/Seccion';
import type { RespuestaEventos } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha, hora } from '@/lib/formato';

const MESES = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

/** Eventos académicos: los próximos y el historial de los ya realizados (RF-13). */
export default function Eventos() {
    useTitulo('Eventos académicos');
    const [params] = useSearchParams();
    const page = params.get('page') ?? '';
    const consulta = useApi<RespuestaEventos>('eventos', { page });

    return (
        <>
            <Encabezado
                titulo="Eventos académicos"
                descripcion="Conferencias, seminarios, talleres, ferias y otras actividades de la Carrera."
                migas={[{ titulo: 'Noticias' }, { titulo: 'Eventos' }]}
            />

            <BloqueDatos consulta={consulta}>
                {({ proximos, historial, tipos }) => (
                    <>
                        <Seccion subtitulo="Agenda" titulo="Próximos eventos">
                            {proximos.length > 0 ? (
                                <ul className="space-y-4">
                                    {proximos.map((e) => (
                                        <li key={e.id}>
                                            <Link
                                                to={`/eventos/${e.slug}`}
                                                className="group grid overflow-hidden rounded-lg border bg-card hover:border-primary sm:grid-cols-[110px_160px_1fr]"
                                            >
                                                <div className="flex flex-row items-center justify-center gap-3 bg-primary p-4 text-primary-foreground sm:flex-col sm:gap-0">
                                                    <span className="text-4xl leading-none font-black">{e.fecha.slice(8, 10)}</span>
                                                    <span className="text-xs font-bold tracking-widest">
                                                        {MESES[Number(e.fecha.slice(5, 7)) - 1]} {e.fecha.slice(0, 4)}
                                                    </span>
                                                </div>
                                                <div className="hidden aspect-[4/5] sm:block">
                                                    <Imagen src={e.afiche_url} alt={`Afiche: ${e.titulo}`} />
                                                </div>
                                                <div className="p-5">
                                                    <p className="text-xs font-bold tracking-wider text-marca uppercase">{tipos[e.tipo] ?? e.tipo}</p>
                                                    <h3 className="mt-1 text-xl font-bold group-hover:text-primary">{e.titulo}</h3>
                                                    <p className="mt-3 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                                        {e.hora_inicio && (
                                                            <span className="inline-flex items-center gap-1.5">
                                                                <Clock className="size-4" aria-hidden="true" /> {hora(e.hora_inicio)}
                                                                {e.hora_fin && <> - {hora(e.hora_fin)}</>}
                                                            </span>
                                                        )}
                                                        {e.lugar && (
                                                            <span className="inline-flex items-center gap-1.5">
                                                                <MapPin className="size-4" aria-hidden="true" /> {e.lugar}
                                                            </span>
                                                        )}
                                                    </p>
                                                    {e.enlace_inscripcion && (
                                                        <span className="insignia mt-4 rounded-lg border-primary font-semibold text-primary">Inscripciones abiertas</span>
                                                    )}
                                                </div>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <SinResultados>No hay eventos programados por el momento.</SinResultados>
                            )}
                        </Seccion>

                        <div className="border-t bg-muted/40">
                            <Seccion subtitulo="Archivo" titulo="Historial de eventos">
                                {historial.data.length > 0 ? (
                                    <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                        {historial.data.map((e) => (
                                            <li key={e.id}>
                                                <Link to={`/eventos/${e.slug}`} className="group flex h-full overflow-hidden rounded-lg border bg-card hover:border-primary">
                                                    <div className="w-24 shrink-0">
                                                        <Imagen src={e.afiche_url} alt={`Afiche: ${e.titulo}`} className="grayscale group-hover:grayscale-0" />
                                                    </div>
                                                    <div className="p-4">
                                                        <p className="text-xs text-muted-foreground">
                                                            {fecha(e.fecha)} · {tipos[e.tipo] ?? e.tipo}
                                                        </p>
                                                        <h3 className="mt-1 leading-snug font-semibold group-hover:text-primary">{e.titulo}</h3>
                                                    </div>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-muted-foreground">Todavía no hay eventos finalizados.</p>
                                )}
                                <Paginacion datos={historial} className="mt-8" />
                            </Seccion>
                        </div>
                    </>
                )}
            </BloqueDatos>
        </>
    );
}

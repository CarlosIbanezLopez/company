import { CalendarDays, ExternalLink, Users } from 'lucide-react';
import { useParams } from 'react-router';
import { ErrorCarga } from '@/components/ui/Cargando';
import { EnlaceExterno } from '@/components/sitio/Enlace';
import { Encabezado } from '@/components/sitio/Encabezado';
import { CargandoDetalle } from '@/components/sitio/Estados';
import type { RespuestaProyecto } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha } from '@/lib/formato';

/** Detalle de un proyecto de investigación, PIA, semillero o producción académica (RF-07). */
export default function Proyecto() {
    const { slug = '' } = useParams();
    const ruta = `investigacion/${encodeURIComponent(slug)}`;

    return <DetalleProyecto key={ruta} ruta={ruta} />;
}

function DetalleProyecto({ ruta }: { ruta: string }) {
    const { datos, error, estado, recargar } = useApi<RespuestaProyecto>(ruta);

    useTitulo(datos?.proyecto.titulo ?? (estado === 404 ? 'Página no encontrada' : null));

    if (error) {
        return <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} />;
    }

    if (!datos) {
        return <CargandoDetalle />;
    }

    const { proyecto, tipos, estados } = datos;
    const etiquetaDato = 'text-xs font-bold tracking-wider text-muted-foreground uppercase';

    return (
        <>
            <Encabezado
                titulo={proyecto.titulo}
                descripcion={proyecto.linea?.nombre}
                migas={[{ titulo: 'Investigación', url: '/investigacion' }, { titulo: tipos[proyecto.tipo] ?? 'Proyecto' }]}
            />

            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-3">
                <article className="min-w-0 lg:col-span-2">
                    {proyecto.imagen_url && <img src={proyecto.imagen_url} alt={proyecto.titulo} className="mb-8 aspect-video w-full rounded-lg border object-cover" />}
                    <div className="contenido" dangerouslySetInnerHTML={{ __html: proyecto.html }} />
                    {proyecto.resultados && (
                        <section className="mt-8 rounded-lg border-l-4 border-chart-5 bg-card p-5" aria-labelledby="resultados-proyecto">
                            <h2 id="resultados-proyecto" className="font-bold">
                                Resultados obtenidos
                            </h2>
                            <p className="mt-2 text-sm whitespace-pre-line">{proyecto.resultados}</p>
                        </section>
                    )}
                </article>

                <aside className="h-fit overflow-hidden rounded-lg border bg-card" aria-label="Datos del proyecto">
                    <dl className="divide-y text-sm">
                        <div className="p-4">
                            <dt className={etiquetaDato}>Tipo</dt>
                            <dd className="mt-1 font-semibold">{tipos[proyecto.tipo] ?? proyecto.tipo}</dd>
                        </div>
                        <div className="p-4">
                            <dt className={etiquetaDato}>Estado</dt>
                            <dd className="mt-1 font-semibold text-primary">{estados[proyecto.estado] ?? proyecto.estado}</dd>
                        </div>
                        {proyecto.responsables && (
                            <div className="p-4">
                                <dt className={etiquetaDato}>Responsables</dt>
                                <dd className="mt-1 flex gap-2">
                                    <Users className="size-4 shrink-0" aria-hidden="true" />
                                    {proyecto.responsables}
                                </dd>
                            </div>
                        )}
                        <div className="p-4">
                            <dt className={etiquetaDato}>Periodo de ejecución</dt>
                            <dd className="mt-1 flex gap-2">
                                <CalendarDays className="size-4 shrink-0" aria-hidden="true" />
                                {fecha(proyecto.fecha_inicio)} — {proyecto.fecha_fin ? fecha(proyecto.fecha_fin) : 'en curso'}
                            </dd>
                        </div>
                        {(proyecto.enlace_publicacion || proyecto.enlace_repositorio) && (
                            <div className="p-4">
                                <dt className="sr-only">Enlaces</dt>
                                <dd className="space-y-2">
                                    {proyecto.enlace_publicacion && (
                                        <EnlaceExterno href={proyecto.enlace_publicacion} className="flex items-center gap-2 text-primary hover:underline">
                                            <ExternalLink className="size-4" aria-hidden="true" /> Ver publicación
                                        </EnlaceExterno>
                                    )}
                                    {proyecto.enlace_repositorio && (
                                        <EnlaceExterno href={proyecto.enlace_repositorio} className="flex items-center gap-2 text-primary hover:underline">
                                            <ExternalLink className="size-4" aria-hidden="true" /> Ver repositorio
                                        </EnlaceExterno>
                                    )}
                                </dd>
                            </div>
                        )}
                    </dl>
                </aside>
            </div>
        </>
    );
}

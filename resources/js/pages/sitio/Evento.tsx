import { CalendarDays, Clock, ExternalLink, MapPin } from 'lucide-react';
import { useParams } from 'react-router';
import { ErrorCarga } from '@/components/ui/Cargando';
import { EnlaceExterno } from '@/components/sitio/Enlace';
import { Encabezado } from '@/components/sitio/Encabezado';
import { CargandoDetalle } from '@/components/sitio/Estados';
import type { RespuestaEvento } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha, hora } from '@/lib/formato';

/** Detalle de un evento académico; los finalizados se muestran como parte del historial (RF-13). */
export default function Evento() {
    const { slug = '' } = useParams();
    const ruta = `eventos/${encodeURIComponent(slug)}`;

    return <DetalleEvento key={ruta} ruta={ruta} />;
}

function DetalleEvento({ ruta }: { ruta: string }) {
    const { datos, error, estado, recargar } = useApi<RespuestaEvento>(ruta);

    useTitulo(datos?.evento.titulo ?? (estado === 404 ? 'Página no encontrada' : null));

    if (error) {
        return <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} />;
    }

    if (!datos) {
        return <CargandoDetalle />;
    }

    const { evento, tipos } = datos;
    const etiquetaDato = 'flex items-center gap-1.5 text-xs font-bold text-muted-foreground uppercase';

    return (
        <>
            <Encabezado
                titulo={evento.titulo}
                descripcion={tipos[evento.tipo] ?? evento.tipo}
                migas={[{ titulo: 'Eventos', url: '/eventos' }, { titulo: evento.finalizado ? 'Historial' : 'Próximo' }]}
            />

            <div className="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-5">
                <div className="md:col-span-2">
                    {evento.afiche_url && <img src={evento.afiche_url} alt={`Afiche: ${evento.titulo}`} className="w-full rounded-lg border shadow-lg" />}
                </div>
                <div className="min-w-0 md:col-span-3">
                    {evento.finalizado && (
                        <p className="mb-4 rounded-lg border-l-4 border-muted-foreground bg-muted px-4 py-2 text-sm">Este evento ya finalizó y forma parte del historial.</p>
                    )}
                    <dl className="grid gap-px overflow-hidden rounded-lg border bg-border sm:grid-cols-3">
                        <div className="bg-card p-4">
                            <dt className={etiquetaDato}>
                                <CalendarDays className="size-4" aria-hidden="true" /> Fecha
                            </dt>
                            <dd className="mt-1 font-semibold">{fecha(evento.fecha)}</dd>
                        </div>
                        <div className="bg-card p-4">
                            <dt className={etiquetaDato}>
                                <Clock className="size-4" aria-hidden="true" /> Horario
                            </dt>
                            <dd className="mt-1 font-semibold">
                                {hora(evento.hora_inicio) || '—'}
                                {evento.hora_fin && <> - {hora(evento.hora_fin)}</>}
                            </dd>
                        </div>
                        <div className="bg-card p-4">
                            <dt className={etiquetaDato}>
                                <MapPin className="size-4" aria-hidden="true" /> Lugar
                            </dt>
                            <dd className="mt-1 font-semibold">{evento.lugar ?? '—'}</dd>
                        </div>
                    </dl>
                    <div className="contenido mt-8" dangerouslySetInnerHTML={{ __html: evento.html }} />
                    {evento.enlace_inscripcion && !evento.finalizado && (
                        <EnlaceExterno href={evento.enlace_inscripcion} className="boton mt-8 bg-marca text-marca-foreground">
                            Inscribirme <ExternalLink className="size-4" aria-hidden="true" />
                        </EnlaceExterno>
                    )}
                </div>
            </div>
        </>
    );
}

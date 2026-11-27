import { CalendarDays, User } from 'lucide-react';
import { Link, useParams } from 'react-router';
import { ErrorCarga } from '@/components/ui/Cargando';
import { Encabezado } from '@/components/sitio/Encabezado';
import { CargandoDetalle } from '@/components/sitio/Estados';
import { Imagen } from '@/components/sitio/Imagen';
import type { RespuestaNoticia } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha } from '@/lib/formato';

/** Detalle de una noticia con sus noticias relacionadas (RF-12). */
export default function Noticia() {
    const { slug = '' } = useParams();
    const ruta = `noticias/${encodeURIComponent(slug)}`;

    return <DetalleNoticia key={ruta} ruta={ruta} />;
}

function DetalleNoticia({ ruta }: { ruta: string }) {
    const { datos, error, estado, recargar } = useApi<RespuestaNoticia>(ruta);

    useTitulo(datos?.noticia.titulo ?? (estado === 404 ? 'Página no encontrada' : null));

    if (error) {
        return <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} />;
    }

    if (!datos) {
        return <CargandoDetalle />;
    }

    const { noticia, relacionadas } = datos;

    return (
        <>
            <Encabezado
                titulo={noticia.titulo}
                descripcion={noticia.resumen}
                migas={[
                    { titulo: 'Noticias', url: '/noticias' },
                    noticia.categoria ? { titulo: noticia.categoria.nombre, url: `/noticias?categoria=${encodeURIComponent(noticia.categoria.slug)}` } : { titulo: 'Noticia' },
                ]}
            >
                <div className="mt-6 flex flex-wrap gap-5 text-sm opacity-90">
                    <span className="inline-flex items-center gap-2">
                        <CalendarDays className="size-4" aria-hidden="true" />
                        <span className="sr-only">Publicada el </span>
                        {fecha(noticia.fecha_publicacion)}
                    </span>
                    {noticia.autor && (
                        <span className="inline-flex items-center gap-2">
                            <User className="size-4" aria-hidden="true" />
                            <span className="sr-only">Autor: </span>
                            {noticia.autor.name}
                        </span>
                    )}
                </div>
            </Encabezado>

            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-3">
                <article className="min-w-0 lg:col-span-2">
                    {noticia.imagen_url && <img src={noticia.imagen_url} alt={noticia.titulo} className="mb-8 aspect-video w-full rounded-lg border object-cover" />}
                    <div className="contenido text-[17px]" dangerouslySetInnerHTML={{ __html: noticia.html }} />
                </article>

                {relacionadas.length > 0 && (
                    <aside aria-labelledby="noticias-relacionadas">
                        <h2 id="noticias-relacionadas" className="mb-4 border-b pb-2 text-sm font-bold tracking-wider uppercase">
                            Noticias relacionadas
                        </h2>
                        <ul className="space-y-4">
                            {relacionadas.map((r) => (
                                <li key={r.id}>
                                    <Link to={`/noticias/${r.slug}`} className="group flex gap-3">
                                        <div className="size-20 shrink-0 overflow-hidden rounded-lg border">
                                            <Imagen src={r.imagen_url} alt={r.titulo} />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="text-xs text-muted-foreground">{fecha(r.fecha_publicacion)}</p>
                                            <p className="text-sm leading-snug font-semibold group-hover:text-primary">{r.titulo}</p>
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </aside>
                )}
            </div>
        </>
    );
}

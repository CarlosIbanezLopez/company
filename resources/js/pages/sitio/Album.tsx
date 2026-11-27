import { CirclePlay } from 'lucide-react';
import { useState } from 'react';
import { useParams } from 'react-router';
import { ErrorCarga } from '@/components/ui/Cargando';
import { EnlaceExterno } from '@/components/sitio/Enlace';
import { Encabezado } from '@/components/sitio/Encabezado';
import { CargandoDetalle } from '@/components/sitio/Estados';
import { Imagen } from '@/components/sitio/Imagen';
import type { RespuestaAlbum } from '@/components/sitio/tipos';
import { VisorFotos } from '@/components/sitio/VisorFotos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha, youtubeEmbed } from '@/lib/formato';

/** Álbum de fotografías con visor a pantalla completa y videos del álbum (RF-14). */
export default function Album() {
    const { slug = '' } = useParams();
    const ruta = `galeria/${encodeURIComponent(slug)}`;

    return <DetalleAlbum key={ruta} ruta={ruta} />;
}

function DetalleAlbum({ ruta }: { ruta: string }) {
    const { datos, error, estado, recargar } = useApi<RespuestaAlbum>(ruta);
    const [abierta, setAbierta] = useState<number | null>(null);

    useTitulo(datos?.album.titulo ?? (estado === 404 ? 'Página no encontrada' : null));

    if (error) {
        return <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} />;
    }

    if (!datos) {
        return <CargandoDetalle />;
    }

    const { album } = datos;
    const fotos = album.fotos ?? [];
    const videos = album.videos ?? [];

    return (
        <>
            <Encabezado
                titulo={album.titulo}
                descripcion={album.descripcion}
                migas={[{ titulo: 'Galería', url: '/galeria' }, { titulo: album.fecha ? fecha(album.fecha) : 'Álbum' }]}
            />

            <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                {fotos.length > 0 ? (
                    <ul className="grid grid-cols-2 gap-2 md:grid-cols-3 lg:grid-cols-4" aria-label={`Fotografías del álbum (${fotos.length})`}>
                        {fotos.map((f, i) => (
                            <li key={f.id}>
                                <button
                                    type="button"
                                    className="group block aspect-square w-full overflow-hidden rounded-lg border"
                                    aria-label={`Ver en grande: ${f.descripcion?.trim() || `fotografía ${i + 1}`}`}
                                    onClick={() => setAbierta(i)}
                                >
                                    <Imagen src={f.imagen_url} alt={f.descripcion?.trim() || `${album.titulo}, fotografía ${i + 1}`} className="transition-transform duration-500 group-hover:scale-110" />
                                </button>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="text-muted-foreground">Este álbum aún no tiene fotografías.</p>
                )}

                {videos.length > 0 && (
                    <section className="mt-12" aria-labelledby="videos-album">
                        <h2 id="videos-album" className="mb-4 text-xl font-bold">
                            Videos del álbum
                        </h2>
                        <div className="grid gap-6 md:grid-cols-2">
                            {videos.map((v) => {
                                const insertado = youtubeEmbed(v.url);

                                return insertado ? (
                                    <iframe
                                        key={v.id}
                                        src={insertado}
                                        title={v.titulo}
                                        className="aspect-video w-full rounded-lg border"
                                        allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowFullScreen
                                        loading="lazy"
                                        referrerPolicy="strict-origin-when-cross-origin"
                                    />
                                ) : (
                                    <EnlaceExterno key={v.id} href={v.url} className="flex items-center gap-3 rounded-lg border bg-card p-5 hover:border-primary">
                                        <CirclePlay className="size-8 shrink-0 text-marca" aria-hidden="true" /> {v.titulo}
                                    </EnlaceExterno>
                                );
                            })}
                        </div>
                    </section>
                )}
            </div>

            {abierta !== null && fotos.length > 0 && <VisorFotos fotos={fotos} indice={abierta} titulo={album.titulo} onCambiar={setAbierta} onCerrar={() => setAbierta(null)} />}
        </>
    );
}

import { CirclePlay, Images } from 'lucide-react';
import { Link } from 'react-router';
import { EnlaceExterno } from '@/components/sitio/Enlace';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos } from '@/components/sitio/Estados';
import { Imagen } from '@/components/sitio/Imagen';
import { Seccion } from '@/components/sitio/Seccion';
import type { RespuestaGaleria } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha, plural } from '@/lib/formato';

/** Galería multimedia: álbumes de fotografías y videos (RF-14). */
export default function Galeria() {
    useTitulo('Galería multimedia');
    const consulta = useApi<RespuestaGaleria>('galeria');

    return (
        <>
            <Encabezado titulo="Galería multimedia" descripcion="Fotografías y videos de las actividades de la Carrera." migas={[{ titulo: 'Noticias' }, { titulo: 'Galería' }]} />

            <BloqueDatos consulta={consulta}>
                {({ albumes, videos }) => (
                    <>
                        <Seccion subtitulo="Fotografías" titulo="Álbumes">
                            {albumes.length > 0 ? (
                                <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                    {albumes.map((a) => (
                                        <li key={a.id}>
                                            <Link to={`/galeria/${a.slug}`} className="group relative block aspect-[4/3] overflow-hidden rounded-lg border">
                                                <Imagen src={a.portada_url} alt={a.titulo} className="transition-transform duration-500 group-hover:scale-105" />
                                                <span className="absolute inset-x-0 bottom-0 block bg-linear-to-t from-franja/95 via-franja/75 to-transparent p-5 pt-12 text-franja-foreground">
                                                    {a.fecha && <span className="block text-xs">{fecha(a.fecha)}</span>}
                                                    <span className="block text-lg font-bold">{a.titulo}</span>
                                                    <span className="mt-1 inline-flex items-center gap-1.5 text-xs">
                                                        <Images className="size-3.5" aria-hidden="true" /> {plural(a.fotos_count ?? 0, 'foto')}
                                                    </span>
                                                </span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-muted-foreground">Aún no hay álbumes publicados.</p>
                            )}
                        </Seccion>

                        {videos.length > 0 && (
                            <div className="border-t bg-muted/40">
                                <Seccion subtitulo="Audiovisual" titulo="Videos">
                                    <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        {videos.map((v) => (
                                            <li key={v.id}>
                                                <EnlaceExterno href={v.url} className="group flex h-full items-start gap-4 rounded-lg border bg-card p-5 hover:border-primary">
                                                    <CirclePlay className="size-10 shrink-0 text-marca" aria-hidden="true" />
                                                    <span>
                                                        <span className="block font-semibold group-hover:text-primary">{v.titulo}</span>
                                                        {v.descripcion && <span className="mt-1 block text-sm text-muted-foreground">{v.descripcion}</span>}
                                                    </span>
                                                </EnlaceExterno>
                                            </li>
                                        ))}
                                    </ul>
                                </Seccion>
                            </div>
                        )}
                    </>
                )}
            </BloqueDatos>
        </>
    );
}

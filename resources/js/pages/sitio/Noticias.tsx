import { Star } from 'lucide-react';
import { Link, useSearchParams } from 'react-router';
import { Paginacion } from '@/components/ui/Paginacion';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos, BotonFiltro } from '@/components/sitio/Estados';
import { Imagen } from '@/components/sitio/Imagen';
import type { RespuestaNoticias } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha } from '@/lib/formato';

/** Noticias y comunicados publicados, filtrables por categoría y paginados (RF-12). */
export default function Noticias() {
    useTitulo('Noticias y comunicados');
    const [params, setParams] = useSearchParams();
    const categoria = params.get('categoria') ?? '';
    const page = params.get('page') ?? '';
    const consulta = useApi<RespuestaNoticias>('noticias', { categoria, page });

    // Al cambiar de categoría se vuelve a la primera página.
    const filtrar = (slug: string | null) => setParams(slug ? { categoria: slug } : {});

    return (
        <>
            <Encabezado
                titulo="Noticias y comunicados"
                descripcion="Noticias institucionales, actividades académicas, convocatorias y reconocimientos."
                migas={[{ titulo: 'Noticias' }]}
            />

            <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                <BloqueDatos consulta={consulta}>
                    {({ noticias, categorias }) => (
                        <>
                            {categorias.length > 0 && (
                                <div className="mb-8 flex flex-wrap gap-2" role="group" aria-label="Filtrar por categoría">
                                    <BotonFiltro activo={!categoria} onClick={() => filtrar(null)}>
                                        Todas
                                    </BotonFiltro>
                                    {categorias.map((c) => (
                                        <BotonFiltro key={c.slug} activo={categoria === c.slug} onClick={() => filtrar(c.slug)}>
                                            {c.nombre}
                                        </BotonFiltro>
                                    ))}
                                </div>
                            )}

                            {noticias.data.length > 0 ? (
                                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                                    {noticias.data.map((n) => (
                                        <Link
                                            key={n.id}
                                            to={`/noticias/${n.slug}`}
                                            className="group flex flex-col overflow-hidden rounded-lg border bg-card hover:border-primary"
                                        >
                                            <div className="relative aspect-video overflow-hidden">
                                                <Imagen src={n.imagen_url} alt={n.titulo} className="transition-transform duration-500 group-hover:scale-105" />
                                                {n.destacado && (
                                                    <span className="absolute top-2 left-2 inline-flex items-center gap-1 rounded-lg bg-marca px-2 py-1 text-xs font-semibold text-marca-foreground">
                                                        <Star className="size-3" aria-hidden="true" /> Destacada
                                                    </span>
                                                )}
                                            </div>
                                            <div className="flex flex-1 flex-col p-5">
                                                <div className="flex flex-wrap items-center gap-2 text-xs">
                                                    {n.categoria && <span className="font-semibold tracking-wide text-primary uppercase">{n.categoria.nombre}</span>}
                                                    <span className="text-muted-foreground">{fecha(n.fecha_publicacion)}</span>
                                                </div>
                                                <h2 className="mt-2 text-lg leading-snug font-bold group-hover:text-primary">{n.titulo}</h2>
                                                {n.resumen && <p className="mt-2 line-clamp-3 text-sm text-muted-foreground">{n.resumen}</p>}
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground">No hay noticias en esta categoría.</p>
                            )}

                            <Paginacion datos={noticias} className="mt-8" />
                        </>
                    )}
                </BloqueDatos>
            </div>
        </>
    );
}

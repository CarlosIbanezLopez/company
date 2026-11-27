import { Link } from 'react-router';
import { ErrorCarga } from '@/components/ui/Cargando';
import { Encabezado } from '@/components/sitio/Encabezado';
import { CargandoDetalle } from '@/components/sitio/Estados';
import type { RespuestaPagina } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { cn } from '@/lib/utils';

interface PropsPagina {
    /** Endpoint de la API: "institucional/{slug}", "academico/{slug}" o "revista-innovar/normas-para-autores". */
    ruta: string;
    /** Enlace de la miga de la sección (si la sección tiene página propia). */
    urlSeccion?: string;
}

/**
 * Página de contenido administrable (Markdown convertido a HTML por la API) con el menú de las
 * páginas hermanas de la misma sección (RF-02, RF-05 y normas para autores de la revista).
 */
export function PaginaContenido({ ruta, urlSeccion }: PropsPagina) {
    const { datos, error, estado, recargar } = useApi<RespuestaPagina>(ruta);

    useTitulo(datos?.pagina.titulo ?? (estado === 404 ? 'Página no encontrada' : null));

    if (error) {
        return <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} />;
    }

    if (!datos) {
        return <CargandoDetalle />;
    }

    const { pagina, seccion, hermanas, prefijo } = datos;
    const conMenu = hermanas.length > 0;

    return (
        <>
            <Encabezado titulo={pagina.titulo} descripcion={pagina.resumen} migas={[{ titulo: seccion, url: urlSeccion }, { titulo: pagina.titulo }]} />

            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-4">
                {conMenu && (
                    <aside className="lg:col-span-1">
                        <nav className="overflow-hidden rounded-lg border bg-card lg:sticky lg:top-24 xl:top-36" aria-label={`Páginas de la sección ${seccion}`}>
                            <p className="border-b bg-muted px-4 py-3 text-xs font-bold tracking-wider uppercase">{seccion}</p>
                            <ul>
                                {hermanas.map((h) => {
                                    const actual = h.slug === pagina.slug;

                                    return (
                                        <li key={h.slug}>
                                            <Link
                                                to={`/${prefijo}/${h.slug}`}
                                                aria-current={actual ? 'page' : undefined}
                                                className={cn(
                                                    'block border-l-4 px-4 py-2.5 text-sm transition-colors',
                                                    actual ? 'border-marca bg-accent font-semibold text-primary' : 'border-transparent hover:bg-accent',
                                                )}
                                            >
                                                {h.titulo}
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </nav>
                    </aside>
                )}

                <article className={conMenu ? 'lg:col-span-3' : 'max-w-4xl lg:col-span-4'}>
                    <div className="contenido text-[17px]" dangerouslySetInnerHTML={{ __html: pagina.html }} />
                    {pagina.imagen_url && (
                        <figure className="mt-8 rounded-lg border bg-card p-2">
                            <img src={pagina.imagen_url} alt={pagina.titulo} loading="lazy" className="w-full rounded-lg" />
                        </figure>
                    )}
                </article>
            </div>
        </>
    );
}

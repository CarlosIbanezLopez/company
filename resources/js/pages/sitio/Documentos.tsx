import { Download, FileText } from 'lucide-react';
import { useSearchParams } from 'react-router';
import { Paginacion } from '@/components/ui/Paginacion';
import { EnlaceExterno } from '@/components/sitio/Enlace';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos, SinResultados } from '@/components/sitio/Estados';
import { FormBusqueda } from '@/components/sitio/FormBusqueda';
import type { RespuestaDocumentos } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { urlApi } from '@/lib/config';
import { fecha, numero, plural } from '@/lib/formato';
import { cn } from '@/lib/utils';

/** Biblioteca digital: consultar, filtrar por categoría, buscar y descargar documentos (RF-06). */
export default function Documentos() {
    useTitulo('Biblioteca digital');
    const [params, setParams] = useSearchParams();
    const q = params.get('q') ?? '';
    const categoria = params.get('categoria') ?? '';
    const page = params.get('page') ?? '';
    const consulta = useApi<RespuestaDocumentos>('biblioteca', { q, categoria, page });

    /** Cambia los filtros de la URL y vuelve a la primera página de resultados. */
    const filtrar = (cambios: Record<string, string | null>) => {
        const nuevos = new URLSearchParams(params);

        for (const [clave, valor] of Object.entries(cambios)) {
            if (valor) {
                nuevos.set(clave, valor);
            } else {
                nuevos.delete(clave);
            }
        }

        nuevos.delete('page');
        setParams(nuevos, { preventScrollReset: true });
    };

    const categorias = consulta.datos?.categorias ?? [];
    const claseCategoria = (activa: boolean) =>
        cn(
            'flex w-full items-center justify-between gap-3 border-l-4 px-4 py-2.5 text-left text-sm',
            activa ? 'border-marca bg-accent font-semibold' : 'border-transparent hover:bg-accent',
        );

    return (
        <>
            <Encabezado
                titulo="Biblioteca digital"
                descripcion="Reglamentos, instructivos, formularios y convocatorias de la Carrera en formato PDF."
                migas={[{ titulo: 'Investigación' }, { titulo: 'Biblioteca digital' }]}
            />

            <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-4">
                <aside className="space-y-4">
                    <FormBusqueda valor={q} onBuscar={(texto) => filtrar({ q: texto })} etiqueta="Buscar documentos" placeholder="Buscar documento" />
                    {categorias.length > 0 && (
                        <nav className="overflow-hidden rounded-lg border bg-card" aria-labelledby="categorias-biblioteca">
                            <p id="categorias-biblioteca" className="border-b bg-muted px-4 py-3 text-xs font-bold tracking-wider uppercase">
                                Categorías
                            </p>
                            <ul>
                                <li>
                                    <button type="button" aria-pressed={!categoria} className={claseCategoria(!categoria)} onClick={() => filtrar({ categoria: null })}>
                                        Todas
                                    </button>
                                </li>
                                {categorias.map((c) => (
                                    <li key={c.id}>
                                        <button
                                            type="button"
                                            aria-pressed={categoria === c.slug}
                                            className={claseCategoria(categoria === c.slug)}
                                            onClick={() => filtrar({ categoria: c.slug })}
                                            title={c.descripcion ?? undefined}
                                        >
                                            <span>{c.nombre}</span>
                                            <span className="text-muted-foreground">
                                                {numero(c.documentos_count)}
                                                <span className="sr-only"> documentos</span>
                                            </span>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    )}
                </aside>

                <div className="lg:col-span-3">
                    <BloqueDatos consulta={consulta}>
                        {({ documentos }) => (
                            <>
                                <p className="mb-4 text-sm text-muted-foreground" role="status">
                                    {plural(documentos.total, 'documento encontrado', 'documentos encontrados')}
                                    {q && (
                                        <>
                                            {' '}
                                            para <strong className="text-foreground">«{q}»</strong>
                                        </>
                                    )}
                                </p>
                                {documentos.data.length > 0 ? (
                                    <ul className="divide-y overflow-hidden rounded-lg border bg-card">
                                        {documentos.data.map((d) => (
                                            <li key={d.id} className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                                                <div className="flex size-12 shrink-0 items-center justify-center rounded-lg bg-marca/10 text-marca" aria-hidden="true">
                                                    <FileText className="size-6" />
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    {d.categoria && <p className="text-xs font-semibold tracking-wide text-primary uppercase">{d.categoria.nombre}</p>}
                                                    <h2 className="font-semibold">{d.titulo}</h2>
                                                    {d.descripcion && <p className="text-sm text-muted-foreground">{d.descripcion}</p>}
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        Publicado: {fecha(d.fecha_publicacion)} · {plural(d.descargas ?? 0, 'descarga')}
                                                    </p>
                                                </div>
                                                <div className="flex shrink-0 gap-2">
                                                    {d.archivo_url && (
                                                        <EnlaceExterno href={d.archivo_url} className="boton-borde">
                                                            Ver<span className="sr-only"> {d.titulo}</span>
                                                        </EnlaceExterno>
                                                    )}
                                                    <a href={d.descargar_url ?? urlApi(`biblioteca/${d.id}/descargar`)} className="boton">
                                                        <Download className="size-4" aria-hidden="true" /> Descargar<span className="sr-only"> {d.titulo}</span>
                                                    </a>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <SinResultados>No hay documentos que coincidan con la búsqueda.</SinResultados>
                                )}
                                <Paginacion datos={documentos} className="mt-6" />
                            </>
                        )}
                    </BloqueDatos>
                </div>
            </div>
        </>
    );
}

import { Download, FileText } from 'lucide-react';
import { Link, useParams } from 'react-router';
import { ErrorCarga } from '@/components/ui/Cargando';
import { EnlaceExterno } from '@/components/sitio/Enlace';
import { Encabezado } from '@/components/sitio/Encabezado';
import { CargandoDetalle } from '@/components/sitio/Estados';
import { Imagen } from '@/components/sitio/Imagen';
import type { RespuestaEdicion } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha } from '@/lib/formato';

/** Edición de la Revista INNOVAR con sus artículos y otras ediciones (RF-08). */
export default function RevistaEdicion() {
    const { numero = '' } = useParams();
    const ruta = `revista-innovar/${encodeURIComponent(numero)}`;

    return <DetalleEdicion key={ruta} ruta={ruta} />;
}

function DetalleEdicion({ ruta }: { ruta: string }) {
    const { datos, error, estado, recargar } = useApi<RespuestaEdicion>(ruta);

    useTitulo(datos ? `INNOVAR N° ${datos.edicion.numero}` : estado === 404 ? 'Página no encontrada' : null);

    if (error) {
        return <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} />;
    }

    if (!datos) {
        return <CargandoDetalle />;
    }

    const { edicion, otras } = datos;

    return (
        <>
            <Encabezado
                titulo={`INNOVAR N° ${edicion.numero}: ${edicion.titulo}`}
                descripcion={edicion.fecha_publicacion ? `Publicada el ${fecha(edicion.fecha_publicacion)}` : null}
                migas={[{ titulo: 'Revista INNOVAR', url: '/revista-innovar' }, { titulo: `N° ${edicion.numero}` }]}
            />

            <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-4">
                <aside className="space-y-4">
                    <div className="aspect-[3/4] overflow-hidden rounded-lg border shadow-lg">
                        <Imagen src={edicion.portada_url} alt={`Portada de INNOVAR N° ${edicion.numero}`} />
                    </div>
                    {edicion.archivo_pdf_url && (
                        <EnlaceExterno
                            href={edicion.archivo_pdf_url}
                            className="flex items-center justify-center gap-2 rounded-lg bg-marca px-4 py-3 text-sm font-semibold text-marca-foreground hover:opacity-90"
                        >
                            <Download className="size-4" aria-hidden="true" /> Revista completa (PDF)
                        </EnlaceExterno>
                    )}
                    {edicion.descripcion && <p className="text-sm text-muted-foreground">{edicion.descripcion}</p>}
                </aside>

                <div className="lg:col-span-3">
                    <h2 className="mb-4 text-xl font-bold">Artículos de esta edición</h2>
                    {edicion.articulos.length > 0 ? (
                        <ol className="space-y-4">
                            {edicion.articulos.map((a, i) => {
                                const palabras = (a.palabras_clave ?? '')
                                    .split(',')
                                    .map((p) => p.trim())
                                    .filter(Boolean);

                                return (
                                    <li key={a.id} className="flex gap-4 rounded-lg border bg-card p-5">
                                        <span className="text-3xl font-black text-primary/30" aria-hidden="true">
                                            {String(i + 1).padStart(2, '0')}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <h3 className="text-lg leading-snug font-bold">{a.titulo}</h3>
                                            <p className="mt-1 text-sm font-medium text-primary">{a.autores}</p>
                                            {a.resumen && <p className="mt-2 text-sm text-muted-foreground">{a.resumen}</p>}
                                            {palabras.length > 0 && (
                                                <ul className="mt-3 flex flex-wrap gap-1.5" aria-label="Palabras clave">
                                                    {palabras.map((p) => (
                                                        <li key={p} className="rounded-lg bg-muted px-2 py-0.5 text-xs">
                                                            {p}
                                                        </li>
                                                    ))}
                                                </ul>
                                            )}
                                            {a.archivo_pdf_url && (
                                                <EnlaceExterno
                                                    href={a.archivo_pdf_url}
                                                    className="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline"
                                                >
                                                    <FileText className="size-4" aria-hidden="true" /> Leer artículo (PDF)
                                                    <span className="sr-only">: {a.titulo}</span>
                                                </EnlaceExterno>
                                            )}
                                        </div>
                                    </li>
                                );
                            })}
                        </ol>
                    ) : (
                        <p className="text-muted-foreground">Esta edición aún no tiene artículos publicados.</p>
                    )}

                    {otras.length > 0 && (
                        <section aria-labelledby="otras-ediciones">
                            <h2 id="otras-ediciones" className="mt-12 mb-4 text-xl font-bold">
                                Otras ediciones
                            </h2>
                            <ul className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                {otras.map((o) => (
                                    <li key={o.id}>
                                        <Link to={`/revista-innovar/${o.numero}`} className="group block">
                                            <div className="aspect-[3/4] overflow-hidden rounded-lg border">
                                                <Imagen src={o.portada_url} alt={o.titulo} />
                                            </div>
                                            <p className="mt-2 text-sm font-semibold group-hover:text-primary">N° {o.numero}</p>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}
                </div>
            </div>
        </>
    );
}

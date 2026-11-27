import { ArrowRight, FileText } from 'lucide-react';
import { Link } from 'react-router';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos } from '@/components/sitio/Estados';
import { Imagen } from '@/components/sitio/Imagen';
import type { RespuestaRevista } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha, plural } from '@/lib/formato';

/** Revista Académica INNOVAR: presentación y ediciones publicadas (RF-08). */
export default function Revista() {
    useTitulo('Revista INNOVAR');
    const consulta = useApi<RespuestaRevista>('revista-innovar');

    return (
        <>
            <Encabezado
                titulo="Revista Académica INNOVAR"
                descripcion="Revista académica institucional de la Carrera de Administración de Empresas."
                migas={[{ titulo: 'INNOVAR' }]}
            />

            <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                <BloqueDatos consulta={consulta}>
                    {({ ediciones, presentacion }) => (
                        <>
                            <div className="mb-12 grid gap-8 lg:grid-cols-3">
                                <div className="contenido lg:col-span-2" dangerouslySetInnerHTML={{ __html: presentacion ?? '' }} />
                                <aside className="h-fit rounded-lg border-l-4 border-marca bg-card p-6">
                                    <p className="font-bold">¿Desea publicar en INNOVAR?</p>
                                    <p className="mt-2 text-sm text-muted-foreground">Revise los requisitos y el proceso de evaluación para autores y colaboradores.</p>
                                    <Link to="/revista-innovar/normas-para-autores" className="boton mt-4">
                                        Normas para autores <ArrowRight className="size-4" aria-hidden="true" />
                                    </Link>
                                </aside>
                            </div>

                            <h2 className="mb-6 border-b pb-3 text-2xl font-bold">Ediciones publicadas</h2>
                            {ediciones.length > 0 ? (
                                <ul className="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                                    {ediciones.map((e) => (
                                        <li key={e.id} className="group flex flex-col">
                                            <Link
                                                to={`/revista-innovar/${e.numero}`}
                                                className="block aspect-[3/4] overflow-hidden rounded-lg border shadow-sm transition-shadow group-hover:shadow-xl"
                                                tabIndex={-1}
                                                aria-hidden="true"
                                            >
                                                <Imagen src={e.portada_url} alt={`Portada de INNOVAR N° ${e.numero}`} />
                                            </Link>
                                            <p className="mt-4 text-xs font-bold tracking-wider text-marca">
                                                N° {e.numero} · {fecha(e.fecha_publicacion)}
                                            </p>
                                            <Link to={`/revista-innovar/${e.numero}`} className="mt-1 leading-snug font-bold hover:text-primary">
                                                <span className="sr-only">Edición N° {e.numero}: </span>
                                                {e.titulo}
                                            </Link>
                                            <p className="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                                                <FileText className="size-3.5" aria-hidden="true" /> {plural(e.articulos_count ?? 0, 'artículo')}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-muted-foreground">Aún no hay ediciones publicadas.</p>
                            )}
                        </>
                    )}
                </BloqueDatos>
            </div>
        </>
    );
}

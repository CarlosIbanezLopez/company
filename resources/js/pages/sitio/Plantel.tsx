import { BookOpen, GraduationCap, Mail } from 'lucide-react';
import { useSearchParams } from 'react-router';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos } from '@/components/sitio/Estados';
import { FormBusqueda } from '@/components/sitio/FormBusqueda';
import { Imagen } from '@/components/sitio/Imagen';
import { Seccion } from '@/components/sitio/Seccion';
import type { RespuestaPlantel } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';

/** Autoridades y docentes de la Carrera con búsqueda por nombre, área o materia (RF-03). */
export default function Plantel() {
    useTitulo('Autoridades y docentes');
    const [params, setParams] = useSearchParams();
    const q = params.get('q') ?? '';
    const consulta = useApi<RespuestaPlantel>('plantel-docente', { q });

    const buscar = (texto: string) => {
        const nuevos = new URLSearchParams(params);

        if (texto.trim()) {
            nuevos.set('q', texto.trim());
        } else {
            nuevos.delete('q');
        }

        setParams(nuevos, { preventScrollReset: true });
    };

    return (
        <>
            <Encabezado
                titulo="Autoridades y docentes"
                descripcion="Plantel de autoridades y docentes de la Carrera de Administración de Empresas."
                migas={[{ titulo: 'La Carrera' }, { titulo: 'Autoridades y docentes' }]}
            />

            <BloqueDatos consulta={consulta}>
                {({ autoridades, docentes }) => (
                    <>
                        {autoridades.length > 0 && (
                            <Seccion subtitulo="Gestión" titulo="Autoridades">
                                <div className="grid gap-6 md:grid-cols-2">
                                    {autoridades.map((a) => (
                                        <article key={a.id} className="flex overflow-hidden rounded-lg border bg-card">
                                            <div className="w-36 shrink-0 sm:w-44">
                                                <Imagen src={a.foto_url} alt={a.nombre} className="aspect-[3/4]" />
                                            </div>
                                            <div className="flex min-w-0 flex-col justify-center border-l-4 border-marca p-5">
                                                {a.cargo && <p className="text-xs font-semibold tracking-wider text-marca uppercase">{a.cargo}</p>}
                                                <h3 className="mt-1 text-xl font-bold">{a.nombre}</h3>
                                                {a.formacion && (
                                                    <p className="mt-2 flex items-start gap-2 text-sm text-muted-foreground">
                                                        <GraduationCap className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                                        {a.formacion}
                                                    </p>
                                                )}
                                                {a.correo && (
                                                    <a href={`mailto:${a.correo}`} className="mt-2 inline-flex items-center gap-2 text-sm break-all text-primary hover:underline">
                                                        <Mail className="size-4 shrink-0" aria-hidden="true" />
                                                        {a.correo}
                                                    </a>
                                                )}
                                            </div>
                                        </article>
                                    ))}
                                </div>
                            </Seccion>
                        )}

                        <div className="border-t bg-muted/40">
                            <Seccion
                                subtitulo="Plantel"
                                titulo="Docentes"
                                acciones={
                                    <FormBusqueda
                                        valor={q}
                                        onBuscar={buscar}
                                        etiqueta="Buscar docentes por nombre, área o materia"
                                        placeholder="Buscar por nombre, área o materia"
                                        className="max-w-sm"
                                    />
                                }
                            >
                                {docentes.length > 0 ? (
                                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                                        {docentes.map((d) => (
                                            <article key={d.id} className="flex flex-col overflow-hidden rounded-lg border bg-card">
                                                <div className="aspect-square">
                                                    <Imagen src={d.foto_url} alt={d.nombre} />
                                                </div>
                                                <div className="flex flex-1 flex-col border-t-4 border-primary p-4">
                                                    <h3 className="leading-snug font-bold">{d.nombre}</h3>
                                                    {d.especialidad && <p className="text-xs font-semibold tracking-wide text-marca uppercase">{d.especialidad}</p>}
                                                    {d.formacion && <p className="mt-2 text-xs text-muted-foreground">{d.formacion}</p>}
                                                    {d.materias && (
                                                        <p className="mt-2 flex items-start gap-1.5 text-xs">
                                                            <BookOpen className="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                                                            {d.materias}
                                                        </p>
                                                    )}
                                                    {d.correo && (
                                                        <a href={`mailto:${d.correo}`} className="mt-auto inline-flex items-center gap-1.5 pt-3 text-xs break-all text-primary hover:underline">
                                                            <Mail className="size-3.5 shrink-0" aria-hidden="true" />
                                                            {d.correo}
                                                        </a>
                                                    )}
                                                </div>
                                            </article>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-muted-foreground">{q ? `No se encontraron docentes con el criterio «${q}».` : 'No se encontraron docentes con ese criterio.'}</p>
                                )}
                            </Seccion>
                        </div>
                    </>
                )}
            </BloqueDatos>
        </>
    );
}

import { Users } from 'lucide-react';
import { Link, useSearchParams } from 'react-router';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos, BotonFiltro, SinResultados } from '@/components/sitio/Estados';
import { Imagen } from '@/components/sitio/Imagen';
import { Seccion } from '@/components/sitio/Seccion';
import type { RespuestaInvestigacion } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha } from '@/lib/formato';
import { cn } from '@/lib/utils';

/** Insignia de cada estado del proyecto (tokens del tema). */
const ESTILO_ESTADO_PROYECTO: Record<string, string> = {
    finalizado: 'border-exito bg-exito/10 text-exito',
    en_curso: 'border-primary bg-primary text-primary-foreground',
    planificado: 'bg-muted text-foreground',
};

/** Líneas y proyectos de investigación, PIA, semilleros y producción académica (RF-07). */
export default function Investigacion() {
    useTitulo('Investigación');
    const [params, setParams] = useSearchParams();
    const tipo = params.get('tipo') ?? '';
    const linea = params.get('linea') ?? '';
    const estado = params.get('estado') ?? '';
    const consulta = useApi<RespuestaInvestigacion>('investigacion', { tipo, linea, estado });

    const filtrar = (cambios: Record<string, string | null>) => {
        const nuevos = new URLSearchParams(params);

        for (const [clave, valor] of Object.entries(cambios)) {
            if (valor) {
                nuevos.set(clave, valor);
            } else {
                nuevos.delete(clave);
            }
        }

        setParams(nuevos, { preventScrollReset: true });
    };

    return (
        <>
            <Encabezado
                titulo="Investigación"
                descripcion="Líneas y proyectos de investigación, Proyectos de Investigación en Aula (PIA), semilleros y producción académica de la Carrera."
                migas={[{ titulo: 'Investigación' }]}
            />

            <BloqueDatos consulta={consulta}>
                {(datos) => (
                    <>
                        <Seccion subtitulo="Ejes temáticos" titulo="Líneas de investigación">
                            {datos.lineas.length > 0 ? (
                                <div className="grid gap-px overflow-hidden rounded-lg border bg-border sm:grid-cols-2 lg:grid-cols-4" role="group" aria-label="Filtrar por línea de investigación">
                                    {datos.lineas.map((l) => {
                                        const activa = linea === String(l.id);

                                        return (
                                            <button
                                                key={l.id}
                                                type="button"
                                                aria-pressed={activa}
                                                className={cn('bg-card p-5 text-left transition-colors hover:bg-accent', activa && 'bg-accent outline-2 -outline-offset-2 outline-primary')}
                                                onClick={() => filtrar({ linea: activa ? null : String(l.id) })}
                                            >
                                                <span className="block text-3xl font-black text-primary">
                                                    {l.proyectos_count}
                                                    <span className="sr-only"> trabajos en</span>
                                                </span>
                                                <span className="mt-1 block font-semibold">{l.nombre}</span>
                                                {l.descripcion && <span className="mt-2 block text-xs text-muted-foreground">{l.descripcion}</span>}
                                            </button>
                                        );
                                    })}
                                </div>
                            ) : (
                                <p className="text-muted-foreground">Aún no hay líneas de investigación registradas.</p>
                            )}
                        </Seccion>

                        <div className="border-t bg-muted/40">
                            <Seccion subtitulo="Trabajos" titulo="Proyectos y producción académica">
                                <div className="mb-6 flex flex-wrap items-center gap-2" role="group" aria-label="Filtrar por tipo de trabajo">
                                    <BotonFiltro activo={!tipo} onClick={() => filtrar({ tipo: null })}>
                                        Todos
                                    </BotonFiltro>
                                    {Object.entries(datos.tipos).map(([clave, nombre]) => (
                                        <BotonFiltro key={clave} activo={tipo === clave} onClick={() => filtrar({ tipo: clave })}>
                                            {nombre}
                                        </BotonFiltro>
                                    ))}
                                    <select
                                        value={estado}
                                        className="campo h-9 w-auto bg-card"
                                        aria-label="Filtrar por estado"
                                        onChange={(e) => filtrar({ estado: e.target.value || null })}
                                    >
                                        <option value="">Todos los estados</option>
                                        {Object.entries(datos.estados).map(([clave, nombre]) => (
                                            <option key={clave} value={clave}>
                                                {nombre}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {datos.proyectos.length > 0 ? (
                                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                                        {datos.proyectos.map((p) => (
                                            <Link
                                                key={p.id}
                                                to={`/investigacion/${p.slug}`}
                                                className="group flex flex-col overflow-hidden rounded-lg border bg-card hover:border-primary"
                                            >
                                                <div className="aspect-video overflow-hidden">
                                                    <Imagen src={p.imagen_url} alt={p.titulo} className="transition-transform duration-500 group-hover:scale-105" />
                                                </div>
                                                <div className="flex flex-1 flex-col p-5">
                                                    <div className="flex flex-wrap items-center gap-2 text-xs">
                                                        <span className="insignia rounded-lg border-marca font-semibold text-marca">{datos.tipos[p.tipo] ?? p.tipo}</span>
                                                        <span className={cn('insignia rounded-lg font-semibold', ESTILO_ESTADO_PROYECTO[p.estado] ?? 'bg-muted')}>
                                                            {datos.estados[p.estado] ?? p.estado}
                                                        </span>
                                                    </div>
                                                    <h3 className="mt-3 leading-snug font-bold group-hover:text-primary">{p.titulo}</h3>
                                                    {p.linea && <p className="mt-1 text-xs text-muted-foreground">{p.linea.nombre}</p>}
                                                    {p.responsables && (
                                                        <p className="mt-auto flex items-center gap-1.5 pt-3 text-xs">
                                                            <Users className="size-3.5 shrink-0" aria-hidden="true" />
                                                            {p.responsables}
                                                        </p>
                                                    )}
                                                    <p className="mt-1 text-xs text-muted-foreground">Inicio: {fecha(p.fecha_inicio)}</p>
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                ) : (
                                    <SinResultados>No hay trabajos registrados con estos filtros.</SinResultados>
                                )}
                            </Seccion>
                        </div>
                    </>
                )}
            </BloqueDatos>
        </>
    );
}

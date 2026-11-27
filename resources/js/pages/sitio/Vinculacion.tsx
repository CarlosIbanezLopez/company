import { ArrowRight, CalendarRange } from 'lucide-react';
import { Link, useSearchParams } from 'react-router';
import { Encabezado } from '@/components/sitio/Encabezado';
import { BloqueDatos, BotonFiltro } from '@/components/sitio/Estados';
import { Imagen } from '@/components/sitio/Imagen';
import type { RespuestaVinculacion } from '@/components/sitio/tipos';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha } from '@/lib/formato';
import { cn } from '@/lib/utils';

/** Insignia de cada estado del convenio (tokens del tema). */
const ESTILO_ESTADO: Record<string, string> = {
    vigente: 'border-exito bg-exito/10 text-exito',
    en_tramite: 'border-aviso bg-aviso/15 text-foreground',
    finalizado: 'bg-muted text-muted-foreground',
};

/** Convenios institucionales, prácticas, proyectos con empresas, consultorías e interacción social (RF-09). */
export default function Vinculacion() {
    useTitulo('Vinculación y extensión');
    const [params, setParams] = useSearchParams();
    const tipo = params.get('tipo') ?? '';
    const consulta = useApi<RespuestaVinculacion>('vinculacion', { tipo });

    const filtrar = (valor: string | null) => {
        const nuevos = new URLSearchParams(params);

        if (valor) {
            nuevos.set('tipo', valor);
        } else {
            nuevos.delete('tipo');
        }

        setParams(nuevos, { preventScrollReset: true });
    };

    return (
        <>
            <Encabezado
                titulo="Vinculación y extensión"
                descripcion="Convenios institucionales, prácticas empresariales, proyectos con empresas, consultorías y actividades de interacción social."
                migas={[{ titulo: 'Vinculación' }]}
            />

            <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                <BloqueDatos consulta={consulta}>
                    {(datos) => (
                        <>
                            <div className="mb-8 flex flex-wrap gap-2" role="group" aria-label="Filtrar por tipo de relación">
                                <BotonFiltro activo={!tipo} onClick={() => filtrar(null)}>
                                    Todos
                                </BotonFiltro>
                                {Object.entries(datos.tipos).map(([clave, nombre]) => (
                                    <BotonFiltro key={clave} activo={tipo === clave} onClick={() => filtrar(clave)}>
                                        {nombre}
                                    </BotonFiltro>
                                ))}
                            </div>

                            {datos.convenios.length > 0 ? (
                                <div className="grid gap-6 md:grid-cols-2">
                                    {datos.convenios.map((c) => (
                                        <article key={c.id} className="flex overflow-hidden rounded-lg border bg-card">
                                            <div className="size-28 shrink-0 border-r sm:size-36">
                                                <Imagen src={c.logo_url} alt={`Logotipo de ${c.institucion}`} className="object-contain p-2" />
                                            </div>
                                            <div className="flex min-w-0 flex-1 flex-col p-5">
                                                <div className="flex flex-wrap items-center gap-2 text-xs">
                                                    <span className="font-semibold tracking-wide text-marca uppercase">{datos.tipos[c.tipo] ?? c.tipo}</span>
                                                    <span className={cn('insignia rounded-lg font-semibold', ESTILO_ESTADO[c.estado] ?? 'bg-muted')}>{datos.estados[c.estado] ?? c.estado}</span>
                                                </div>
                                                <h2 className="mt-2 leading-snug font-bold">{c.institucion}</h2>
                                                {c.finalidad && <p className="mt-2 text-sm text-muted-foreground">{c.finalidad}</p>}
                                                {c.fecha_inicio && (
                                                    <p className="mt-auto flex items-center gap-1.5 pt-3 text-xs text-muted-foreground">
                                                        <CalendarRange className="size-3.5 shrink-0" aria-hidden="true" /> {fecha(c.fecha_inicio)} —{' '}
                                                        {c.fecha_fin ? fecha(c.fecha_fin) : 'indefinido'}
                                                    </p>
                                                )}
                                            </div>
                                        </article>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground">No hay registros para este tipo de relación.</p>
                            )}
                        </>
                    )}
                </BloqueDatos>

                <Link
                    to="/innova"
                    className="group mt-12 flex flex-col items-start justify-between gap-4 rounded-lg bg-primary p-8 text-primary-foreground sm:flex-row sm:items-center"
                >
                    <span>
                        <span className="block text-xs font-semibold tracking-[0.18em] uppercase opacity-90">Consultoría estudiantil</span>
                        <span className="mt-1 block text-2xl font-bold">Empresa Junior INNOVA</span>
                        <span className="mt-1 block text-sm opacity-90">Solicite un servicio de consultoría o postule para ser parte del equipo.</span>
                    </span>
                    <span className="inline-flex shrink-0 items-center gap-2 rounded-lg bg-marca px-5 py-3 text-sm font-semibold text-marca-foreground">
                        Conocer INNOVA <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                    </span>
                </Link>
            </div>
        </>
    );
}

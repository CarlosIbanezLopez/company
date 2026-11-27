import { CircleCheck, CircleX, Percent, RotateCcw } from 'lucide-react';
import { Link } from 'react-router';
import { BarrasHorizontales } from '@/components/admin/BarrasHorizontales';
import { EncabezadoPagina, EnlaceSeccion, SeccionPanel } from '@/components/admin/EncabezadoPagina';
import { diaMes, fechaConDia } from '@/components/admin/fechas';
import { GraficoArea, GraficoBarrasH, GraficoColumnas, LeyendaGrafico, TablaDatos } from '@/components/admin/Graficos';
import type { SerieGrafico } from '@/components/admin/Graficos';
import { InsigniaEstado, InsigniaMetodo } from '@/components/admin/Insignia';
import { EsqueletoMetricas, GrillaMetricas, TarjetaMetrica } from '@/components/admin/TarjetaMetrica';
import { ErrorCarga, Esqueleto, Vacio } from '@/components/ui/Cargando';
import { useAuth } from '@/contexts/AuthContext';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha, numero, recortar } from '@/lib/formato';
import { cn } from '@/lib/utils';

interface Par {
    etiqueta: string | number;
    total: number;
}

/** GET /api/admin/dashboard (las secciones de seguridad llegan en null si el rol no ve la bitácora). */
interface RespuestaDashboard {
    tarjetas: { titulo: string; valor: number; icono: string }[];
    consultas: { id: number; nombre: string; mensaje: string; estado: string; created_at: string; categoria: { nombre: string } | null }[];
    bitacora: { id: number; accion: string; modulo: string; descripcion: string; created_at: string; usuario: { name: string } | null }[];
    paginasMasVistas: { ruta: string; total: number }[];
    visitasPorDia?: { fecha: string; total: number }[] | null;
    sesiones?: { exitosas: number; fallidas: number; porDia: { fecha: string; exitosas: number; fallidas: number }[] } | null;
    recursosMasAccedidos?: { recurso: string; metodo: string; ruta: string; total: number }[] | null;
    ipsFrecuentes?: { ip: string; total: number }[] | null;
    negocio?: { noticiasPorCategoria?: Par[]; consultasPorEstado?: Par[]; graduadosPorSituacion?: Par[] } | null;
}

const SERIE_VISITAS: SerieGrafico[] = [{ clave: 'total', nombre: 'Visitas', color: 'var(--chart-1)' }];

/** Éxito y fallo: azul de la serie 1 frente al rojo de error (par validado para daltonismo en todos los temas). */
const SERIES_SESIONES: SerieGrafico[] = [
    { clave: 'exitosas', nombre: 'Exitosos', color: 'var(--chart-1)', icono: CircleCheck },
    { clave: 'fallidas', nombre: 'Fallidos', color: 'var(--destructive)', icono: CircleX },
];

function EsqueletoTablero() {
    return (
        <div className="space-y-6" aria-busy="true">
            <EsqueletoMetricas cantidad={6} className="grid-cols-2 md:grid-cols-3 xl:grid-cols-6" />
            <div className="grid gap-6 xl:grid-cols-3">
                <Esqueleto className="h-80 xl:col-span-2" />
                <Esqueleto className="h-80" />
            </div>
            <span className="sr-only" role="status">
                Cargando el tablero…
            </span>
        </div>
    );
}

function Tablero({ datos }: { datos: RespuestaDashboard }) {
    const { puede } = useAuth();
    const visitas = datos.visitasPorDia ?? [];
    const totalVisitas = visitas.reduce((s, d) => s + (Number(d.total) || 0), 0);
    const sesiones = datos.sesiones;
    const intentos = sesiones ? sesiones.exitosas + sesiones.fallidas : 0;
    const recursos = datos.recursosMasAccedidos;
    const mitad = recursos ? Math.ceil(recursos.length / 2) : 0;
    const negocio = datos.negocio;
    const verConsultas = puede('consultas.ver');
    const verBitacora = puede('bitacora.ver');
    const datosRecursos = (recursos ?? []).map((r) => ({
        etiqueta: r.ruta || r.recurso,
        titulo: `${r.metodo} ${r.ruta} · ${r.recurso}`,
        total: r.total,
        prefijo: <InsigniaMetodo metodo={r.metodo} />,
    }));

    return (
        <div className="space-y-6">
            {datos.tarjetas?.length > 0 && (
                <GrillaMetricas className="grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
                    {datos.tarjetas.map((t) => (
                        <TarjetaMetrica key={t.titulo} titulo={t.titulo} valor={t.valor} icono={t.icono} />
                    ))}
                </GrillaMetricas>
            )}

            {/* Tráfico del portal */}
            <div className="grid gap-6 xl:grid-cols-3">
                <SeccionPanel
                    className="xl:col-span-2"
                    titulo="Visitas por día"
                    subtitulo={`Últimos ${visitas.length || 14} días · ${numero(totalVisitas)} visitas`}
                    accion={puede('reportes.ver') ? <EnlaceSeccion to="/admin/reportes">Ver reportes</EnlaceSeccion> : undefined}
                >
                    {visitas.length ? (
                        <>
                            <GraficoArea
                                datos={visitas}
                                claveX="fecha"
                                series={SERIE_VISITAS}
                                etiqueta={`Visitas por día de los últimos ${visitas.length} días`}
                                formatoX={diaMes}
                                formatoEtiqueta={fechaConDia}
                                unidad="visitas"
                            />
                            <TablaDatos
                                filas={visitas}
                                columnas={[
                                    { clave: 'fecha', etiqueta: 'Fecha', formato: (v) => fecha(String(v)) },
                                    { clave: 'total', etiqueta: 'Visitas', numerica: true },
                                ]}
                            />
                        </>
                    ) : (
                        <Vacio texto="Todavía no hay visitas registradas en el período." />
                    )}
                </SeccionPanel>

                <SeccionPanel titulo="Páginas más visitadas" subtitulo="Contador histórico por página">
                    <BarrasHorizontales
                        monoespaciado
                        vacio="Todavía no hay visitas registradas."
                        datos={(datos.paginasMasVistas ?? []).map((p) => ({ etiqueta: p.ruta, total: p.total }))}
                    />
                </SeccionPanel>
            </div>

            {/* Accesos y seguridad (solo con permiso de bitácora) */}
            {sesiones && (
                <div className="grid gap-6 xl:grid-cols-3">
                    <SeccionPanel
                        className="xl:col-span-2"
                        titulo="Inicios de sesión"
                        subtitulo="Intentos exitosos y fallidos de los últimos 14 días"
                        accion={verBitacora ? <EnlaceSeccion to="/admin/bitacora?pestana=sesiones">Ver bitácora</EnlaceSeccion> : undefined}
                    >
                        <GrillaMetricas className="mb-5 grid-cols-1 sm:grid-cols-3">
                            <TarjetaMetrica titulo="Exitosos" valor={sesiones.exitosas} icono={<CircleCheck className="size-4" aria-hidden="true" />} tono="exito" className="p-4" />
                            <TarjetaMetrica titulo="Fallidos" valor={sesiones.fallidas} icono={<CircleX className="size-4" aria-hidden="true" />} tono="peligro" className="p-4" />
                            <TarjetaMetrica
                                titulo="Tasa de éxito"
                                valor={intentos ? `${Math.round((sesiones.exitosas / intentos) * 100)} %` : '—'}
                                icono={<Percent className="size-4" aria-hidden="true" />}
                                className="p-4"
                            />
                        </GrillaMetricas>
                        <LeyendaGrafico series={SERIES_SESIONES} className="mb-3" />
                        <GraficoColumnas
                            datos={sesiones.porDia ?? []}
                            claveX="fecha"
                            series={SERIES_SESIONES}
                            etiqueta="Inicios de sesión exitosos y fallidos por día"
                            formatoX={diaMes}
                            formatoEtiqueta={fechaConDia}
                            alto={240}
                        />
                        <TablaDatos
                            filas={sesiones.porDia ?? []}
                            columnas={[
                                { clave: 'fecha', etiqueta: 'Fecha', formato: (v) => fecha(String(v)) },
                                { clave: 'exitosas', etiqueta: 'Exitosos', numerica: true },
                                { clave: 'fallidas', etiqueta: 'Fallidos', numerica: true },
                            ]}
                        />
                    </SeccionPanel>

                    <SeccionPanel titulo="IPs más frecuentes" subtitulo="Solicitudes a la API por dirección IP">
                        <BarrasHorizontales monoespaciado color="var(--chart-2)" vacio="Sin solicitudes registradas." datos={(datos.ipsFrecuentes ?? []).map((i) => ({ etiqueta: i.ip, total: i.total }))} />
                    </SeccionPanel>
                </div>
            )}

            {recursos && (
                <SeccionPanel
                    titulo="Recursos con mayor índice de acceso"
                    subtitulo="Endpoints de la API más solicitados (contador por recurso y método)"
                    accion={verBitacora ? <EnlaceSeccion to="/admin/bitacora?pestana=trafico">Ver tráfico</EnlaceSeccion> : undefined}
                >
                    {datosRecursos.length ? (
                        <div className="grid gap-x-10 gap-y-3 md:grid-cols-2">
                            <BarrasHorizontales monoespaciado datos={datosRecursos.slice(0, mitad)} />
                            {datosRecursos.length > mitad && <BarrasHorizontales monoespaciado datos={datosRecursos.slice(mitad)} />}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">Todavía no hay solicitudes registradas.</p>
                    )}
                </SeccionPanel>
            )}

            {/* Métricas del negocio */}
            {negocio && (
                <div className="grid gap-6 lg:grid-cols-3">
                    <SeccionPanel titulo="Noticias por categoría" accion={puede('noticias.ver') ? <EnlaceSeccion to="/admin/noticias">Noticias</EnlaceSeccion> : undefined}>
                        <GraficoBarrasH datos={negocio.noticiasPorCategoria ?? []} etiqueta="Cantidad de noticias por categoría" nombreSerie="Noticias" color="var(--chart-2)" vacio="No hay noticias registradas." />
                    </SeccionPanel>
                    <SeccionPanel titulo="Consultas por estado" accion={verConsultas ? <EnlaceSeccion to="/admin/consultas">Consultas</EnlaceSeccion> : undefined}>
                        <GraficoBarrasH datos={negocio.consultasPorEstado ?? []} etiqueta="Cantidad de consultas por estado" nombreSerie="Consultas" color="var(--chart-2)" vacio="No hay consultas registradas." />
                    </SeccionPanel>
                    <SeccionPanel titulo="Graduados por situación laboral" accion={puede('graduados.ver') ? <EnlaceSeccion to="/admin/graduados">Graduados</EnlaceSeccion> : undefined}>
                        <GraficoBarrasH
                            datos={negocio.graduadosPorSituacion ?? []}
                            etiqueta="Cantidad de graduados por situación laboral"
                            nombreSerie="Graduados"
                            color="var(--chart-2)"
                            anchoEtiquetas={170}
                            vacio="Aún no hay graduados registrados."
                        />
                    </SeccionPanel>
                </div>
            )}

            {/* Actividad reciente */}
            {(verConsultas || verBitacora) && (
                <div className={cn('grid gap-6', verConsultas && verBitacora && 'xl:grid-cols-5')}>
                    {verConsultas && (
                        <SeccionPanel className="xl:col-span-2" titulo="Últimas consultas recibidas" accion={<EnlaceSeccion to="/admin/consultas">Ver todas</EnlaceSeccion>} cuerpo="p-0">
                            <ul className="divide-y">
                                {(datos.consultas ?? []).map((c) => (
                                    <li key={c.id}>
                                        <Link to={`/admin/consultas/${c.id}/editar`} className="flex items-start justify-between gap-4 px-5 py-3 transition-colors hover:bg-accent/50">
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-semibold">
                                                    {c.nombre}
                                                    {c.categoria?.nombre && <span className="font-normal text-muted-foreground"> · {c.categoria.nombre}</span>}
                                                </p>
                                                <p className="truncate text-sm text-muted-foreground">{recortar(c.mensaje, 120)}</p>
                                            </div>
                                            <div className="shrink-0 text-right">
                                                <InsigniaEstado valor={c.estado} />
                                                <p className="mt-1 text-xs text-muted-foreground">{fecha(c.created_at)}</p>
                                            </div>
                                        </Link>
                                    </li>
                                ))}
                                {!datos.consultas?.length && <li className="px-5 py-8 text-center text-sm text-muted-foreground">No hay consultas.</li>}
                            </ul>
                        </SeccionPanel>
                    )}

                    {verBitacora && (
                        <SeccionPanel className="xl:col-span-3" titulo="Actividad reciente (bitácora)" accion={<EnlaceSeccion to="/admin/bitacora">Ver bitácora</EnlaceSeccion>} cuerpo="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <caption className="sr-only">Últimas acciones registradas en la bitácora</caption>
                                    <thead className="bg-muted text-left text-xs tracking-wide text-muted-foreground uppercase">
                                        <tr>
                                            <th scope="col" className="px-5 py-2.5 font-semibold">
                                                Usuario y fecha
                                            </th>
                                            <th scope="col" className="px-5 py-2.5 font-semibold">
                                                Acción
                                            </th>
                                            <th scope="col" className="px-5 py-2.5 font-semibold">
                                                Detalle
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {(datos.bitacora ?? []).map((b) => (
                                            <tr key={b.id} className="align-top">
                                                <td className="px-5 py-2.5">
                                                    <span className="block font-medium">{b.usuario?.name ?? 'Sistema'}</span>
                                                    <span className="block text-xs whitespace-nowrap text-muted-foreground">{fecha(b.created_at, true)}</span>
                                                </td>
                                                <td className="px-5 py-2.5">
                                                    <InsigniaEstado valor={b.accion} />
                                                </td>
                                                <td className="min-w-56 px-5 py-2.5">
                                                    <span className="block text-xs text-muted-foreground">{b.modulo}</span>
                                                    <span className="line-clamp-2">{recortar(b.descripcion, 140)}</span>
                                                </td>
                                            </tr>
                                        ))}
                                        {!datos.bitacora?.length && (
                                            <tr>
                                                <td colSpan={3} className="px-5 py-8 text-center text-muted-foreground">
                                                    Sin actividad registrada.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </SeccionPanel>
                    )}
                </div>
            )}
        </div>
    );
}

/** Tablero analítico del panel: métricas del portal, del negocio y del flujo de accesos. */
export default function Dashboard() {
    useTitulo('Panel de administración');
    const { usuario } = useAuth();
    const { datos, cargando, error, estado, recargar } = useApi<RespuestaDashboard>('admin/dashboard');

    return (
        <div className="space-y-6">
            <EncabezadoPagina
                titulo={`Bienvenido, ${usuario?.name ?? ''}`}
                descripcion={
                    <>
                        Rol: <strong className="font-semibold text-foreground">{usuario?.rol ?? 'Sin rol'}</strong> · Resumen general del portal de la Carrera.
                    </>
                }
                acciones={
                    <button type="button" className="boton-borde" onClick={recargar} disabled={cargando}>
                        <RotateCcw className={cn('size-4', cargando && 'animate-spin')} aria-hidden="true" /> Actualizar
                    </button>
                }
            />

            {datos ? (
                <div className={cn('transition-opacity', cargando && 'opacity-60')} aria-busy={cargando}>
                    <Tablero datos={datos} />
                </div>
            ) : error ? (
                <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} />
            ) : (
                <EsqueletoTablero />
            )}
        </div>
    );
}

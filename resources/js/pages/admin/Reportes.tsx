import { CalendarRange, Download, LoaderCircle, Printer } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import { toast } from 'sonner';
import { BarrasHorizontales } from '@/components/admin/BarrasHorizontales';
import { EncabezadoPagina, SeccionPanel } from '@/components/admin/EncabezadoPagina';
import { diaMes, fechaConDia, fechaLocal, haceDias } from '@/components/admin/fechas';
import { GraficoBarrasH, GraficoColumnas, TablaDatos } from '@/components/admin/Graficos';
import type { SerieGrafico } from '@/components/admin/Graficos';
import { EsqueletoMetricas, GrillaMetricas, TarjetaMetrica } from '@/components/admin/TarjetaMetrica';
import { useParametrosUrl } from '@/components/admin/useParametrosUrl';
import { ConPermiso } from '@/components/Guardias';
import { ErrorCarga, Esqueleto } from '@/components/ui/Cargando';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { descargarArchivo, mensajeError } from '@/lib/api';
import { fecha, numero } from '@/lib/formato';
import { cn } from '@/lib/utils';
import { validar } from '@/lib/validacion';

interface Par {
    etiqueta: string | number;
    total: number;
}

/** GET /api/admin/reportes?desde=&hasta= (mismas props que la página Reportes original). */
interface RespuestaReportes {
    filtros: { desde: string; hasta: string };
    resumen: { visitas_periodo: number; visitas_historicas: number; paginas_registradas: number; consultas_periodo: number };
    visitasPorDia: { fecha: string; total: number }[];
    paginasPeriodo: { ruta: string; total: number }[];
    paginasHistorico: { ruta: string; total: number; ultima_visita: string | null }[];
    consultasPorEstado: Par[];
    consultasPorCategoria: Par[];
    graduadosPorSituacion: Par[];
    graduadosPorAnio: Par[];
    documentosMasDescargados: { id: number; titulo: string; descargas: number; categoria: { nombre: string } | null }[];
    contenido: Par[];
    innova: { solicitudes: Par[]; postulaciones: Par[] };
    actividadUsuarios: Par[];
}

const SERIE_VISITAS: SerieGrafico[] = [{ clave: 'total', nombre: 'Visitas', color: 'var(--chart-1)' }];
const SERIE_GRADUADOS: SerieGrafico[] = [{ clave: 'total', nombre: 'Graduados', color: 'var(--chart-2)' }];

const pares = (lista: Par[] | undefined) => (lista ?? []).map((p) => ({ etiqueta: String(p.etiqueta ?? '—'), total: Number(p.total) || 0 }));

const PRESETS = [
    { dias: 7, texto: '7 días' },
    { dias: 30, texto: '30 días' },
    { dias: 90, texto: '90 días' },
];

function ContenidoReportes() {
    const { valores: consulta, cambiar } = useParametrosUrl();
    const { datos, cargando, error, estado, recargar } = useApi<RespuestaReportes>('admin/reportes', { desde: consulta.desde, hasta: consulta.hasta });
    const [desde, setDesde] = useState(consulta.desde ?? '');
    const [hasta, setHasta] = useState(consulta.hasta ?? '');
    const [errores, setErrores] = useState<Record<string, string>>({});
    const [exportando, setExportando] = useState(false);
    const id = useId();

    // El servidor define el período por defecto (últimos 30 días): se refleja en los campos.
    useEffect(() => {
        if (datos?.filtros) {
            setDesde(datos.filtros.desde);
            setHasta(datos.filtros.hasta);
        }
    }, [datos?.filtros]);

    const aplicar = (nuevoDesde: string, nuevoHasta: string) => {
        const encontrados = validar({ desde: nuevoDesde, hasta: nuevoHasta }, { desde: ['requerido', 'fecha'], hasta: ['requerido', 'fecha'] }, { desde: 'desde', hasta: 'hasta' });

        if (!encontrados.hasta && !encontrados.desde && nuevoDesde > nuevoHasta) {
            encontrados.hasta = 'La fecha «hasta» debe ser igual o posterior a la fecha «desde».';
        }

        setErrores(encontrados);

        if (Object.keys(encontrados).length === 0) {
            cambiar({ desde: nuevoDesde, hasta: nuevoHasta });
        }
    };

    const rango = (dias: number) => {
        const inicio = haceDias(dias - 1);
        const fin = fechaLocal();
        setDesde(inicio);
        setHasta(fin);
        aplicar(inicio, fin);
    };

    const exportar = async () => {
        if (!datos) {
            return;
        }

        setExportando(true);

        try {
            await descargarArchivo('admin/reportes/visitas.csv', { desde: datos.filtros.desde, hasta: datos.filtros.hasta }, `visitas_${datos.filtros.desde}_${datos.filtros.hasta}.csv`);
            toast.success('Reporte de visitas descargado.');
        } catch (e) {
            toast.error(mensajeError(e));
        } finally {
            setExportando(false);
        }
    };

    const visitas = datos?.visitasPorDia ?? [];

    return (
        <div className="space-y-6">
            <EncabezadoPagina
                titulo="Reportes y estadísticas"
                descripcion={datos ? `Período: ${fecha(datos.filtros.desde)} — ${fecha(datos.filtros.hasta)}` : 'Indicadores del portal por período.'}
                acciones={
                    <>
                        <button type="button" className="boton-borde" onClick={() => void exportar()} disabled={!datos || exportando}>
                            {exportando ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : <Download className="size-4" aria-hidden="true" />} Exportar visitas (CSV)
                        </button>
                        <button type="button" className="boton-borde" onClick={() => window.print()} disabled={!datos}>
                            <Printer className="size-4" aria-hidden="true" /> Imprimir / PDF
                        </button>
                    </>
                }
            />

            {/* Filtro de período (afecta a todos los indicadores de la página) */}
            <form
                noValidate
                className="tarjeta flex flex-wrap items-end gap-3 p-3 print:hidden"
                onSubmit={(e) => {
                    e.preventDefault();
                    aplicar(desde, hasta);
                }}
                aria-label="Período del reporte"
            >
                <div>
                    <label htmlFor={`${id}-desde`} className="mb-1 block text-xs font-medium text-muted-foreground">
                        Desde
                    </label>
                    <input id={`${id}-desde`} name="desde" type="date" value={desde} max={hasta || undefined} onChange={(e) => setDesde(e.target.value)} className="campo" aria-invalid={errores.desde ? true : undefined} />
                </div>
                <div>
                    <label htmlFor={`${id}-hasta`} className="mb-1 block text-xs font-medium text-muted-foreground">
                        Hasta
                    </label>
                    <input id={`${id}-hasta`} name="hasta" type="date" value={hasta} min={desde || undefined} onChange={(e) => setHasta(e.target.value)} className="campo" aria-invalid={errores.hasta ? true : undefined} />
                </div>
                <button type="submit" className="boton">
                    <CalendarRange className="size-4" aria-hidden="true" /> Aplicar
                </button>
                <div className="flex flex-wrap gap-1" role="group" aria-label="Períodos rápidos">
                    {PRESETS.map((p) => (
                        <button key={p.dias} type="button" className="boton-borde" onClick={() => rango(p.dias)}>
                            {p.texto}
                        </button>
                    ))}
                </div>
                {(errores.desde || errores.hasta) && (
                    <p role="alert" className="error-campo w-full">
                        {errores.desde ?? errores.hasta}
                    </p>
                )}
            </form>

            {!datos ? (
                error ? (
                    <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} />
                ) : (
                    <div className="space-y-6" aria-busy="true">
                        <EsqueletoMetricas cantidad={4} className="grid-cols-2 lg:grid-cols-4" />
                        <Esqueleto className="h-80" />
                    </div>
                )
            ) : (
                <div className={cn('space-y-6 transition-opacity', cargando && 'opacity-60')} aria-busy={cargando}>
                    <GrillaMetricas className="grid-cols-2 lg:grid-cols-4">
                        <TarjetaMetrica titulo="Visitas en el período" valor={datos.resumen.visitas_periodo} icono="eye" />
                        <TarjetaMetrica titulo="Visitas históricas" valor={datos.resumen.visitas_historicas} icono="bar-chart-3" />
                        <TarjetaMetrica titulo="Páginas con contador" valor={datos.resumen.paginas_registradas} icono="file-text" />
                        <TarjetaMetrica titulo="Consultas en el período" valor={datos.resumen.consultas_periodo} icono="inbox" />
                    </GrillaMetricas>

                    <SeccionPanel titulo="Visitas por día" subtitulo={`${numero(datos.resumen.visitas_periodo)} visitas en ${visitas.length} días`}>
                        <GraficoColumnas
                            datos={visitas}
                            claveX="fecha"
                            series={SERIE_VISITAS}
                            etiqueta="Visitas por día en el período seleccionado"
                            formatoX={diaMes}
                            formatoEtiqueta={fechaConDia}
                            unidad="visitas"
                            alto={280}
                        />
                        <TablaDatos
                            filas={visitas}
                            columnas={[
                                { clave: 'fecha', etiqueta: 'Fecha', formato: (v) => fecha(String(v)) },
                                { clave: 'total', etiqueta: 'Visitas', numerica: true },
                            ]}
                        />
                    </SeccionPanel>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <SeccionPanel titulo="Páginas más visitadas en el período">
                            <BarrasHorizontales monoespaciado datos={(datos.paginasPeriodo ?? []).map((p) => ({ etiqueta: p.ruta, total: p.total }))} />
                        </SeccionPanel>

                        <SeccionPanel titulo="Contador histórico por página" cuerpo="p-0">
                            <div className="max-h-[420px] overflow-y-auto">
                                <table className="w-full text-sm">
                                    <caption className="sr-only">Visitas acumuladas por página</caption>
                                    <thead className="sticky top-0 bg-muted text-left text-xs text-muted-foreground uppercase">
                                        <tr>
                                            <th scope="col" className="px-5 py-2 font-semibold">
                                                Página
                                            </th>
                                            <th scope="col" className="px-5 py-2 text-right font-semibold">
                                                Visitas
                                            </th>
                                            <th scope="col" className="px-5 py-2 font-semibold">
                                                Última visita
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {(datos.paginasHistorico ?? []).map((p) => (
                                            <tr key={p.ruta}>
                                                <td className="px-5 py-2 font-mono text-xs break-all">{p.ruta}</td>
                                                <td className="px-5 py-2 text-right font-semibold tabular-nums">{numero(p.total)}</td>
                                                <td className="px-5 py-2 text-xs whitespace-nowrap text-muted-foreground">{fecha(p.ultima_visita, true)}</td>
                                            </tr>
                                        ))}
                                        {!datos.paginasHistorico?.length && (
                                            <tr>
                                                <td colSpan={3} className="px-5 py-8 text-center text-muted-foreground">
                                                    Sin visitas registradas.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </SeccionPanel>

                        <SeccionPanel titulo="Consultas por categoría">
                            <GraficoBarrasH datos={pares(datos.consultasPorCategoria)} etiqueta="Consultas por categoría en el período" nombreSerie="Consultas" vacio="Sin consultas en el período." />
                        </SeccionPanel>
                        <SeccionPanel titulo="Consultas por estado">
                            <GraficoBarrasH datos={pares(datos.consultasPorEstado)} etiqueta="Consultas por estado en el período" nombreSerie="Consultas" vacio="Sin consultas en el período." />
                        </SeccionPanel>

                        <SeccionPanel titulo="Graduados por situación laboral">
                            <GraficoBarrasH
                                datos={pares(datos.graduadosPorSituacion)}
                                etiqueta="Graduados por situación laboral"
                                nombreSerie="Graduados"
                                color="var(--chart-2)"
                                anchoEtiquetas={180}
                                vacio="Aún no hay graduados registrados."
                            />
                        </SeccionPanel>
                        <SeccionPanel titulo="Graduados por año de graduación">
                            {datos.graduadosPorAnio?.length ? (
                                <>
                                    <GraficoColumnas datos={pares(datos.graduadosPorAnio)} claveX="etiqueta" series={SERIE_GRADUADOS} etiqueta="Graduados por año de graduación" unidad="graduados" alto={240} />
                                    <TablaDatos
                                        filas={pares(datos.graduadosPorAnio)}
                                        columnas={[
                                            { clave: 'etiqueta', etiqueta: 'Año' },
                                            { clave: 'total', etiqueta: 'Graduados', numerica: true },
                                        ]}
                                    />
                                </>
                            ) : (
                                <p className="text-sm text-muted-foreground">Aún no hay graduados registrados.</p>
                            )}
                        </SeccionPanel>

                        <SeccionPanel titulo="Documentos más descargados">
                            <BarrasHorizontales
                                datos={(datos.documentosMasDescargados ?? []).map((d) => ({
                                    etiqueta: d.titulo,
                                    total: d.descargas,
                                    detalle: d.categoria?.nombre,
                                    titulo: `${d.titulo}${d.categoria?.nombre ? ` (${d.categoria.nombre})` : ''}`,
                                }))}
                                vacio="Todavía no hay descargas."
                            />
                        </SeccionPanel>
                        <SeccionPanel titulo="Contenido publicado por módulo">
                            <GraficoBarrasH datos={pares(datos.contenido)} etiqueta="Cantidad de contenido por módulo" nombreSerie="Registros" color="var(--chart-5)" anchoEtiquetas={170} />
                        </SeccionPanel>

                        <SeccionPanel titulo="INNOVA: solicitudes y postulaciones">
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <p className="mb-3 text-xs font-bold text-muted-foreground uppercase">Solicitudes de servicio</p>
                                    <BarrasHorizontales datos={pares(datos.innova?.solicitudes)} vacio="Sin solicitudes." color="var(--chart-2)" />
                                </div>
                                <div>
                                    <p className="mb-3 text-xs font-bold text-muted-foreground uppercase">Postulaciones</p>
                                    <BarrasHorizontales datos={pares(datos.innova?.postulaciones)} vacio="Sin postulaciones." color="var(--chart-2)" />
                                </div>
                            </div>
                        </SeccionPanel>
                        <SeccionPanel titulo="Actividad de usuarios (bitácora)" subtitulo="Acciones registradas en el período">
                            <BarrasHorizontales datos={pares(datos.actividadUsuarios)} vacio="Sin actividad registrada en el período." />
                        </SeccionPanel>
                    </div>
                </div>
            )}
        </div>
    );
}

/** CU8 - Reportes y estadísticas del portal. */
export default function Reportes() {
    useTitulo('Reportes y estadísticas');

    return (
        <ConPermiso permiso="reportes.ver">
            <ContenidoReportes />
        </ConPermiso>
    );
}

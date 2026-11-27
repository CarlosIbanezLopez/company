import { Activity, CircleCheck, CircleX, Globe, History, LogIn, Users, X } from 'lucide-react';
import { useId } from 'react';
import type { ReactNode } from 'react';
import { BarrasHorizontales } from '@/components/admin/BarrasHorizontales';
import { EncabezadoPagina, SeccionPanel } from '@/components/admin/EncabezadoPagina';
import { horaDelDia } from '@/components/admin/fechas';
import { GraficoColumnas, TablaDatos } from '@/components/admin/Graficos';
import type { SerieGrafico } from '@/components/admin/Graficos';
import { Insignia, InsigniaHttp, InsigniaMetodo } from '@/components/admin/Insignia';
import { ListadoCrud } from '@/components/admin/ListadoCrud';
import { EsqueletoMetricas, GrillaMetricas, TarjetaMetrica } from '@/components/admin/TarjetaMetrica';
import { useParametrosUrl, useTextoUrl } from '@/components/admin/useParametrosUrl';
import type { CambiarParametros } from '@/components/admin/useParametrosUrl';
import { ConPermiso } from '@/components/Guardias';
import { ErrorCarga, Esqueleto } from '@/components/ui/Cargando';
import { Paginacion } from '@/components/ui/Paginacion';
import { Pestanas } from '@/components/ui/Pestanas';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { fecha, numero } from '@/lib/formato';
import { cn } from '@/lib/utils';
import type { Paginado } from '@/types';

/*
 * Bitácora de auditoría (RF-19): acciones de los usuarios del panel, intentos de inicio de sesión
 * (exitosos y fallidos) y tráfico de la API por IP, recurso y hora. Requiere el permiso bitacora.ver.
 */
interface UsuarioBreve {
    id: number;
    name: string;
}

interface RespuestaSesiones {
    resumen: { exitosas: number; fallidas: number; hoy: number; ipsDistintas: number };
    ipsConFallos: { ip: string; total: number }[];
    registros: Paginado<{ id: number; email: string; exitoso: boolean; motivo: string | null; ip: string | null; agente: string | null; created_at: string; usuario: UsuarioBreve | null }>;
}

interface RespuestaTrafico {
    resumen: { solicitudes: number; hoy: number; ipsDistintas: number; usuariosDistintos: number };
    recursos: { recurso: string; metodo: string; ruta: string; total: number; ultimo_acceso: string | null }[];
    ips: { ip: string; total: number; ultimo: string | null }[];
    paginas: { ruta: string; total: number }[];
    porHora: { hora: number; total: number }[];
    registros: Paginado<{ id: number; metodo: string; ruta: string; recurso: string; estado: number; duracion_ms: number; ip: string | null; created_at: string; usuario: UsuarioBreve | null }>;
}

const PESTANAS = [
    { id: 'acciones', texto: 'Acciones', icono: History },
    { id: 'sesiones', texto: 'Inicios de sesión', icono: LogIn },
    { id: 'trafico', texto: 'Tráfico', icono: Activity },
] as const;

type IdPestana = (typeof PESTANAS)[number]['id'];

const SERIE_HORAS: SerieGrafico[] = [{ clave: 'total', nombre: 'Solicitudes', color: 'var(--chart-1)' }];

const claseTh = 'px-4 py-3 font-semibold whitespace-nowrap';
const claseTd = 'px-4 py-2.5 align-middle';

/** Campo de filtro con su etiqueta pequeña (fila de filtros sobre los resultados). */
function Filtro({ etiqueta, children, className }: { etiqueta: string; children: (id: string) => ReactNode; className?: string }) {
    const id = useId();

    return (
        <div className={cn('min-w-36 flex-1 sm:flex-none', className)}>
            <label htmlFor={id} className="mb-1 block text-xs font-medium text-muted-foreground">
                {etiqueta}
            </label>
            {children(id)}
        </div>
    );
}

function FiltrosFechas({ consulta, cambiar }: { consulta: Record<string, string>; cambiar: CambiarParametros }) {
    return (
        <>
            <Filtro etiqueta="Desde">
                {(id) => <input id={id} type="date" className="campo" value={consulta.desde ?? ''} max={consulta.hasta || undefined} onChange={(e) => cambiar({ desde: e.target.value || null })} />}
            </Filtro>
            <Filtro etiqueta="Hasta">
                {(id) => <input id={id} type="date" className="campo" value={consulta.hasta ?? ''} min={consulta.desde || undefined} onChange={(e) => cambiar({ hasta: e.target.value || null })} />}
            </Filtro>
        </>
    );
}

function BotonLimpiar({ visible, onLimpiar }: { visible: boolean; onLimpiar: () => void }) {
    return visible ? (
        <button type="button" className="boton-borde" onClick={onLimpiar}>
            <X className="size-4" aria-hidden="true" /> Limpiar
        </button>
    ) : null;
}

function EsqueletoPestana() {
    return (
        <div className="space-y-6" aria-busy="true">
            <EsqueletoMetricas cantidad={4} className="grid-cols-2 lg:grid-cols-4" />
            <Esqueleto className="h-72" />
            <span className="sr-only" role="status">
                Cargando…
            </span>
        </div>
    );
}

/* ------------------------------------------------------------------ Inicios de sesión */

function PestanaSesiones({ consulta, cambiar, limpiar }: { consulta: Record<string, string>; cambiar: CambiarParametros; limpiar: () => void }) {
    const { datos, cargando, error, estado, recargar } = useApi<RespuestaSesiones>('admin/bitacora/sesiones', {
        exitoso: consulta.exitoso,
        email: consulta.email,
        ip: consulta.ip,
        desde: consulta.desde,
        hasta: consulta.hasta,
        page: consulta.page,
    });
    const [email, setEmail] = useTextoUrl('email', consulta.email ?? '', cambiar);
    const [ip, setIp] = useTextoUrl('ip', consulta.ip ?? '', cambiar);
    const hayFiltros = ['exitoso', 'email', 'ip', 'desde', 'hasta'].some((k) => consulta[k]);

    return (
        <div className="space-y-6">
            <div role="search" aria-label="Filtrar inicios de sesión" className="tarjeta flex flex-wrap items-end gap-3 p-3">
                <Filtro etiqueta="Resultado">
                    {(id) => (
                        <select id={id} className="campo" value={consulta.exitoso ?? ''} onChange={(e) => cambiar({ exitoso: e.target.value || null })}>
                            <option value="">Todos</option>
                            <option value="1">Exitosos</option>
                            <option value="0">Fallidos</option>
                        </select>
                    )}
                </Filtro>
                <Filtro etiqueta="Correo" className="min-w-52">
                    {(id) => <input id={id} type="search" className="campo" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="usuario@…" autoComplete="off" />}
                </Filtro>
                <Filtro etiqueta="Dirección IP">
                    {(id) => <input id={id} type="search" className="campo font-mono" value={ip} onChange={(e) => setIp(e.target.value)} placeholder="Ej. 127.0.0.1" autoComplete="off" />}
                </Filtro>
                <FiltrosFechas consulta={consulta} cambiar={cambiar} />
                <BotonLimpiar
                    visible={hayFiltros}
                    onLimpiar={() => {
                        setEmail('');
                        setIp('');
                        limpiar();
                    }}
                />
            </div>

            {!datos ? (
                error ? (
                    <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} />
                ) : (
                    <EsqueletoPestana />
                )
            ) : (
                <div className={cn('space-y-6 transition-opacity', cargando && 'opacity-60')} aria-busy={cargando}>
                    <GrillaMetricas className="grid-cols-2 lg:grid-cols-4">
                        <TarjetaMetrica titulo="Intentos exitosos" valor={datos.resumen.exitosas} icono={<CircleCheck className="size-4" aria-hidden="true" />} tono="exito" />
                        <TarjetaMetrica titulo="Intentos fallidos" valor={datos.resumen.fallidas} icono={<CircleX className="size-4" aria-hidden="true" />} tono="peligro" />
                        <TarjetaMetrica titulo="Intentos de hoy" valor={datos.resumen.hoy} icono="calendar" />
                        <TarjetaMetrica titulo="IPs distintas" valor={datos.resumen.ipsDistintas} icono={<Globe className="size-4" aria-hidden="true" />} />
                    </GrillaMetricas>

                    <div className="grid gap-6 xl:grid-cols-3">
                        <SeccionPanel className="xl:col-span-2" titulo="Registro de intentos" subtitulo={`${numero(datos.registros.total)} intentos con los filtros actuales`} cuerpo="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <caption className="sr-only">Intentos de inicio de sesión</caption>
                                    <thead className="bg-muted text-left text-xs tracking-wide text-muted-foreground uppercase">
                                        <tr>
                                            <th scope="col" className={claseTh}>
                                                Fecha y hora
                                            </th>
                                            <th scope="col" className={claseTh}>
                                                Correo
                                            </th>
                                            <th scope="col" className={claseTh}>
                                                Resultado
                                            </th>
                                            <th scope="col" className={claseTh}>
                                                IP
                                            </th>
                                            <th scope="col" className={claseTh}>
                                                Navegador
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {datos.registros.data.map((r) => (
                                            <tr key={r.id} className="hover:bg-accent/40">
                                                <td className={cn(claseTd, 'whitespace-nowrap text-muted-foreground')}>{fecha(r.created_at, true)}</td>
                                                <td className={claseTd}>
                                                    <span className="block max-w-56 truncate font-medium" title={r.email}>
                                                        {r.email}
                                                    </span>
                                                    {r.usuario && <span className="block text-xs text-muted-foreground">{r.usuario.name}</span>}
                                                </td>
                                                <td className={claseTd}>
                                                    {r.exitoso ? (
                                                        <Insignia tono="exito">
                                                            <CircleCheck className="size-3.5" aria-hidden="true" /> Exitoso
                                                        </Insignia>
                                                    ) : (
                                                        <Insignia tono="peligro" title={r.motivo ?? undefined}>
                                                            <CircleX className="size-3.5" aria-hidden="true" /> Fallido
                                                        </Insignia>
                                                    )}
                                                    {!r.exitoso && r.motivo && <span className="mt-0.5 block text-xs text-muted-foreground">{r.motivo}</span>}
                                                </td>
                                                <td className={cn(claseTd, 'font-mono text-xs whitespace-nowrap')}>
                                                    {r.ip ? (
                                                        <button type="button" className="hover:text-primary hover:underline" onClick={() => setIp(r.ip ?? '')} title="Filtrar por esta IP">
                                                            {r.ip}
                                                        </button>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </td>
                                                <td className={claseTd}>
                                                    <span className="block max-w-64 truncate text-xs text-muted-foreground" title={r.agente ?? undefined}>
                                                        {r.agente ?? '—'}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                        {!datos.registros.data.length && (
                                            <tr>
                                                <td colSpan={5} className="px-4 py-12 text-center text-muted-foreground">
                                                    No hay intentos de inicio de sesión{hayFiltros ? ' con estos filtros' : ''}.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                            <div className="border-t px-4 py-3">
                                <Paginacion datos={datos.registros} />
                            </div>
                        </SeccionPanel>

                        <SeccionPanel titulo="IPs con más intentos fallidos" subtitulo="Posibles ataques de fuerza bruta">
                            <BarrasHorizontales
                                monoespaciado
                                color="var(--destructive)"
                                vacio="No hay intentos fallidos con los filtros actuales."
                                datos={(datos.ipsConFallos ?? []).map((i) => ({ etiqueta: i.ip, total: i.total }))}
                            />
                        </SeccionPanel>
                    </div>
                </div>
            )}
        </div>
    );
}

/* ------------------------------------------------------------------ Tráfico */

function PestanaTrafico({ consulta, cambiar, limpiar }: { consulta: Record<string, string>; cambiar: CambiarParametros; limpiar: () => void }) {
    const { datos, cargando, error, estado, recargar } = useApi<RespuestaTrafico>('admin/bitacora/trafico', {
        desde: consulta.desde,
        hasta: consulta.hasta,
        ip: consulta.ip,
        metodo: consulta.metodo,
        page: consulta.page,
    });
    const [ip, setIp] = useTextoUrl('ip', consulta.ip ?? '', cambiar);
    const hayFiltros = ['desde', 'hasta', 'ip', 'metodo'].some((k) => consulta[k]);
    const conFechas = Boolean(consulta.desde || consulta.hasta);

    return (
        <div className="space-y-6">
            <div role="search" aria-label="Filtrar tráfico" className="tarjeta flex flex-wrap items-end gap-3 p-3">
                <FiltrosFechas consulta={consulta} cambiar={cambiar} />
                <Filtro etiqueta="Dirección IP">
                    {(id) => <input id={id} type="search" className="campo font-mono" value={ip} onChange={(e) => setIp(e.target.value)} placeholder="Ej. 127.0.0.1" autoComplete="off" />}
                </Filtro>
                <Filtro etiqueta="Método">
                    {(id) => (
                        <select id={id} className="campo" value={consulta.metodo ?? ''} onChange={(e) => cambiar({ metodo: e.target.value || null })}>
                            <option value="">Todos</option>
                            {['GET', 'POST', 'PUT', 'PATCH', 'DELETE'].map((m) => (
                                <option key={m} value={m}>
                                    {m}
                                </option>
                            ))}
                        </select>
                    )}
                </Filtro>
                <BotonLimpiar
                    visible={hayFiltros}
                    onLimpiar={() => {
                        setIp('');
                        limpiar();
                    }}
                />
            </div>

            {!datos ? (
                error ? (
                    <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} />
                ) : (
                    <EsqueletoPestana />
                )
            ) : (
                <div className={cn('space-y-6 transition-opacity', cargando && 'opacity-60')} aria-busy={cargando}>
                    <GrillaMetricas className="grid-cols-2 lg:grid-cols-4">
                        <TarjetaMetrica titulo="Solicitudes" valor={datos.resumen.solicitudes} icono="activity" />
                        <TarjetaMetrica titulo="Solicitudes de hoy" valor={datos.resumen.hoy} icono="calendar" />
                        <TarjetaMetrica titulo="IPs distintas" valor={datos.resumen.ipsDistintas} icono={<Globe className="size-4" aria-hidden="true" />} />
                        <TarjetaMetrica titulo="Usuarios distintos" valor={datos.resumen.usuariosDistintos} icono={<Users className="size-4" aria-hidden="true" />} />
                    </GrillaMetricas>

                    <SeccionPanel titulo="Distribución por hora del día" subtitulo={conFechas ? 'Solicitudes del período filtrado' : 'Solicitudes de los últimos 7 días'}>
                        <GraficoColumnas
                            datos={datos.porHora ?? []}
                            claveX="hora"
                            series={SERIE_HORAS}
                            etiqueta="Solicitudes a la API por hora del día"
                            formatoX={horaDelDia}
                            formatoEtiqueta={(h) => `De ${horaDelDia(h)} a ${horaDelDia(Number(h) + 1 === 24 ? 0 : Number(h) + 1)}`}
                            unidad="solicitudes"
                            alto={240}
                        />
                        <TablaDatos
                            filas={datos.porHora ?? []}
                            columnas={[
                                { clave: 'hora', etiqueta: 'Hora', formato: (v) => horaDelDia(Number(v)) },
                                { clave: 'total', etiqueta: 'Solicitudes', numerica: true },
                            ]}
                        />
                    </SeccionPanel>

                    <SeccionPanel titulo="Recursos con mayor índice de acceso" subtitulo={hayFiltros ? 'Según los filtros actuales' : 'Contador acumulado por recurso y método'} cuerpo="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <caption className="sr-only">Recursos de la API más solicitados</caption>
                                <thead className="bg-muted text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th scope="col" className={claseTh}>
                                            Método
                                        </th>
                                        <th scope="col" className={claseTh}>
                                            Ruta
                                        </th>
                                        <th scope="col" className={claseTh}>
                                            Recurso
                                        </th>
                                        <th scope="col" className={cn(claseTh, 'text-right')}>
                                            Accesos
                                        </th>
                                        <th scope="col" className={claseTh}>
                                            Último acceso
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {(datos.recursos ?? []).map((r) => {
                                        const maximo = Math.max(1, ...(datos.recursos ?? []).map((x) => x.total));

                                        return (
                                            <tr key={`${r.metodo}-${r.recurso}-${r.ruta}`} className="hover:bg-accent/40">
                                                <td className={claseTd}>
                                                    <InsigniaMetodo metodo={r.metodo} />
                                                </td>
                                                <td className={cn(claseTd, 'font-mono text-xs break-all')}>{r.ruta}</td>
                                                <td className={cn(claseTd, 'font-mono text-xs text-muted-foreground break-all')}>{r.recurso}</td>
                                                <td className={cn(claseTd, 'w-40 text-right')}>
                                                    <span className="font-semibold tabular-nums">{numero(r.total)}</span>
                                                    <span className="mt-1 block h-1.5 overflow-hidden rounded-full bg-muted" aria-hidden="true">
                                                        <span className="block h-full rounded-full bg-chart-1" style={{ width: `${Math.max(2, (r.total / maximo) * 100)}%` }} />
                                                    </span>
                                                </td>
                                                <td className={cn(claseTd, 'text-xs whitespace-nowrap text-muted-foreground')}>{fecha(r.ultimo_acceso, true)}</td>
                                            </tr>
                                        );
                                    })}
                                    {!datos.recursos?.length && (
                                        <tr>
                                            <td colSpan={5} className="px-4 py-10 text-center text-muted-foreground">
                                                Sin solicitudes registradas.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </SeccionPanel>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <SeccionPanel titulo="Direcciones IP" subtitulo="Solicitudes por IP (las 15 más activas)">
                            <BarrasHorizontales
                                monoespaciado
                                color="var(--chart-2)"
                                vacio="Sin solicitudes registradas."
                                datos={(datos.ips ?? []).map((i) => ({ etiqueta: i.ip, total: i.total, detalle: i.ultimo ? `últ. ${fecha(i.ultimo, true)}` : undefined }))}
                            />
                        </SeccionPanel>
                        <SeccionPanel titulo="Páginas más vistas" subtitulo="Visitas a las páginas del sitio y del panel">
                            <BarrasHorizontales monoespaciado vacio="Sin visitas registradas." datos={(datos.paginas ?? []).map((p) => ({ etiqueta: p.ruta, total: p.total }))} />
                        </SeccionPanel>
                    </div>

                    <SeccionPanel titulo="Últimas solicitudes" subtitulo={`${numero(datos.registros.total)} solicitudes con los filtros actuales`} cuerpo="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <caption className="sr-only">Últimas solicitudes a la API</caption>
                                <thead className="bg-muted text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th scope="col" className={claseTh}>
                                            Fecha y hora
                                        </th>
                                        <th scope="col" className={claseTh}>
                                            Método
                                        </th>
                                        <th scope="col" className={claseTh}>
                                            Ruta
                                        </th>
                                        <th scope="col" className={claseTh}>
                                            Estado
                                        </th>
                                        <th scope="col" className={cn(claseTh, 'text-right')}>
                                            Duración
                                        </th>
                                        <th scope="col" className={claseTh}>
                                            IP
                                        </th>
                                        <th scope="col" className={claseTh}>
                                            Usuario
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {datos.registros.data.map((r) => (
                                        <tr key={r.id} className="hover:bg-accent/40">
                                            <td className={cn(claseTd, 'whitespace-nowrap text-muted-foreground')}>{fecha(r.created_at, true)}</td>
                                            <td className={claseTd}>
                                                <InsigniaMetodo metodo={r.metodo} />
                                            </td>
                                            <td className={claseTd}>
                                                <span className="block font-mono text-xs break-all">{r.ruta}</span>
                                                <span className="block font-mono text-[11px] text-muted-foreground">{r.recurso}</span>
                                            </td>
                                            <td className={claseTd}>
                                                <InsigniaHttp estado={r.estado} />
                                            </td>
                                            <td className={cn(claseTd, 'text-right whitespace-nowrap tabular-nums')}>{numero(r.duracion_ms)} ms</td>
                                            <td className={cn(claseTd, 'font-mono text-xs whitespace-nowrap')}>
                                                {r.ip ? (
                                                    <button type="button" className="hover:text-primary hover:underline" onClick={() => setIp(r.ip ?? '')} title="Filtrar por esta IP">
                                                        {r.ip}
                                                    </button>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td className={cn(claseTd, 'whitespace-nowrap')}>{r.usuario?.name ?? <span className="text-muted-foreground">Visitante</span>}</td>
                                        </tr>
                                    ))}
                                    {!datos.registros.data.length && (
                                        <tr>
                                            <td colSpan={7} className="px-4 py-12 text-center text-muted-foreground">
                                                No hay solicitudes{hayFiltros ? ' con estos filtros' : ''}.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <div className="border-t px-4 py-3">
                            <Paginacion datos={datos.registros} />
                        </div>
                    </SeccionPanel>
                </div>
            )}
        </div>
    );
}

/* ------------------------------------------------------------------ Página */

function ContenidoBitacora() {
    const { valores: consulta, cambiar, reemplazar } = useParametrosUrl();
    const pedida = consulta.pestana;
    const activa: IdPestana = PESTANAS.some((p) => p.id === pedida) ? (pedida as IdPestana) : 'acciones';

    // Cada pestaña tiene sus propios filtros: al cambiar de pestaña se reinician.
    const irA = (id: string) => reemplazar(id === 'acciones' ? {} : { pestana: id });
    const limpiar = () => reemplazar(activa === 'acciones' ? {} : { pestana: activa });

    return (
        <div className="space-y-5">
            <EncabezadoPagina
                titulo="Bitácora y accesos"
                descripcion="Auditoría de las acciones realizadas en el panel, de los inicios de sesión (exitosos y fallidos) y del tráfico de la API por IP y recurso."
            />

            <Pestanas
                pestanas={PESTANAS.map((p) => {
                    const IconoPestana = p.icono;

                    return {
                        id: p.id,
                        titulo: (
                            <>
                                <IconoPestana className="size-4" aria-hidden="true" /> {p.texto}
                            </>
                        ),
                    };
                })}
                activa={activa}
                onCambiar={irA}
            >
                {activa === 'acciones' && <ListadoCrud uri="bitacora" conEncabezado={false} conTitulo={false} conDetalle={false} />}
                {activa === 'sesiones' && <PestanaSesiones consulta={consulta} cambiar={cambiar} limpiar={limpiar} />}
                {activa === 'trafico' && <PestanaTrafico consulta={consulta} cambiar={cambiar} limpiar={limpiar} />}
            </Pestanas>
        </div>
    );
}

export default function Bitacora() {
    useTitulo('Bitácora y accesos');

    return (
        <ConPermiso permiso="bitacora.ver">
            <ContenidoBitacora />
        </ConPermiso>
    );
}

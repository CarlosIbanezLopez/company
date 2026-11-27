import { Check, Eye, LoaderCircle, Pencil, Plus, RotateCcw, Search, Trash2, X } from 'lucide-react';
import { useId, useState } from 'react';
import { Link } from 'react-router';
import { toast } from 'sonner';
import { OpcionesSelect } from '@/components/admin/CamposCrud';
import { EncabezadoPagina } from '@/components/admin/EncabezadoPagina';
import { InsigniaEstado } from '@/components/admin/Insignia';
import { usePanel } from '@/components/admin/panel';
import { useParametrosUrl, useTextoUrl } from '@/components/admin/useParametrosUrl';
import Icono from '@/components/Icono';
import { ErrorCarga, Esqueleto } from '@/components/ui/Cargando';
import { Confirmar } from '@/components/ui/Modal';
import { Paginacion } from '@/components/ui/Paginacion';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { api, descargarArchivo, mensajeError } from '@/lib/api';
import { fecha, valorDe } from '@/lib/formato';
import { cn } from '@/lib/utils';
import type { AccionCrud, Columna, ConfigCrud, Fila, RespuestaCrudIndex } from '@/types';

/** Parámetros de la URL que no son filtros del listado. */
const NO_FILTROS = ['page', 'pestana'];

const vacio = (v: unknown) => v === null || v === undefined || v === '';

function texto(fila: Fila, clave: string): string {
    const v = valorDe(fila, clave);

    return vacio(v) ? '—' : String(v);
}

/** Nombre legible de una fila para los textos accesibles ("Editar «Noticia X»"). */
function nombreFila(fila: Fila, columnas: Columna[]): string {
    const columna = columnas.find((c) => !c.tipo || c.tipo === 'text');

    return columna ? texto(fila, columna.clave) : `#${fila.id}`;
}

const urlFila = (url: string, id: number) => url.replaceAll('__ID__', String(id));

const esExterna = (url: string) => /^https?:\/\//i.test(url);

/** Celda según el tipo de columna (image, bool, date, datetime, badge, link o texto). */
function Celda({ fila, columna }: { fila: Fila; columna: Columna }) {
    const v = valorDe(fila, columna.clave);

    switch (columna.tipo) {
        case 'image':
            return vacio(v) ? (
                <span className="block size-12 rounded-lg border bg-muted" aria-hidden="true" />
            ) : (
                <img src={String(v)} alt="" loading="lazy" className="size-12 rounded-lg border object-cover" />
            );
        case 'bool':
            return v === true || v === 1 || v === '1' ? (
                <span className="inline-flex items-center gap-1 text-exito">
                    <Check className="size-4" aria-hidden="true" /> Sí
                </span>
            ) : (
                <span className="inline-flex items-center gap-1 text-muted-foreground">
                    <X className="size-4" aria-hidden="true" /> No
                </span>
            );
        case 'date':
            return <span className="whitespace-nowrap">{vacio(v) ? '—' : fecha(String(v))}</span>;
        case 'datetime':
            return <span className="whitespace-nowrap">{vacio(v) ? '—' : fecha(String(v), true)}</span>;
        case 'badge':
            return <InsigniaEstado valor={v} />;
        case 'link': {
            if (vacio(v)) {
                return <span className="text-muted-foreground">—</span>;
            }

            const url = String(v);

            return esExterna(url) || !url.startsWith('/') ? (
                <a href={url} target="_blank" rel="noopener noreferrer" className="font-medium text-primary hover:underline">
                    Abrir<span className="sr-only"> (se abre en una pestaña nueva)</span>
                </a>
            ) : (
                <Link to={url} className="font-medium text-primary hover:underline">
                    Abrir
                </Link>
            );
        }
        default:
            return <span className="line-clamp-2 max-w-md break-words">{texto(fila, columna.clave)}</span>;
    }
}

function EsqueletoListado({ conEncabezado }: { conEncabezado: boolean }) {
    return (
        <div className="space-y-5" aria-busy="true">
            {conEncabezado && (
                <div className="border-b pb-4">
                    <Esqueleto className="h-1 w-10" />
                    <Esqueleto className="mt-3 h-7 w-64" />
                    <Esqueleto className="mt-2 h-4 w-96 max-w-full" />
                </div>
            )}
            <Esqueleto className="h-14 w-full" />
            <div className="tarjeta divide-y">
                {Array.from({ length: 6 }, (_, i) => (
                    <div key={i} className="flex items-center gap-4 px-4 py-3">
                        <Esqueleto className="h-4 flex-1" />
                        <Esqueleto className="h-4 w-24" />
                        <Esqueleto className="h-4 w-16" />
                    </div>
                ))}
            </div>
            <span className="sr-only" role="status">
                Cargando registros…
            </span>
        </div>
    );
}

function TituloPestana({ titulo }: { titulo: string }) {
    useTitulo(titulo);

    return null;
}

const claseBotonFila = 'inline-flex size-8 items-center justify-center rounded-lg border bg-card transition-colors';

interface PropsListadoCrud {
    /** Segmento de la API y de la SPA: /api/admin/{uri} y /admin/{uri}. */
    uri: string;
    /** Muestra el encabezado (título, descripción y botones). */
    conEncabezado?: boolean;
    /** Cambia el título de la pestaña del navegador con el título del módulo. */
    conTitulo?: boolean;
    /** Sin permiso de edición, ofrece "Ver" (formulario de solo lectura). Desactivar si el módulo no tiene formulario. */
    conDetalle?: boolean;
}

/**
 * Listado genérico del panel (GET /api/admin/{uri}): búsqueda con espera, filtros sincronizados
 * con la URL, columnas por tipo, paginación, acciones extra y botones crear/editar/eliminar
 * según la matriz de acceso (config.permisos).
 */
export function ListadoCrud({ uri, conEncabezado = true, conTitulo = true, conDetalle = true }: PropsListadoCrud) {
    const { valores: consulta, cambiar } = useParametrosUrl();
    const { datos, cargando, error, estado, recargar } = useApi<RespuestaCrudIndex>(`admin/${uri}`, consulta);
    const [busqueda, setBusqueda] = useTextoUrl('q', consulta.q ?? '', cambiar);
    const [porEliminar, setPorEliminar] = useState<Fila | null>(null);
    const [eliminando, setEliminando] = useState(false);
    const [descargando, setDescargando] = useState<string | null>(null);
    const { recargarMenu } = usePanel();
    const id = useId();

    if (!datos) {
        return error ? <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} /> : <EsqueletoListado conEncabezado={conEncabezado} />;
    }

    const { config, columnas, filtros, registros } = datos;
    const permisos: ConfigCrud['permisos'] = config.permisos ?? { crear: false, editar: false, eliminar: false };
    const accionesFila = config.accionesFila ?? [];
    const verDetalle = conDetalle && !permisos.editar;
    const conAcciones = permisos.editar || permisos.eliminar || verDetalle || accionesFila.length > 0;
    const filtrosActivos = Object.entries(consulta).some(([clave, valor]) => !NO_FILTROS.includes(clave) && valor !== '');
    const filasDatos = registros?.data ?? [];

    const limpiar = () => {
        setBusqueda('');
        cambiar(Object.fromEntries(Object.keys(consulta).filter((k) => !NO_FILTROS.includes(k)).map((k) => [k, null])));
    };

    const descargar = async (accion: AccionCrud) => {
        setDescargando(accion.url);

        try {
            const filtrosActuales = Object.fromEntries(Object.entries(consulta).filter(([clave, valor]) => !NO_FILTROS.includes(clave) && valor !== ''));
            await descargarArchivo(accion.url, filtrosActuales, `${uri}.csv`);
            toast.success('Descarga iniciada.');
        } catch (e) {
            toast.error(mensajeError(e));
        } finally {
            setDescargando(null);
        }
    };

    const eliminar = async () => {
        if (!porEliminar) {
            return;
        }

        setEliminando(true);

        try {
            const { data } = await api.delete<{ message?: string }>(`admin/${uri}/${porEliminar.id}`);
            toast.success(data?.message ?? 'Registro eliminado.');

            if (filasDatos.length === 1 && registros.current_page > 1) {
                cambiar({ page: String(registros.current_page - 1) });
            } else {
                recargar();
            }

            if (uri === 'menu') {
                recargarMenu();
            }
        } catch (e) {
            toast.error(mensajeError(e));
        } finally {
            setEliminando(false);
            setPorEliminar(null);
        }
    };

    const botonAccion = (accion: AccionCrud, enFila?: Fila) => {
        const url = enFila ? urlFila(accion.url, enFila.id) : accion.url;
        const clase = enFila
            ? 'inline-flex h-8 items-center gap-1.5 rounded-lg border bg-card px-2.5 text-xs font-medium transition-colors hover:border-primary hover:text-primary disabled:opacity-50'
            : 'boton-borde';
        const icono = <Icono nombre={accion.icono ?? (accion.tipo === 'descarga' ? 'download' : null)} className={enFila ? 'size-3.5' : 'size-4'} />;

        if (accion.tipo === 'descarga') {
            const ocupado = descargando === url;

            return (
                <button key={`${accion.etiqueta}-${url}`} type="button" className={clase} disabled={ocupado} onClick={() => void descargar({ ...accion, url })}>
                    {ocupado ? <LoaderCircle className={cn('animate-spin', enFila ? 'size-3.5' : 'size-4')} aria-hidden="true" /> : icono}
                    {accion.etiqueta}
                </button>
            );
        }

        return esExterna(url) ? (
            <a key={`${accion.etiqueta}-${url}`} href={url} target="_blank" rel="noopener noreferrer" className={clase}>
                {icono}
                {accion.etiqueta}
            </a>
        ) : (
            <Link key={`${accion.etiqueta}-${url}`} to={url} className={clase}>
                {icono}
                {accion.etiqueta}
            </Link>
        );
    };

    return (
        <div className="space-y-5">
            {conTitulo && <TituloPestana titulo={config.titulo} />}

            {conEncabezado && (
                <EncabezadoPagina
                    titulo={config.titulo}
                    descripcion={config.descripcion}
                    acciones={
                        ((config.acciones ?? []).length > 0 || permisos.crear) && (
                            <>
                                {(config.acciones ?? []).map((a) => botonAccion(a))}
                                {permisos.crear && (
                                    <Link to={`/admin/${uri}/crear`} className="boton">
                                        <Plus className="size-4" aria-hidden="true" /> {config.femenino ? 'Nueva' : 'Nuevo'} {config.singular}
                                    </Link>
                                )}
                            </>
                        )
                    }
                />
            )}

            {(datos.buscar || filtros.length > 0) && (
                <div role="search" aria-label={`Buscar y filtrar ${config.titulo}`} className="tarjeta flex flex-wrap items-end gap-3 p-3 print:hidden">
                    {datos.buscar && (
                        <div className="relative min-w-56 flex-1">
                            <label htmlFor={`${id}-q`} className="sr-only">
                                Buscar en {config.titulo}
                            </label>
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                            <input
                                id={`${id}-q`}
                                type="search"
                                value={busqueda}
                                onChange={(e) => setBusqueda(e.target.value)}
                                placeholder="Buscar…"
                                className="campo pl-9"
                                autoComplete="off"
                            />
                        </div>
                    )}
                    {filtros.map((f) => (
                        <div key={f.clave} className="min-w-40 flex-1 sm:flex-none">
                            <label htmlFor={`${id}-${f.clave}`} className="mb-1 block text-xs font-medium text-muted-foreground">
                                {f.etiqueta}
                            </label>
                            {f.tipo === 'date' ? (
                                <input
                                    id={`${id}-${f.clave}`}
                                    type="date"
                                    value={consulta[f.clave] ?? ''}
                                    onChange={(e) => cambiar({ [f.clave]: e.target.value || null })}
                                    className="campo"
                                />
                            ) : (
                                <select id={`${id}-${f.clave}`} value={consulta[f.clave] ?? ''} onChange={(e) => cambiar({ [f.clave]: e.target.value || null })} className="campo">
                                    <option value="">Todos</option>
                                    <OpcionesSelect opciones={f.opciones} />
                                </select>
                            )}
                        </div>
                    ))}
                    {filtrosActivos && (
                        <button type="button" className="boton-borde" onClick={limpiar}>
                            <X className="size-4" aria-hidden="true" /> Limpiar
                        </button>
                    )}
                </div>
            )}

            {error && (
                <div role="alert" className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                    <span>{error}</span>
                    <button type="button" className="boton-borde h-8" onClick={recargar}>
                        <RotateCcw className="size-4" aria-hidden="true" /> Reintentar
                    </button>
                </div>
            )}

            <div className={cn('tarjeta overflow-hidden transition-opacity', cargando && 'opacity-60')} aria-busy={cargando}>
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <caption className="sr-only">
                            {config.titulo}
                            {registros ? `: ${registros.total} registros` : ''}
                        </caption>
                        <thead className="bg-muted text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                {columnas.map((c) => (
                                    <th key={c.clave} scope="col" className="px-4 py-3 font-semibold whitespace-nowrap">
                                        {c.etiqueta}
                                    </th>
                                ))}
                                {conAcciones && (
                                    <th scope="col" className="w-1 px-4 py-3 text-right font-semibold">
                                        Acciones
                                    </th>
                                )}
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {filasDatos.map((fila) => {
                                const nombre = nombreFila(fila, columnas);

                                return (
                                    <tr key={fila.id} className="hover:bg-accent/50">
                                        {columnas.map((c) => (
                                            <td key={c.clave} className="px-4 py-3 align-middle">
                                                <Celda fila={fila} columna={c} />
                                            </td>
                                        ))}
                                        {conAcciones && (
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-1">
                                                    {accionesFila.map((a) => botonAccion(a, fila))}
                                                    {permisos.editar && (
                                                        <Link
                                                            to={`/admin/${uri}/${fila.id}/editar`}
                                                            className={cn(claseBotonFila, 'hover:border-primary hover:text-primary')}
                                                            title={`Editar ${config.singular}`}
                                                            aria-label={`Editar «${nombre}»`}
                                                        >
                                                            <Pencil className="size-3.5" aria-hidden="true" />
                                                        </Link>
                                                    )}
                                                    {verDetalle && (
                                                        <Link
                                                            to={`/admin/${uri}/${fila.id}/editar`}
                                                            className={cn(claseBotonFila, 'hover:border-primary hover:text-primary')}
                                                            title={`Ver ${config.singular}`}
                                                            aria-label={`Ver «${nombre}»`}
                                                        >
                                                            <Eye className="size-3.5" aria-hidden="true" />
                                                        </Link>
                                                    )}
                                                    {permisos.eliminar && (
                                                        <button
                                                            type="button"
                                                            className={cn(claseBotonFila, 'hover:border-destructive hover:bg-destructive hover:text-destructive-foreground')}
                                                            title={`Eliminar ${config.singular}`}
                                                            aria-label={`Eliminar «${nombre}»`}
                                                            onClick={() => setPorEliminar(fila)}
                                                        >
                                                            <Trash2 className="size-3.5" aria-hidden="true" />
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                );
                            })}
                            {!filasDatos.length && (
                                <tr>
                                    <td colSpan={columnas.length + (conAcciones ? 1 : 0)} className="px-4 py-12 text-center text-muted-foreground">
                                        {filtrosActivos ? 'No hay registros con estos filtros.' : 'Todavía no hay registros.'}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {registros && <Paginacion datos={registros} />}

            <Confirmar
                abierto={porEliminar !== null}
                titulo={`¿Eliminar ${config.singular}?`}
                mensaje={
                    <>
                        Se eliminará «{porEliminar ? nombreFila(porEliminar, columnas) : ''}». Esta acción queda registrada en la bitácora y no se puede deshacer.
                    </>
                }
                textoConfirmar="Eliminar"
                procesando={eliminando}
                onConfirmar={() => void eliminar()}
                onCancelar={() => !eliminando && setPorEliminar(null)}
            />
        </div>
    );
}

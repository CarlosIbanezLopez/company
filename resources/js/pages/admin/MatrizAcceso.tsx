import { Check, Eye, Minus, Pencil, Plus, ShieldCheck, Trash2, X } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Fragment, useId, useMemo, useState } from 'react';
import { Link } from 'react-router';
import { EncabezadoPagina } from '@/components/admin/EncabezadoPagina';
import { Insignia } from '@/components/admin/Insignia';
import { nombreAccion } from '@/components/admin/MatrizPermisos';
import { ConPermiso } from '@/components/Guardias';
import { ErrorCarga, Esqueleto } from '@/components/ui/Cargando';
import { useAuth } from '@/contexts/AuthContext';
import { useApi } from '@/hooks/useApi';
import { useTitulo } from '@/hooks/useTitulo';
import { cn } from '@/lib/utils';

/** GET /api/admin/roles/matriz: roles × recursos × acciones (solo lectura). */
interface CeldaMatriz {
    id: number;
    roles: number[];
}

interface RolMatriz {
    id: number;
    nombre: string;
    acceso_panel: boolean;
    es_admin: boolean;
}

interface RespuestaMatriz {
    acciones: string[];
    roles: RolMatriz[];
    filas: { recurso: string; etiqueta: string; modulo: string; permisos: Record<string, CeldaMatriz | null> }[];
}

const ICONO_ACCION: Record<string, LucideIcon> = { ver: Eye, crear: Plus, editar: Pencil, eliminar: Trash2 };

/** Íconos de las acciones de un rol sobre un recurso (resaltado = permitido, tenue = no permitido). */
function AccionesRol({ acciones, permisos, rolId }: { acciones: string[]; permisos: Record<string, CeldaMatriz | null>; rolId: number }) {
    const permitidas = acciones.filter((a) => permisos[a]?.roles.includes(rolId));
    const negadas = acciones.filter((a) => permisos[a] && !permisos[a]?.roles.includes(rolId));

    return (
        <span className="inline-flex items-center gap-1">
            {acciones.map((a) => {
                const IconoAccion = ICONO_ACCION[a] ?? Check;
                const celda = permisos[a];

                if (!celda) {
                    return <span key={a} className="inline-block size-6" aria-hidden="true" />;
                }

                const tiene = celda.roles.includes(rolId);

                return (
                    <span
                        key={a}
                        aria-hidden="true"
                        title={`${nombreAccion(a)}: ${tiene ? 'permitido' : 'no permitido'}`}
                        className={cn('inline-flex size-6 items-center justify-center rounded-md border', tiene ? 'border-exito/40 bg-exito/15 text-exito' : 'border-transparent text-muted-foreground/40')}
                    >
                        <IconoAccion className="size-3.5" />
                    </span>
                );
            })}
            <span className="sr-only">
                {permitidas.length ? `Permitido: ${permitidas.map(nombreAccion).join(', ')}.` : 'Sin permisos.'}
                {negadas.length ? ` No permitido: ${negadas.map(nombreAccion).join(', ')}.` : ''}
            </span>
        </span>
    );
}

function ContenidoMatriz() {
    const { usuario, puede } = useAuth();
    const { datos, error, estado, recargar } = useApi<RespuestaMatriz>('admin/roles/matriz');
    const [rolElegido, setRolElegido] = useState('');
    const id = useId();

    const grupos = useMemo(() => {
        const mapa = new Map<string, RespuestaMatriz['filas']>();

        for (const fila of datos?.filas ?? []) {
            mapa.set(fila.modulo || 'General', [...(mapa.get(fila.modulo || 'General') ?? []), fila]);
        }

        return [...mapa.entries()];
    }, [datos]);

    if (!datos) {
        return error ? (
            <ErrorCarga mensaje={error} estado={estado} onReintentar={recargar} />
        ) : (
            <div className="space-y-3" aria-busy="true">
                <Esqueleto className="h-12 w-full" />
                <Esqueleto className="h-96 w-full" />
            </div>
        );
    }

    const acciones = datos.acciones?.length ? datos.acciones : ['ver', 'crear', 'editar', 'eliminar'];
    const esAdministrador = usuario?.rol === 'Administrador';
    const puedeEditarRol = (rol: RolMatriz) => puede('roles.editar') && (esAdministrador || (!rol.es_admin && rol.nombre !== usuario?.rol));
    const totalPermisos = datos.filas.reduce((n, f) => n + acciones.filter((a) => f.permisos[a]).length, 0);
    const permisosDe = (rol: RolMatriz) => datos.filas.reduce((n, f) => n + acciones.filter((a) => f.permisos[a]?.roles.includes(rol.id)).length, 0);
    const roles = rolElegido ? datos.roles.filter((r) => String(r.id) === rolElegido) : datos.roles;
    const unRol = rolElegido ? roles[0] : null;

    return (
        <div className="space-y-5">
            <div className="tarjeta flex flex-wrap items-end justify-between gap-4 p-3">
                <div className="min-w-56">
                    <label htmlFor={`${id}-rol`} className="mb-1 block text-xs font-medium text-muted-foreground">
                        Mostrar
                    </label>
                    <select id={`${id}-rol`} className="campo" value={rolElegido} onChange={(e) => setRolElegido(e.target.value)}>
                        <option value="">Todos los roles</option>
                        {datos.roles.map((r) => (
                            <option key={r.id} value={String(r.id)}>
                                {r.nombre}
                            </option>
                        ))}
                    </select>
                </div>
                <ul className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground" aria-label="Leyenda">
                    {acciones.map((a) => {
                        const IconoAccion = ICONO_ACCION[a] ?? Check;

                        return (
                            <li key={a} className="inline-flex items-center gap-1.5">
                                <span className="inline-flex size-5 items-center justify-center rounded-md border border-exito/40 bg-exito/15 text-exito" aria-hidden="true">
                                    <IconoAccion className="size-3" />
                                </span>
                                {nombreAccion(a)}
                            </li>
                        );
                    })}
                    <li className="inline-flex items-center gap-1.5">
                        <span className="inline-flex size-5 items-center justify-center rounded-md text-muted-foreground/40" aria-hidden="true">
                            <Eye className="size-3" />
                        </span>
                        No permitido
                    </li>
                </ul>
            </div>

            {/* Resumen por rol */}
            <ul className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                {roles.map((rol) => (
                    <li key={rol.id} className="tarjeta flex items-center justify-between gap-3 p-4">
                        <div className="min-w-0">
                            <p className="flex flex-wrap items-center gap-2 font-semibold">
                                {rol.nombre}
                                {rol.es_admin && (
                                    <Insignia tono="marca">
                                        <ShieldCheck className="size-3.5" aria-hidden="true" /> Acceso total
                                    </Insignia>
                                )}
                            </p>
                            <p className="mt-0.5 text-xs text-muted-foreground">
                                {rol.es_admin ? `${totalPermisos} de ${totalPermisos}` : `${permisosDe(rol)} de ${totalPermisos}`} permisos ·{' '}
                                {rol.acceso_panel ? 'Ingresa al panel' : 'Sin acceso al panel'}
                            </p>
                        </div>
                        {puedeEditarRol(rol) && (
                            <Link to={`/admin/roles/${rol.id}/editar`} className="boton-borde h-8 shrink-0 px-3 text-xs">
                                <Pencil className="size-3.5" aria-hidden="true" /> Editar<span className="sr-only"> el rol {rol.nombre}</span>
                            </Link>
                        )}
                    </li>
                ))}
            </ul>

            <div className="tarjeta overflow-hidden">
                <div className="overflow-x-auto">
                    {unRol ? (
                        <table className="w-full min-w-[32rem] text-sm">
                            <caption className="sr-only">Permisos del rol {unRol.nombre} por recurso y acción</caption>
                            <thead className="bg-muted text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th scope="col" className="px-4 py-3 text-left font-semibold">
                                        Recurso
                                    </th>
                                    {acciones.map((a) => (
                                        <th key={a} scope="col" className="w-24 px-3 py-3 text-center font-semibold">
                                            {nombreAccion(a)}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {grupos.map(([modulo, filas]) => (
                                    <Fragment key={modulo}>
                                        <tr className="border-t bg-muted/40">
                                            <th scope="colgroup" colSpan={acciones.length + 1} className="px-4 py-1.5 text-left text-xs font-bold tracking-wide uppercase">
                                                {modulo}
                                            </th>
                                        </tr>
                                        {filas.map((f) => (
                                            <tr key={f.recurso} className="border-t hover:bg-accent/40">
                                                <th scope="row" className="px-4 py-2 text-left font-medium">
                                                    {f.etiqueta}
                                                    <span className="block font-mono text-[11px] font-normal text-muted-foreground">{f.recurso}</span>
                                                </th>
                                                {acciones.map((a) => {
                                                    const celda = f.permisos[a];
                                                    const tiene = celda?.roles.includes(unRol.id);

                                                    return (
                                                        <td key={a} className="px-3 py-2 text-center">
                                                            {!celda ? (
                                                                <span className="text-muted-foreground" title="No aplica">
                                                                    <Minus className="mx-auto size-4" aria-hidden="true" />
                                                                    <span className="sr-only">No aplica</span>
                                                                </span>
                                                            ) : tiene ? (
                                                                <span className="inline-flex size-7 items-center justify-center rounded-md bg-exito/15 text-exito" title="Permitido">
                                                                    <Check className="size-4" aria-hidden="true" />
                                                                    <span className="sr-only">Permitido</span>
                                                                </span>
                                                            ) : (
                                                                <span className="inline-flex size-7 items-center justify-center rounded-md text-muted-foreground/50" title="No permitido">
                                                                    <X className="size-4" aria-hidden="true" />
                                                                    <span className="sr-only">No permitido</span>
                                                                </span>
                                                            )}
                                                        </td>
                                                    );
                                                })}
                                            </tr>
                                        ))}
                                    </Fragment>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <table className="w-full min-w-[48rem] text-sm">
                            <caption className="sr-only">Matriz de control de acceso: recursos por rol</caption>
                            <thead className="bg-muted text-xs text-muted-foreground">
                                <tr>
                                    <th scope="col" className="sticky left-0 z-10 bg-muted px-4 py-3 text-left font-semibold tracking-wide uppercase">
                                        Recurso
                                    </th>
                                    {roles.map((rol) => (
                                        <th key={rol.id} scope="col" className="px-3 py-3 text-center font-semibold">
                                            <span className="block text-foreground">{rol.nombre}</span>
                                            {!rol.acceso_panel && <span className="block text-[11px] font-normal">sin panel</span>}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {grupos.map(([modulo, filas]) => (
                                    <Fragment key={modulo}>
                                        <tr className="border-t bg-muted/40">
                                            <th scope="colgroup" colSpan={roles.length + 1} className="sticky left-0 px-4 py-1.5 text-left text-xs font-bold tracking-wide uppercase">
                                                {modulo}
                                            </th>
                                        </tr>
                                        {filas.map((f) => (
                                            <tr key={f.recurso} className="border-t hover:bg-accent/40">
                                                <th scope="row" className="sticky left-0 z-10 bg-card px-4 py-2 text-left font-medium">
                                                    {f.etiqueta}
                                                    <span className="block font-mono text-[11px] font-normal text-muted-foreground">{f.recurso}</span>
                                                </th>
                                                {roles.map((rol) => (
                                                    <td key={rol.id} className="px-3 py-2 text-center whitespace-nowrap">
                                                        <AccionesRol acciones={acciones} permisos={f.permisos} rolId={rol.id} />
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </Fragment>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </div>
    );
}

/** CU1 - Matriz de control de acceso (roles × recursos × acciones), de solo lectura. */
export default function MatrizAcceso() {
    useTitulo('Matriz de acceso');
    const { puede } = useAuth();

    return (
        <div className="space-y-5">
            <EncabezadoPagina
                titulo="Matriz de control de acceso"
                descripcion="Acciones (ver, crear, editar y eliminar) que puede realizar cada rol sobre cada recurso del panel. Para modificarla, edite el rol correspondiente."
                acciones={
                    puede('roles.ver') && (
                        <Link to="/admin/roles" className="boton-borde">
                            <ShieldCheck className="size-4" aria-hidden="true" /> Roles y permisos
                        </Link>
                    )
                }
            />
            <ConPermiso permiso="roles.ver">
                <ContenidoMatriz />
            </ConPermiso>
        </div>
    );
}

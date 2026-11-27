import { Fragment, useMemo } from 'react';
import { cn } from '@/lib/utils';
import type { Campo, FilaMatriz } from '@/types';

export const ACCIONES_PERMISO = ['ver', 'crear', 'editar', 'eliminar'];

const NOMBRE_ACCION: Record<string, string> = { ver: 'Ver', crear: 'Crear', editar: 'Editar', eliminar: 'Eliminar' };

export const nombreAccion = (accion: string): string => NOMBRE_ACCION[accion] ?? accion.charAt(0).toUpperCase() + accion.slice(1);

type Id = number | string;

interface PropsCasilla {
    marcado: boolean;
    parcial?: boolean;
    etiqueta: string;
    onCambiar: () => void;
    name?: string;
    className?: string;
}

/** Casilla con estado intermedio (fila o columna marcada parcialmente). */
function Casilla({ marcado, parcial = false, etiqueta, onCambiar, name, className }: PropsCasilla) {
    return (
        <input
            type="checkbox"
            name={name}
            checked={marcado}
            ref={(el) => {
                if (el) {
                    el.indeterminate = parcial && !marcado;
                }
            }}
            onChange={onCambiar}
            aria-label={etiqueta}
            title={etiqueta}
            className={cn('size-4 cursor-pointer accent-[var(--primary)] disabled:cursor-not-allowed', className)}
        />
    );
}

interface PropsMatriz {
    campo: Campo;
    valor: unknown;
    onCambiar: (ids: Id[]) => void;
}

/**
 * Matriz de control de acceso de un rol: recursos (filas, agrupados por módulo) × acciones (columnas).
 * Permite marcar celdas, filas completas, columnas completas o módulos. Al marcar crear, editar o
 * eliminar se marca también "ver" (necesario para entrar al módulo); al quitar "ver" se quita la fila.
 */
export function MatrizPermisos({ campo, valor, onCambiar }: PropsMatriz) {
    const acciones = campo.acciones?.length ? campo.acciones : ACCIONES_PERMISO;
    const filas = useMemo(() => campo.filas ?? [], [campo.filas]);
    const lista = useMemo<Id[]>(() => (Array.isArray(valor) ? (valor as Id[]) : []), [valor]);
    const seleccion = useMemo(() => new Set(lista.map(String)), [lista]);

    const grupos = useMemo(() => {
        const mapa = new Map<string, FilaMatriz[]>();

        for (const fila of filas) {
            const modulo = fila.modulo || 'General';
            mapa.set(modulo, [...(mapa.get(modulo) ?? []), fila]);
        }

        return [...mapa.entries()];
    }, [filas]);

    const idsDe = (de: FilaMatriz[], soloAccion?: string): Id[] =>
        de.flatMap((f) => (soloAccion ? [f.permisos[soloAccion]] : acciones.map((a) => f.permisos[a]))).filter((id): id is number => id !== null && id !== undefined);

    const todos = idsDe(filas);
    const conjuntoMatriz = new Set(todos.map(String));
    const marcados = todos.filter((id) => seleccion.has(String(id))).length;

    /** Aplica cambios conservando el orden de la matriz y los ids que no pertenecen a ella. */
    const aplicar = (marcar: Id[], desmarcar: Id[] = []) => {
        const nuevo = new Set(seleccion);
        desmarcar.forEach((id) => nuevo.delete(String(id)));
        marcar.forEach((id) => nuevo.add(String(id)));
        const fuera = lista.filter((id) => !conjuntoMatriz.has(String(id)));
        onCambiar([...fuera, ...todos.filter((id) => nuevo.has(String(id)))]);
    };

    const estado = (ids: Id[]) => {
        const n = ids.filter((id) => seleccion.has(String(id))).length;

        return { marcado: ids.length > 0 && n === ids.length, parcial: n > 0 && n < ids.length };
    };

    /** "ver" de las filas indicadas (dependencia de las demás acciones). */
    const verDe = (de: FilaMatriz[]) => idsDe(de, 'ver');

    const alternarCelda = (fila: FilaMatriz, accion: string) => {
        const id = fila.permisos[accion];

        if (id === null || id === undefined) {
            return;
        }

        if (seleccion.has(String(id))) {
            aplicar([], accion === 'ver' ? idsDe([fila]) : [id]);
        } else {
            aplicar(accion === 'ver' ? [id] : [id, ...verDe([fila])]);
        }
    };

    const alternarGrupo = (de: FilaMatriz[], accion?: string) => {
        const ids = idsDe(de, accion);
        const { marcado } = estado(ids);

        if (marcado) {
            aplicar([], accion === 'ver' ? idsDe(de) : ids);
        } else {
            aplicar(accion && accion !== 'ver' ? [...ids, ...verDe(de)] : ids);
        }
    };

    if (!filas.length) {
        return <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">No hay recursos definidos en la matriz de acceso.</p>;
    }

    const totalGeneral = estado(todos);

    return (
        <div>
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full min-w-[34rem] text-sm">
                    <caption className="sr-only">{campo.etiqueta}: recursos por acciones permitidas</caption>
                    <thead className="bg-muted text-xs text-muted-foreground">
                        <tr>
                            <th scope="col" className="px-3 py-2 text-left font-semibold tracking-wide uppercase">
                                Recurso
                            </th>
                            {acciones.map((a) => {
                                const columna = estado(idsDe(filas, a));

                                return (
                                    <th key={a} scope="col" className="w-20 px-2 py-2 text-center font-semibold tracking-wide uppercase">
                                        <span className="flex flex-col items-center gap-1">
                                            {nombreAccion(a)}
                                            <Casilla {...columna} etiqueta={`${nombreAccion(a)}: marcar o quitar en todos los recursos`} onCambiar={() => alternarGrupo(filas, a)} />
                                        </span>
                                    </th>
                                );
                            })}
                            <th scope="col" className="w-20 px-2 py-2 text-center font-semibold tracking-wide uppercase">
                                <span className="flex flex-col items-center gap-1">
                                    Todos
                                    <Casilla {...totalGeneral} etiqueta="Marcar o quitar todos los permisos" onCambiar={() => alternarGrupo(filas)} />
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {grupos.map(([modulo, deModulo]) => (
                            <Fragment key={modulo}>
                                <tr className="border-t bg-muted/40">
                                    <th scope="colgroup" colSpan={acciones.length + 1} className="px-3 py-1.5 text-left text-xs font-bold tracking-wide uppercase">
                                        {modulo}
                                    </th>
                                    <td className="px-2 py-1.5 text-center">
                                        <Casilla {...estado(idsDe(deModulo))} etiqueta={`Todos los permisos del módulo ${modulo}`} onCambiar={() => alternarGrupo(deModulo)} />
                                    </td>
                                </tr>
                                {deModulo.map((fila) => (
                                    <tr key={fila.recurso} className="border-t hover:bg-accent/40">
                                        <th scope="row" className="px-3 py-2 text-left font-medium">
                                            {fila.etiqueta}
                                            <span className="block font-mono text-[11px] font-normal text-muted-foreground">{fila.recurso}</span>
                                        </th>
                                        {acciones.map((a) => {
                                            const id = fila.permisos[a];

                                            return (
                                                <td key={a} className="px-2 py-2 text-center">
                                                    {id === null || id === undefined ? (
                                                        <span className="text-muted-foreground" title="No aplica">
                                                            <span aria-hidden="true">—</span>
                                                            <span className="sr-only">{`${nombreAccion(a)}: no aplica`}</span>
                                                        </span>
                                                    ) : (
                                                        <Casilla
                                                            name={campo.nombre}
                                                            marcado={seleccion.has(String(id))}
                                                            etiqueta={`${nombreAccion(a)} — ${fila.etiqueta}`}
                                                            onCambiar={() => alternarCelda(fila, a)}
                                                        />
                                                    )}
                                                </td>
                                            );
                                        })}
                                        <td className="px-2 py-2 text-center">
                                            <Casilla {...estado(idsDe([fila]))} etiqueta={`Todos los permisos de ${fila.etiqueta}`} onCambiar={() => alternarGrupo([fila])} />
                                        </td>
                                    </tr>
                                ))}
                            </Fragment>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                <span aria-live="polite">
                    {marcados} de {todos.length} permisos seleccionados.
                </span>
                <span className="flex gap-3">
                    <button type="button" className="font-medium text-primary hover:underline disabled:opacity-50" onClick={() => aplicar(todos)}>
                        Marcar todos
                    </button>
                    <button type="button" className="font-medium text-muted-foreground hover:underline disabled:opacity-50" onClick={() => aplicar([], todos)}>
                        Quitar todos
                    </button>
                </span>
            </div>
        </div>
    );
}

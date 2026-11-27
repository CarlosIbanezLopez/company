import { ChevronRight, Globe, PanelLeftClose, PanelLeftOpen, RotateCcw } from 'lucide-react';
import { useEffect, useId, useState } from 'react';
import type { ReactNode } from 'react';
import { Link } from 'react-router';
import { LogoCarrera } from '@/components/admin/Logo';
import Icono from '@/components/Icono';
import { Esqueleto } from '@/components/ui/Cargando';
import { urlSitio } from '@/lib/config';
import { cn } from '@/lib/utils';
import type { GrupoMenu, ItemMenu } from '@/types';

const esExterna = (url: string) => /^https?:\/\//i.test(url);
const sinConsulta = (url: string) => url.split(/[?#]/)[0];

export interface UbicacionMenu {
    /** Ruta (sin query) de la opción activa. */
    url: string;
    grupo: GrupoMenu;
    /** null cuando la opción activa es el propio grupo (p. ej. Inicio o Reportes). */
    item: ItemMenu | null;
}

/** Opción del menú que corresponde a la ruta actual: la coincidencia más larga (exacta o por prefijo). */
export function ubicacionEnMenu(menu: GrupoMenu[] | null | undefined, ruta: string): UbicacionMenu | null {
    let mejor: UbicacionMenu | null = null;

    for (const grupo of menu ?? []) {
        const candidatos: [string | null, ItemMenu | null][] = [[grupo.url, null], ...grupo.items.map((item): [string | null, ItemMenu] => [item.url, item])];

        for (const [url, item] of candidatos) {
            if (!url || esExterna(url)) {
                continue;
            }

            const base = sinConsulta(url);
            const coincide = ruta === base || (base !== '/admin' && ruta.startsWith(`${base}/`));

            if (coincide && (!mejor || base.length > mejor.url.length)) {
                mejor = { url: base, grupo, item };
            }
        }
    }

    return mejor;
}

const claseItem = (colapsado: boolean, activo = false) =>
    cn(
        'relative flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm font-medium text-sidebar-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
        colapsado && 'justify-center px-0',
        activo &&
            'bg-sidebar-accent font-semibold text-sidebar-accent-foreground before:absolute before:inset-y-1.5 before:left-0 before:w-1 before:rounded-full before:bg-marca',
    );

/** Enlace a una ruta de la SPA o a un sitio externo (pestaña nueva). */
function Enlace({ url, className, activo, titulo, children }: { url: string; className: string; activo: boolean; titulo?: string; children: ReactNode }) {
    if (esExterna(url)) {
        return (
            <a href={url} target="_blank" rel="noopener noreferrer" className={className} title={titulo}>
                {children}
                <span className="sr-only"> (se abre en una pestaña nueva)</span>
            </a>
        );
    }

    return (
        <Link to={url} className={className} aria-current={activo ? 'page' : undefined} title={titulo}>
            {children}
        </Link>
    );
}

interface PropsGrupo {
    grupo: GrupoMenu;
    abierto: boolean;
    activa: string | null;
    colapsado: boolean;
    onAlternar: () => void;
    onExpandir: () => void;
}

/** Grupo colapsable del menú (p. ej. "Difusión" con Noticias, Eventos...). */
function GrupoLateral({ grupo, abierto, activa, colapsado, onAlternar, onExpandir }: PropsGrupo) {
    const id = useId();
    const contieneActiva = grupo.items.some((i) => i.url && sinConsulta(i.url) === activa);

    if (colapsado) {
        return (
            <button type="button" className={claseItem(true, contieneActiva)} title={grupo.titulo} aria-label={`${grupo.titulo}: mostrar opciones`} onClick={onExpandir}>
                <Icono nombre={grupo.icono} className={cn('size-4 shrink-0', contieneActiva && 'text-marca')} />
            </button>
        );
    }

    return (
        <>
            <button type="button" aria-expanded={abierto} aria-controls={id} onClick={onAlternar} className={cn(claseItem(false), contieneActiva && 'font-semibold')}>
                <Icono nombre={grupo.icono} className={cn('size-4 shrink-0', contieneActiva && 'text-marca')} />
                <span className="min-w-0 flex-1 truncate">{grupo.titulo}</span>
                <ChevronRight className={cn('size-4 shrink-0 opacity-70 transition-transform duration-200', abierto && 'rotate-90')} aria-hidden="true" />
            </button>
            <ul id={id} hidden={!abierto} className="mt-0.5 mb-1.5 ml-[1.15rem] space-y-0.5 border-l border-sidebar-border pl-2">
                {grupo.items.map((item) => {
                    const activo = Boolean(item.url) && sinConsulta(item.url ?? '') === activa;

                    return (
                        <li key={`${item.titulo}-${item.url}`}>
                            {item.url && (
                                <Enlace
                                    url={item.url}
                                    activo={activo}
                                    className={cn(
                                        'relative block truncate rounded-lg px-2.5 py-1.5 text-sm text-sidebar-foreground/90 transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                        activo &&
                                            'bg-sidebar-accent font-semibold text-sidebar-accent-foreground before:absolute before:inset-y-1 before:-left-[9px] before:w-0.5 before:bg-marca',
                                    )}
                                >
                                    {item.titulo}
                                </Enlace>
                            )}
                        </li>
                    );
                })}
            </ul>
        </>
    );
}

interface PropsMenuLateral {
    menu: GrupoMenu[] | null;
    cargando: boolean;
    error: string | null;
    onReintentar: () => void;
    ubicacion: UbicacionMenu | null;
    /** Solo íconos (barra lateral contraída en escritorio). */
    colapsado?: boolean;
    /** Expande la barra contraída (al pulsar un grupo). */
    onExpandir?: () => void;
    /** Botón para contraer/expandir la barra (solo escritorio). */
    onAlternarColapso?: () => void;
}

/**
 * Contenido de la barra lateral del panel: identidad, menú dinámico cargado desde la base de datos
 * (GET /api/menu, filtrado por los permisos del rol) con grupos colapsables y la opción activa resaltada.
 */
export function MenuLateral({ menu, cargando, error, onReintentar, ubicacion, colapsado = false, onExpandir, onAlternarColapso }: PropsMenuLateral) {
    const grupoActivo = ubicacion?.item ? ubicacion.grupo.titulo : null;
    const [abiertos, setAbiertos] = useState<Set<string>>(() => new Set(grupoActivo ? [grupoActivo] : []));

    // Al navegar, el grupo de la página actual se despliega (sin cerrar los demás).
    useEffect(() => {
        if (grupoActivo) {
            setAbiertos((actual) => (actual.has(grupoActivo) ? actual : new Set(actual).add(grupoActivo)));
        }
    }, [grupoActivo]);

    const alternar = (titulo: string) =>
        setAbiertos((actual) => {
            const nuevo = new Set(actual);

            if (nuevo.has(titulo)) {
                nuevo.delete(titulo);
            } else {
                nuevo.add(titulo);
            }

            return nuevo;
        });

    const activa = ubicacion?.url ?? null;

    return (
        <div className="flex h-full min-h-0 flex-col">
            <div className={cn('flex h-16 shrink-0 items-center border-b border-sidebar-border', colapsado ? 'justify-center px-2' : 'px-4')}>
                <Link to="/admin" className="flex min-w-0 items-center gap-3 rounded-lg" title="Inicio del panel de administración">
                    <LogoCarrera />
                    {colapsado ? (
                        <span className="sr-only">Inicio del panel de administración</span>
                    ) : (
                        <span className="grid min-w-0 leading-tight">
                            <span className="truncate text-sm font-bold">Administración</span>
                            <span className="truncate text-xs text-sidebar-foreground/70">Panel de la Carrera</span>
                        </span>
                    )}
                </Link>
            </div>

            <nav aria-label="Menú del panel" className="min-h-0 flex-1 overflow-x-hidden overflow-y-auto p-2">
                {!menu && cargando && (
                    <div className="space-y-2 p-1" aria-busy="true">
                        {Array.from({ length: 7 }, (_, i) => (
                            <Esqueleto key={i} className={cn('h-8', colapsado ? 'w-8' : 'w-full')} />
                        ))}
                        <span className="sr-only" role="status">
                            Cargando menú…
                        </span>
                    </div>
                )}

                {!menu && !cargando && error && (
                    <div role="alert" className="space-y-2 p-2 text-xs">
                        {!colapsado && <p>No se pudo cargar el menú.</p>}
                        <button type="button" className="inline-flex items-center gap-1 font-medium text-primary hover:underline" onClick={onReintentar} title="Reintentar">
                            <RotateCcw className="size-3.5" aria-hidden="true" /> {colapsado ? <span className="sr-only">Reintentar</span> : 'Reintentar'}
                        </button>
                    </div>
                )}

                {menu && (
                    <ul className="space-y-0.5">
                        {menu.map((grupo) => (
                            <li key={grupo.titulo}>
                                {grupo.items.length === 0 && grupo.url ? (
                                    <Enlace url={grupo.url} activo={sinConsulta(grupo.url) === activa} className={claseItem(colapsado, sinConsulta(grupo.url) === activa)} titulo={colapsado ? grupo.titulo : undefined}>
                                        <Icono nombre={grupo.icono} className={cn('size-4 shrink-0', sinConsulta(grupo.url) === activa && 'text-marca')} />
                                        <span className={colapsado ? 'sr-only' : 'min-w-0 truncate'}>{grupo.titulo}</span>
                                    </Enlace>
                                ) : (
                                    <GrupoLateral
                                        grupo={grupo}
                                        abierto={abiertos.has(grupo.titulo)}
                                        activa={activa}
                                        colapsado={colapsado}
                                        onAlternar={() => alternar(grupo.titulo)}
                                        onExpandir={() => {
                                            setAbiertos((actual) => new Set(actual).add(grupo.titulo));
                                            onExpandir?.();
                                        }}
                                    />
                                )}
                            </li>
                        ))}
                    </ul>
                )}

                {menu && menu.length === 0 && !colapsado && <p className="px-2 py-4 text-xs text-sidebar-foreground/70">Su rol todavía no tiene módulos asignados.</p>}
            </nav>

            <div className="shrink-0 space-y-0.5 border-t border-sidebar-border p-2">
                <a href={urlSitio('/')} target="_blank" rel="noopener noreferrer" className={claseItem(colapsado)} title={colapsado ? 'Ver sitio público' : undefined}>
                    <Globe className="size-4 shrink-0" aria-hidden="true" />
                    <span className={colapsado ? 'sr-only' : 'truncate'}>Ver sitio público</span>
                    <span className="sr-only"> (se abre en una pestaña nueva)</span>
                </a>
                {onAlternarColapso && (
                    <button
                        type="button"
                        onClick={onAlternarColapso}
                        className={claseItem(colapsado)}
                        aria-label={colapsado ? 'Expandir menú lateral' : 'Contraer menú lateral'}
                        title={colapsado ? 'Expandir menú lateral' : 'Contraer menú lateral'}
                    >
                        {colapsado ? <PanelLeftOpen className="size-4 shrink-0" aria-hidden="true" /> : <PanelLeftClose className="size-4 shrink-0" aria-hidden="true" />}
                        {!colapsado && <span className="truncate">Contraer menú</span>}
                    </button>
                )}
            </div>
        </div>
    );
}

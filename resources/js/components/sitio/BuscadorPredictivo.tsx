import axios from 'axios';
import { BookOpen, CalendarDays, CornerDownLeft, FileText, FlaskConical, LoaderCircle, Network, Newspaper, Search, Users, X } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useId, useMemo, useRef, useState } from 'react';
import type { FocusEvent, FormEvent, KeyboardEvent } from 'react';
import { useNavigate } from 'react-router';
import { rutaInterna } from '@/components/sitio/Enlace';
import { normalizar, Resaltado } from '@/components/sitio/Resaltado';
import type { RespuestaSugerencias, Sugerencia } from '@/components/sitio/tipos';
import { useDebounce } from '@/hooks/useDebounce';
import { api } from '@/lib/api';
import { cn } from '@/lib/utils';

/*
 * Búsqueda global predictiva del encabezado (RF-17). Mientras se escribe pide sugerencias a
 * GET /api/buscar/sugerencias?q= (con espera de 250 ms y desde 2 caracteres) y las muestra
 * agrupadas por sección. Sigue el patrón ARIA "combobox con lista": el foco queda en el campo,
 * las flechas recorren las opciones (aria-activedescendant), Enter abre la opción marcada
 * (o la página de resultados /buscar?q=) y Escape cierra la lista.
 */

const MINIMO_SUGERENCIAS = 2;
const MINIMO_BUSQUEDA = 3;
const MAXIMO_SUGERENCIAS = 8;
const ESPERA_MS = 250;

type OpcionSugerencia = { tipo: 'sugerencia'; indice: number; sugerencia: Sugerencia };
type Opcion = OpcionSugerencia | { tipo: 'todos'; indice: number };

interface Grupo {
    seccion: string;
    opciones: OpcionSugerencia[];
}

interface Resultado {
    q: string;
    sugerencias: Sugerencia[];
    error: boolean;
}

/** Ícono que identifica la sección de cada sugerencia. */
function iconoSeccion(seccion: string): LucideIcon {
    const s = normalizar(seccion);

    if (s.includes('noticia')) return Newspaper;
    if (s.includes('evento')) return CalendarDays;
    if (s.includes('materia') || s.includes('malla')) return Network;
    if (s.includes('proyecto') || s.includes('investig')) return FlaskConical;
    if (s.includes('revista') || s.includes('articulo')) return BookOpen;
    if (s.includes('docente') || s.includes('autoridad') || s.includes('plantel')) return Users;

    return FileText;
}

const urlBusqueda = (q: string) => `/buscar?q=${encodeURIComponent(q)}`;

interface PropsBuscador {
    className?: string;
    /** Enfoca el campo al aparecer (panel de búsqueda en móviles). */
    autoFocus?: boolean;
    /** Se llama después de navegar (p. ej. para cerrar el panel de búsqueda móvil). */
    onNavegar?: () => void;
}

export default function BuscadorPredictivo({ className, autoFocus = false, onNavegar }: PropsBuscador) {
    const navigate = useNavigate();
    const id = useId();
    const idEntrada = `${id}-entrada`;
    const idLista = `${id}-lista`;
    const idAyuda = `${id}-ayuda`;
    const entrada = useRef<HTMLInputElement>(null);
    const memoria = useRef(new Map<string, Sugerencia[]>());

    const [texto, setTexto] = useState('');
    const [abierto, setAbierto] = useState(false);
    const [activo, setActivo] = useState(-1);
    const [resultado, setResultado] = useState<Resultado | null>(null);
    const [cargando, setCargando] = useState(false);

    const consulta = texto.trim();
    const demorada = useDebounce(consulta, ESPERA_MS);

    useEffect(() => {
        if (autoFocus) {
            entrada.current?.focus();
        }
    }, [autoFocus]);

    // Pide las sugerencias cuando se deja de escribir; las respuestas se recuerdan para no repetir consultas.
    useEffect(() => {
        if (demorada.length < MINIMO_SUGERENCIAS) {
            setCargando(false);

            return;
        }

        const clave = normalizar(demorada);
        const guardadas = memoria.current.get(clave);

        if (guardadas) {
            setResultado({ q: demorada, sugerencias: guardadas, error: false });
            setCargando(false);

            return;
        }

        const control = new AbortController();
        setCargando(true);

        api.get<RespuestaSugerencias>('buscar/sugerencias', { params: { q: demorada }, signal: control.signal })
            .then(({ data }) => {
                const lista = (Array.isArray(data.sugerencias) ? data.sugerencias : []).slice(0, MAXIMO_SUGERENCIAS);
                memoria.current.set(clave, lista);
                setResultado({ q: demorada, sugerencias: lista, error: false });
            })
            .catch((e: unknown) => {
                if (!axios.isCancel(e)) {
                    setResultado({ q: demorada, sugerencias: [], error: true });
                }
            })
            .finally(() => {
                if (!control.signal.aborted) {
                    setCargando(false);
                }
            });

        return () => control.abort();
    }, [demorada]);

    const suficiente = consulta.length >= MINIMO_SUGERENCIAS;
    // Mientras se escribe se mantienen las sugerencias anteriores hasta que llegan las nuevas.
    const sugerencias = suficiente && resultado ? resultado.sugerencias : [];
    const pendiente = suficiente && (cargando || consulta !== demorada);

    const grupos = useMemo<Grupo[]>(() => {
        const porSeccion = new Map<string, Sugerencia[]>();

        for (const sugerencia of sugerencias) {
            const seccion = sugerencia.seccion?.trim() || 'Resultados';
            porSeccion.set(seccion, [...(porSeccion.get(seccion) ?? []), sugerencia]);
        }

        // El índice sigue el orden en pantalla (agrupado), que es el que recorren las flechas.
        let indice = 0;

        return [...porSeccion.entries()].map(([seccion, lista]) => ({
            seccion,
            opciones: lista.map((sugerencia) => ({ tipo: 'sugerencia' as const, indice: indice++, sugerencia })),
        }));
    }, [sugerencias]);

    const verTodos = consulta.length >= MINIMO_BUSQUEDA;
    const opciones: Opcion[] = [...grupos.flatMap((g) => g.opciones), ...(verTodos ? [{ tipo: 'todos' as const, indice: sugerencias.length }] : [])];
    const mostrar = abierto && suficiente;
    const expandido = mostrar && opciones.length > 0;
    const marcado = activo < opciones.length ? activo : -1;
    const idOpcion = (indice: number) => `${id}-opcion-${indice}`;

    // Mantiene visible la opción marcada al recorrer la lista con el teclado.
    useEffect(() => {
        if (marcado >= 0) {
            document.getElementById(`${id}-opcion-${marcado}`)?.scrollIntoView({ block: 'nearest' });
        }
    }, [marcado, id]);

    let anuncio = '';

    if (mostrar && !pendiente && resultado) {
        if (resultado.error) {
            anuncio = 'No se pudieron cargar las sugerencias.';
        } else if (sugerencias.length > 0) {
            anuncio = `${sugerencias.length} ${sugerencias.length === 1 ? 'sugerencia disponible' : 'sugerencias disponibles'}. Use las flechas para recorrerlas.`;
        } else {
            anuncio = `No hay sugerencias para «${consulta}».`;
        }
    }

    function irA(destino: string) {
        setAbierto(false);
        setActivo(-1);
        setTexto('');
        entrada.current?.blur();

        const ruta = rutaInterna(destino);

        if (ruta) {
            navigate(ruta);
        } else {
            window.location.assign(destino);
        }

        onNavegar?.();
    }

    function elegir(opcion: Opcion) {
        irA(opcion.tipo === 'todos' ? urlBusqueda(consulta) : opcion.sugerencia.url);
    }

    function alEnviar(e: FormEvent) {
        e.preventDefault();

        if (!consulta) {
            entrada.current?.focus();

            return;
        }

        irA(urlBusqueda(consulta));
    }

    function alTeclear(e: KeyboardEvent<HTMLInputElement>) {
        switch (e.key) {
            case 'ArrowDown':
                if (!suficiente || opciones.length === 0) {
                    return;
                }

                e.preventDefault();
                setAbierto(true);
                setActivo(!expandido || marcado >= opciones.length - 1 ? 0 : marcado + 1);
                break;
            case 'ArrowUp':
                if (!suficiente || opciones.length === 0) {
                    return;
                }

                e.preventDefault();
                setAbierto(true);
                setActivo(!expandido || marcado <= 0 ? opciones.length - 1 : marcado - 1);
                break;
            case 'Enter':
                // Con una opción marcada se abre esa opción; si no, el formulario lleva a /buscar?q=.
                if (expandido && marcado >= 0) {
                    e.preventDefault();
                    elegir(opciones[marcado]);
                }
                break;
            case 'Escape':
                if (expandido || mostrar) {
                    e.preventDefault();
                    setAbierto(false);
                    setActivo(-1);
                } else if (texto) {
                    e.preventDefault();
                    setTexto('');
                }
                break;
            case 'Tab':
                setAbierto(false);
                setActivo(-1);
                break;
            default:
                break;
        }
    }

    function alSalir(e: FocusEvent<HTMLDivElement>) {
        if (!e.currentTarget.contains(e.relatedTarget as Node | null)) {
            setAbierto(false);
            setActivo(-1);
        }
    }

    const claseOpcion = (indice: number) =>
        cn('flex cursor-pointer items-start gap-3 px-3 py-2 text-sm', marcado === indice ? 'bg-accent text-accent-foreground' : 'hover:bg-muted');

    return (
        <div className={cn('relative', className)} onBlur={alSalir}>
            <form role="search" aria-label="Buscar en el sitio" onSubmit={alEnviar} className="relative">
                <label htmlFor={idEntrada} className="sr-only">
                    Buscar en el sitio
                </label>
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                <input
                    ref={entrada}
                    id={idEntrada}
                    type="search"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-expanded={expandido}
                    aria-controls={expandido ? idLista : undefined}
                    aria-activedescendant={expandido && marcado >= 0 ? idOpcion(marcado) : undefined}
                    aria-describedby={idAyuda}
                    autoComplete="off"
                    autoCorrect="off"
                    spellCheck={false}
                    enterKeyHint="search"
                    maxLength={100}
                    placeholder="Buscar noticias, eventos, documentos, materias…"
                    value={texto}
                    onChange={(e) => {
                        setTexto(e.target.value);
                        setActivo(-1);
                        setAbierto(true);
                    }}
                    onFocus={() => setAbierto(true)}
                    onKeyDown={alTeclear}
                    className="campo h-11 pr-28 pl-9 [&::-webkit-search-cancel-button]:hidden"
                />
                <div className="absolute inset-y-1 right-1 flex items-center gap-1">
                    {pendiente && <LoaderCircle className="size-4 animate-spin text-muted-foreground" aria-hidden="true" />}
                    {texto && (
                        <button
                            type="button"
                            className="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground"
                            aria-label="Borrar búsqueda"
                            onClick={() => {
                                setTexto('');
                                setActivo(-1);
                                entrada.current?.focus();
                            }}
                        >
                            <X className="size-4" aria-hidden="true" />
                        </button>
                    )}
                    <button type="submit" className="boton h-full px-3 text-xs sm:px-4">
                        Buscar
                    </button>
                </div>
            </form>

            <p id={idAyuda} className="sr-only">
                Escriba al menos 2 letras para ver sugerencias. Use las flechas arriba y abajo para recorrerlas, Enter para abrir y Escape para cerrar.
            </p>
            <p className="sr-only" role="status" aria-live="polite" aria-atomic="true">
                {anuncio}
            </p>

            {mostrar && (
                <div className="absolute inset-x-0 top-full z-50 mt-1.5 overflow-hidden rounded-lg border border-t-2 border-t-marca bg-popover text-popover-foreground shadow-xl">
                    {expandido && (
                        <div id={idLista} role="listbox" aria-label="Sugerencias de búsqueda" className="max-h-[min(26rem,65vh)] overflow-y-auto py-1">
                            {grupos.map((grupo, g) => {
                                const IconoSeccion = iconoSeccion(grupo.seccion);

                                return (
                                    <ul key={grupo.seccion} role="group" aria-labelledby={`${id}-grupo-${g}`} className="py-1">
                                        <li role="presentation" id={`${id}-grupo-${g}`} className="px-3 pt-1 pb-1 text-[11px] font-bold tracking-wider text-muted-foreground uppercase">
                                            {grupo.seccion}
                                        </li>
                                        {grupo.opciones.map((opcion) => (
                                            <li
                                                key={`${opcion.sugerencia.url}-${opcion.indice}`}
                                                id={idOpcion(opcion.indice)}
                                                role="option"
                                                aria-selected={marcado === opcion.indice}
                                                className={claseOpcion(opcion.indice)}
                                                onMouseDown={(e) => e.preventDefault()}
                                                onMouseMove={() => marcado !== opcion.indice && setActivo(opcion.indice)}
                                                onClick={() => elegir(opcion)}
                                            >
                                                <IconoSeccion className="mt-0.5 size-4 shrink-0 text-primary" aria-hidden="true" />
                                                <span className="line-clamp-2 min-w-0 flex-1">
                                                    <Resaltado texto={opcion.sugerencia.titulo} consulta={consulta} />
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                );
                            })}

                            {verTodos && (
                                <div
                                    id={idOpcion(sugerencias.length)}
                                    role="option"
                                    aria-selected={marcado === sugerencias.length}
                                    className={cn(claseOpcion(sugerencias.length), 'items-center font-semibold text-primary', sugerencias.length > 0 && 'border-t')}
                                    onMouseDown={(e) => e.preventDefault()}
                                    onMouseMove={() => marcado !== sugerencias.length && setActivo(sugerencias.length)}
                                    onClick={() => elegir({ tipo: 'todos', indice: sugerencias.length })}
                                >
                                    <Search className="size-4 shrink-0" aria-hidden="true" />
                                    <span className="min-w-0 flex-1 truncate">Ver todos los resultados para «{consulta}»</span>
                                    <CornerDownLeft className="size-3.5 shrink-0 opacity-60" aria-hidden="true" />
                                </div>
                            )}
                        </div>
                    )}

                    {pendiente && sugerencias.length === 0 && (
                        <p className="flex items-center gap-2 px-3 py-3 text-sm text-muted-foreground">
                            <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> Buscando sugerencias…
                        </p>
                    )}
                    {!pendiente && resultado?.error && <p className="px-3 py-3 text-sm text-destructive">No se pudieron cargar las sugerencias.</p>}
                    {!pendiente && resultado && !resultado.error && sugerencias.length === 0 && (
                        <p className="px-3 py-3 text-sm text-muted-foreground">
                            No hay sugerencias para «{consulta}».{!verTodos && ' Escriba al menos 3 caracteres para buscar en todo el sitio.'}
                        </p>
                    )}

                    <p className="hidden border-t bg-muted/50 px-3 py-1.5 text-[11px] text-muted-foreground sm:block" aria-hidden="true">
                        ↑ ↓ para moverse · Enter para abrir · Esc para cerrar
                    </p>
                </div>
            )}
        </div>
    );
}

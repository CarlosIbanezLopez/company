import { FileText, Image as IconoImagen, X } from 'lucide-react';
import { useEffect, useId, useMemo, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { EditorMarkdown } from '@/components/admin/EditorMarkdown';
import { EntradaContrasena } from '@/components/admin/EntradaContrasena';
import { MatrizPermisos } from '@/components/admin/MatrizPermisos';
import { tamanoLegible } from '@/components/admin/subidas';
import { CampoFormulario } from '@/components/ui/CampoFormulario';
import { fecha } from '@/lib/formato';
import { cn } from '@/lib/utils';
import { reglasDesdeServidor } from '@/lib/validacion';
import type { Etiquetas, ReglasFormulario } from '@/lib/validacion';
import type { Campo, Opcion } from '@/types';

/*
 * Controles del formulario genérico del CRUD: un componente por tipo de Campo
 * (text, email, url, number, date, time, password, textarea, markdown, select, multiselect,
 * checkboxes, checkbox, image, file, readonly, matriz) y utilidades para preparar los valores,
 * las reglas de validación del cliente (ReglasCliente del servidor) y los datos a enviar.
 */
export type Valores = Record<string, unknown>;

const TIPOS_LISTA = ['multiselect', 'checkboxes', 'matriz'];

export const esArchivo = (v: unknown): v is File => typeof File !== 'undefined' && v instanceof File;

const verdadero = (v: unknown): boolean => v === true || v === 1 || v === '1' || v === 'true' || v === 'on';

/** Ocupa las dos columnas del formulario. */
export const anchoCompleto = (c: Campo): boolean => c.ancho === 'full' || ['markdown', 'checkboxes', 'multiselect', 'matriz'].includes(c.tipo);

/** Etiqueta sin aclaraciones entre paréntesis, para los mensajes de validación. */
export const etiquetaLimpia = (c: Campo): string => c.etiqueta.replace(/\s*\([^)]*\)/g, '').trim() || c.nombre.replaceAll('_', ' ');

/** En edición, los archivos y contraseñas existentes no son obligatorios. */
export function esRequerido(c: Campo, esEdicion: boolean): boolean {
    const requerido = c.reglas?.requerido ?? Boolean(c.requerido);

    return requerido && !(esEdicion && ['image', 'file', 'password'].includes(c.tipo));
}

/** Valores iniciales del formulario a partir de los que envía la API. */
export function valoresIniciales(campos: Campo[], valores: Valores): Valores {
    const inicial: Valores = {};

    for (const c of campos) {
        const v = valores[c.nombre];

        switch (c.tipo) {
            case 'readonly':
                break;
            case 'image':
            case 'file':
                inicial[c.nombre] = null;
                inicial[`${c.nombre}_quitar`] = false;
                break;
            case 'checkbox':
                inicial[c.nombre] = verdadero(v);
                break;
            case 'multiselect':
            case 'checkboxes':
            case 'matriz':
                inicial[c.nombre] = Array.isArray(v) ? v.map((x) => (x && typeof x === 'object' && 'id' in x ? (x as { id: unknown }).id : x)) : [];
                break;
            case 'password':
                inicial[c.nombre] = '';
                if (c.reglas?.confirmado) {
                    inicial[`${c.nombre}_confirmation`] = '';
                }
                break;
            case 'date':
                inicial[c.nombre] = typeof v === 'string' && /^\d{4}-\d{2}-\d{2}/.test(v) ? v.slice(0, 10) : (v ?? '');
                break;
            case 'time':
                inicial[c.nombre] = typeof v === 'string' && /^\d{2}:\d{2}/.test(v) ? v.slice(0, 5) : (v ?? '');
                break;
            default:
                inicial[c.nombre] = v === null || v === undefined ? '' : v;
        }
    }

    return inicial;
}

/** Datos que se envían a la API (sin campos de solo lectura; archivos solo si se eligió uno). */
export function datosParaEnviar(campos: Campo[], valores: Valores): Valores {
    const datos: Valores = {};

    for (const c of campos) {
        const v = valores[c.nombre];

        if (c.tipo === 'readonly') {
            continue;
        }

        if (c.tipo === 'image' || c.tipo === 'file') {
            if (esArchivo(v)) {
                datos[c.nombre] = v;
            } else if (valores[`${c.nombre}_quitar`] === true) {
                datos[`${c.nombre}_quitar`] = true;
            }
            continue;
        }

        if (c.tipo === 'password') {
            // Vacía = conservar la contraseña actual.
            if (typeof v === 'string' && v !== '') {
                datos[c.nombre] = v;

                if (c.reglas?.confirmado) {
                    datos[`${c.nombre}_confirmation`] = valores[`${c.nombre}_confirmation`] ?? '';
                }
            }
            continue;
        }

        if (c.tipo === 'checkbox') {
            datos[c.nombre] = Boolean(v);
        } else if (TIPOS_LISTA.includes(c.tipo)) {
            datos[c.nombre] = Array.isArray(v) ? v : [];
        } else if (['select', 'number', 'date', 'time'].includes(c.tipo) && (v === '' || v === undefined)) {
            datos[c.nombre] = null;
        } else {
            datos[c.nombre] = v;
        }
    }

    return datos;
}

const MIME_EXTENSIONES: Record<string, string[]> = {
    'application/pdf': ['pdf'],
    'image/jpeg': ['jpg', 'jpeg'],
    'image/png': ['png'],
    'image/webp': ['webp'],
    'image/gif': ['gif'],
    'application/msword': ['doc'],
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': ['docx'],
    'application/vnd.ms-excel': ['xls'],
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': ['xlsx'],
};

/** "application/pdf,.docx" -> ['pdf', 'docx'] (vacío si admite comodines como image/*). */
function extensionesDeAccept(accept: string | null | undefined): string[] {
    const partes = (accept ?? '').split(',').map((p) => p.trim().toLowerCase()).filter(Boolean);

    if (!partes.length || partes.some((p) => p.endsWith('/*'))) {
        return [];
    }

    return [...new Set(partes.flatMap((p) => (p.startsWith('.') ? [p.slice(1)] : (MIME_EXTENSIONES[p] ?? []))))];
}

/** Reglas del validador del cliente: las mismas que el servidor envía en `campo.reglas` (ReglasCliente). */
export function reglasCampos(campos: Campo[], esEdicion: boolean): ReglasFormulario {
    const reglas: ReglasFormulario = {};

    for (const c of campos) {
        if (c.tipo === 'readonly') {
            continue;
        }

        const lista = reglasDesdeServidor({ ...(c.reglas ?? {}), requerido: c.reglas?.requerido ?? Boolean(c.requerido) }, c.tipo, esEdicion);

        if (c.tipo === 'number' && !lista.includes('numerico') && !lista.includes('entero')) {
            lista.push('numerico');
        }

        if (c.tipo === 'date' && !lista.includes('fecha')) {
            lista.push('fecha');
        }

        if (c.tipo === 'time' && !lista.includes('hora')) {
            lista.push('hora');
        }

        if (c.tipo === 'image' && !lista.includes('imagen')) {
            lista.push('imagen');
        }

        // Si el servidor todavía no envía reglas, se respeta al menos el tipo de archivo aceptado.
        if (c.tipo === 'file' && !c.reglas) {
            const extensiones = extensionesDeAccept(c.accept);

            if (extensiones.length) {
                lista.push(`mimes:${extensiones.join(',')}`);
            }
        }

        if (lista.length) {
            reglas[c.nombre] = lista;
        }
    }

    return reglas;
}

export function etiquetasCampos(campos: Campo[]): Etiquetas {
    const etiquetas: Etiquetas = {};

    for (const c of campos) {
        etiquetas[c.nombre] = etiquetaLimpia(c);
        etiquetas[`${c.nombre}_confirmation`] = `confirmación de ${etiquetaLimpia(c).toLowerCase()}`;
    }

    return etiquetas;
}

/** Agrupa opciones por `grupo` conservando el orden (para <optgroup> y listas de casillas). */
export function agruparOpciones(opciones: Opcion[] = []): [string, Opcion[]][] {
    const mapa = new Map<string, Opcion[]>();

    for (const o of opciones) {
        const g = o.grupo ?? '';
        mapa.set(g, [...(mapa.get(g) ?? []), o]);
    }

    return [...mapa.entries()];
}

/** <option> de un select, con <optgroup> cuando las opciones traen grupo. */
export function OpcionesSelect({ opciones }: { opciones?: Opcion[] }) {
    return (
        <>
            {agruparOpciones(opciones).map(([grupo, lista]) =>
                grupo ? (
                    <optgroup key={grupo} label={grupo}>
                        {lista.map((o) => (
                            <option key={String(o.value)} value={String(o.value)}>
                                {o.label}
                            </option>
                        ))}
                    </optgroup>
                ) : (
                    lista.map((o) => (
                        <option key={String(o.value)} value={String(o.value)}>
                            {o.label}
                        </option>
                    ))
                ),
            )}
        </>
    );
}

/* ------------------------------------------------------------------ Envoltorios */

interface PropsGrupo {
    etiqueta: ReactNode;
    error?: string;
    ayuda?: ReactNode;
    requerido?: boolean;
    children: ReactNode;
}

/** Grupo de controles (casillas, matriz) con <fieldset>/<legend>, ayuda y error accesibles. */
function GrupoCampo({ etiqueta, error, ayuda, requerido, children }: PropsGrupo) {
    const id = useId();
    const describe = [ayuda && !error ? `${id}-ayuda` : null, error ? `${id}-error` : null].filter(Boolean).join(' ') || undefined;

    return (
        <fieldset className="min-w-0" aria-describedby={describe} aria-invalid={error ? true : undefined}>
            <legend className="etiqueta">
                {etiqueta}
                {requerido && (
                    <span className="ml-0.5 text-destructive" aria-hidden="true">
                        *
                    </span>
                )}
            </legend>
            {children}
            {ayuda && !error && (
                <p id={`${id}-ayuda`} className="mt-1 text-xs text-muted-foreground">
                    {ayuda}
                </p>
            )}
            {error && (
                <p id={`${id}-error`} role="alert" className="error-campo">
                    {error}
                </p>
            )}
        </fieldset>
    );
}

/** Contenido de un campo de solo lectura (p. ej. datos enviados por un visitante). */
function textoSoloLectura(valor: unknown): string {
    if (valor === null || valor === undefined || valor === '') {
        return '—';
    }

    if (typeof valor === 'boolean') {
        return valor ? 'Sí' : 'No';
    }

    if (Array.isArray(valor)) {
        return valor.map(String).join(', ') || '—';
    }

    if (typeof valor === 'string' && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(valor)) {
        return fecha(valor, true);
    }

    return typeof valor === 'object' ? JSON.stringify(valor) : String(valor);
}

/* ------------------------------------------------------------------ Controles especiales */

type PropsAria = {
    id?: string;
    'aria-invalid'?: boolean;
    'aria-describedby'?: string;
    'aria-required'?: boolean;
};

interface PropsSelectorArchivo extends PropsAria {
    campo: Campo;
    archivo: unknown;
    quitar: boolean;
    urlActual: string | null;
    onArchivo: (archivo: File | null) => void;
    onQuitar: (quitar: boolean) => void;
    onBlur: () => void;
}


/** Imagen (con vista previa y opción de quitar la actual) o archivo (con enlace al actual). */
function SelectorArchivo({ campo, archivo, quitar, urlActual, onArchivo, onQuitar, onBlur, ...aria }: PropsSelectorArchivo) {
    const esImagen = campo.tipo === 'image';
    const entrada = useRef<HTMLInputElement>(null);
    const [vista, setVista] = useState<string | null>(null);
    const seleccionado = esArchivo(archivo) ? archivo : null;

    useEffect(() => {
        if (!esImagen || !seleccionado) {
            setVista(null);

            return;
        }

        const url = URL.createObjectURL(seleccionado);
        setVista(url);

        return () => URL.revokeObjectURL(url);
    }, [seleccionado, esImagen]);

    const limpiar = () => {
        onArchivo(null);

        if (entrada.current) {
            entrada.current.value = '';
        }
    };

    const control = (
        <input
            ref={entrada}
            {...aria}
            name={campo.nombre}
            type="file"
            accept={campo.accept ?? (esImagen ? 'image/*' : undefined)}
            onChange={(e) => onArchivo(e.target.files?.[0] ?? null)}
            onBlur={onBlur}
            className="campo pt-1.5 file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1 file:text-sm file:font-medium file:text-secondary-foreground"
        />
    );

    const detalleSeleccion = seleccionado && (
        <p className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <span className="min-w-0 truncate">
                Seleccionado: <strong className="font-medium text-foreground">{seleccionado.name}</strong> ({tamanoLegible(seleccionado.size)})
            </span>
            <button type="button" className="inline-flex items-center gap-1 font-medium text-primary hover:underline" onClick={limpiar}>
                <X className="size-3.5" aria-hidden="true" /> Quitar selección
            </button>
        </p>
    );

    if (!esImagen) {
        return (
            <div className="space-y-2">
                {control}
                {detalleSeleccion}
                {urlActual && !seleccionado && (
                    <div className="flex flex-wrap items-center gap-4 text-xs">
                        <a href={urlActual} target="_blank" rel="noopener noreferrer" className={cn('inline-flex items-center gap-1 text-primary hover:underline', quitar && 'line-through opacity-60')}>
                            <FileText className="size-3.5" aria-hidden="true" /> Ver archivo actual
                        </a>
                        <label className="inline-flex items-center gap-2">
                            <input type="checkbox" className="size-4 accent-[var(--destructive)]" checked={quitar} onChange={(e) => onQuitar(e.target.checked)} /> Quitar archivo
                        </label>
                    </div>
                )}
            </div>
        );
    }

    const mostrar = vista ?? (urlActual && !quitar ? urlActual : null);

    return (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
            <div className="flex size-28 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted">
                {mostrar ? (
                    <img src={mostrar} alt={seleccionado ? 'Vista previa de la imagen seleccionada' : `Imagen actual: ${campo.etiqueta}`} className="size-full object-cover" />
                ) : (
                    <IconoImagen className="size-8 text-muted-foreground" aria-hidden="true" />
                )}
            </div>
            <div className="min-w-0 flex-1 space-y-2">
                {control}
                {detalleSeleccion}
                {urlActual && !seleccionado && (
                    <label className="flex items-center gap-2 text-xs">
                        <input type="checkbox" className="size-4 accent-[var(--destructive)]" checked={quitar} onChange={(e) => onQuitar(e.target.checked)} /> Quitar la imagen actual
                    </label>
                )}
            </div>
        </div>
    );
}

type Id = string | number;

const incluye = (lista: unknown, valor: Id) => Array.isArray(lista) && lista.some((x) => String(x) === String(valor));

const alternar = (lista: unknown, valor: Id): Id[] => {
    const actual = Array.isArray(lista) ? (lista as Id[]) : [];

    return incluye(actual, valor) ? actual.filter((x) => String(x) !== String(valor)) : [...actual, valor];
};

/** Casillas agrupadas (opciones con `grupo`) con atajos "todos" y "ninguno" por grupo. */
function ListaCasillas({ campo, valor, onCambiar }: { campo: Campo; valor: unknown; onCambiar: (v: Id[]) => void }) {
    const grupos = agruparOpciones(campo.opciones);

    const marcarGrupo = (opciones: Opcion[], marcar: boolean) => {
        const ids = opciones.map((o) => String(o.value));
        const resto = (Array.isArray(valor) ? (valor as Id[]) : []).filter((x) => !ids.includes(String(x)));
        onCambiar(marcar ? [...resto, ...opciones.map((o) => o.value)] : resto);
    };

    if (!grupos.length) {
        return <p className="text-sm text-muted-foreground">No hay opciones disponibles.</p>;
    }

    return (
        <div className="grid gap-3 sm:grid-cols-2">
            {grupos.map(([grupo, opciones]) => (
                <fieldset key={grupo || 'general'} className="min-w-0 rounded-lg border p-3">
                    <legend className="px-1 text-xs font-bold tracking-wide uppercase">{grupo || 'General'}</legend>
                    <div className="mb-1 flex justify-end gap-3 text-xs">
                        <button type="button" className="text-primary hover:underline" onClick={() => marcarGrupo(opciones, true)}>
                            todos
                        </button>
                        <button type="button" className="text-muted-foreground hover:underline" onClick={() => marcarGrupo(opciones, false)}>
                            ninguno
                        </button>
                    </div>
                    {opciones.map((o) => (
                        <label key={String(o.value)} className="flex cursor-pointer items-center gap-2 py-1 text-sm">
                            <input
                                type="checkbox"
                                name={campo.nombre}
                                className="size-4 shrink-0 accent-[var(--primary)]"
                                checked={incluye(valor, o.value)}
                                onChange={() => onCambiar(alternar(valor, o.value))}
                            />
                            {o.label}
                        </label>
                    ))}
                </fieldset>
            ))}
        </div>
    );
}

/** Selección múltiple con filtro de texto (p. ej. prerrequisitos de una materia). */
function SeleccionMultiple({ campo, valor, onCambiar }: { campo: Campo; valor: unknown; onCambiar: (v: Id[]) => void }) {
    const [filtro, setFiltro] = useState('');
    const visibles = useMemo(
        () => (campo.opciones ?? []).filter((o) => o.label.toLowerCase().includes(filtro.trim().toLowerCase())),
        [campo.opciones, filtro],
    );
    const cantidad = Array.isArray(valor) ? valor.length : 0;

    return (
        <div className="overflow-hidden rounded-lg border border-input">
            <input
                type="search"
                value={filtro}
                onChange={(e) => setFiltro(e.target.value)}
                placeholder="Filtrar opciones…"
                aria-label={`Filtrar opciones de ${campo.etiqueta}`}
                className="w-full border-b bg-transparent px-3 py-2 text-sm outline-none focus:bg-accent/40"
            />
            <div className="max-h-64 overflow-y-auto p-2">
                {agruparOpciones(visibles).map(([grupo, opciones]) => (
                    <div key={grupo || 'general'}>
                        {grupo && <p className="mt-2 px-1 text-xs font-bold text-muted-foreground uppercase first:mt-0">{grupo}</p>}
                        {opciones.map((o) => (
                            <label key={String(o.value)} className="flex cursor-pointer items-center gap-2 rounded-md px-1 py-1 text-sm hover:bg-accent">
                                <input
                                    type="checkbox"
                                    name={campo.nombre}
                                    className="size-4 shrink-0 accent-[var(--primary)]"
                                    checked={incluye(valor, o.value)}
                                    onChange={() => onCambiar(alternar(valor, o.value))}
                                />
                                {o.label}
                            </label>
                        ))}
                    </div>
                ))}
                {!visibles.length && <p className="px-1 py-2 text-sm text-muted-foreground">Sin coincidencias.</p>}
            </div>
            <p className="border-t bg-muted/50 px-3 py-1.5 text-xs text-muted-foreground" aria-live="polite">
                {cantidad === 1 ? '1 seleccionado' : `${cantidad} seleccionados`}
            </p>
        </div>
    );
}

const AYUDA_MARKDOWN = (
    <>
        Formato Markdown: <code>## Subtítulo</code>, <code>**negrita**</code>, <code>*cursiva*</code>, <code>- lista</code>, <code>1. lista numerada</code>,{' '}
        <code>[texto](https://enlace)</code>, <code>&gt; cita</code>.
    </>
);

const TIPO_INPUT: Partial<Record<Campo['tipo'], string>> = { email: 'email', url: 'url', number: 'number', date: 'date', time: 'time' };

/* ------------------------------------------------------------------ Control por tipo */

interface PropsControl {
    campo: Campo;
    /** Estado actual del formulario. */
    valores: Valores;
    /** Valores recibidos de la API (solo lectura y URLs de archivos: {nombre}_url). */
    originales: Valores;
    errores: Record<string, string>;
    set: (nombre: string, valor: unknown) => void;
    validarUno: (nombre: string) => void;
    esEdicion: boolean;
    soloLectura: boolean;
}

/** Dibuja el control adecuado para cada tipo de Campo del CRUD. */
export function ControlCampo({ campo: c, valores, originales, errores, set, validarUno, esEdicion, soloLectura }: PropsControl) {
    const valor = valores[c.nombre];
    const error = errores[c.nombre];
    const requerido = !soloLectura && esRequerido(c, esEdicion);
    const ayuda = c.ayuda || undefined;
    const alSalir = () => validarUno(c.nombre);
    const texto = valor === null || valor === undefined ? '' : String(valor);

    switch (c.tipo) {
        case 'readonly':
            return (
                <div className="min-w-0">
                    <p className="etiqueta">{c.etiqueta}</p>
                    <div className="min-h-10 rounded-lg border bg-muted/50 px-3 py-2 text-sm break-words whitespace-pre-line">{textoSoloLectura(originales[c.nombre])}</div>
                    {ayuda && <p className="mt-1 text-xs text-muted-foreground">{ayuda}</p>}
                </div>
            );

        case 'checkbox':
            return (
                <div className="min-w-0">
                    <label className="flex cursor-pointer items-center gap-3 rounded-lg border p-3 hover:border-primary">
                        <input
                            type="checkbox"
                            name={c.nombre}
                            checked={Boolean(valor)}
                            onChange={(e) => set(c.nombre, e.target.checked)}
                            aria-invalid={error ? true : undefined}
                            className="size-4 shrink-0 accent-[var(--primary)]"
                        />
                        <span className="text-sm font-medium">
                            {c.etiqueta}
                            {requerido && (
                                <span className="ml-0.5 text-destructive" aria-hidden="true">
                                    *
                                </span>
                            )}
                        </span>
                    </label>
                    {ayuda && !error && <p className="mt-1 text-xs text-muted-foreground">{ayuda}</p>}
                    {error && (
                        <p role="alert" className="error-campo">
                            {error}
                        </p>
                    )}
                </div>
            );

        case 'checkboxes':
            return (
                <GrupoCampo etiqueta={c.etiqueta} error={error} ayuda={ayuda} requerido={requerido}>
                    <ListaCasillas campo={c} valor={valor} onCambiar={(v) => set(c.nombre, v)} />
                </GrupoCampo>
            );

        case 'multiselect':
            return (
                <GrupoCampo etiqueta={c.etiqueta} error={error} ayuda={ayuda} requerido={requerido}>
                    <SeleccionMultiple campo={c} valor={valor} onCambiar={(v) => set(c.nombre, v)} />
                </GrupoCampo>
            );

        case 'matriz':
            return (
                <GrupoCampo
                    etiqueta={c.etiqueta}
                    error={error}
                    requerido={requerido}
                    ayuda={`${ayuda ? `${ayuda} ` : ''}Al marcar crear, editar o eliminar se marca también «ver», necesario para entrar al módulo.`}
                >
                    <MatrizPermisos campo={c} valor={valor} onCambiar={(v) => set(c.nombre, v)} />
                </GrupoCampo>
            );

        case 'image':
        case 'file': {
            const url = originales[`${c.nombre}_url`];

            return (
                <CampoFormulario etiqueta={c.etiqueta} error={error} ayuda={ayuda} requerido={requerido}>
                    <SelectorArchivo
                        campo={c}
                        archivo={valor}
                        quitar={valores[`${c.nombre}_quitar`] === true}
                        urlActual={typeof url === 'string' && url ? url : null}
                        onArchivo={(archivo) => set(c.nombre, archivo)}
                        onQuitar={(quitar) => set(`${c.nombre}_quitar`, quitar)}
                        onBlur={alSalir}
                    />
                </CampoFormulario>
            );
        }

        case 'textarea':
            return (
                <div className="min-w-0">
                    <CampoFormulario etiqueta={c.etiqueta} error={error} ayuda={ayuda} requerido={requerido}>
                        <textarea name={c.nombre} value={texto} rows={4} onChange={(e) => set(c.nombre, e.target.value)} onBlur={alSalir} className="campo" />
                    </CampoFormulario>
                    {c.reglas?.max ? (
                        <p className={cn('mt-1 text-right text-xs tabular-nums', texto.length > c.reglas.max ? 'font-semibold text-destructive' : 'text-muted-foreground')}>
                            {texto.length.toLocaleString('es-BO')} / {c.reglas.max.toLocaleString('es-BO')}
                        </p>
                    ) : null}
                </div>
            );

        case 'markdown':
            return (
                <CampoFormulario etiqueta={c.etiqueta} error={error} ayuda={ayuda ?? AYUDA_MARKDOWN} requerido={requerido}>
                    <EditorMarkdown
                        name={c.nombre}
                        value={texto}
                        maximo={c.reglas?.max}
                        vistaInicial={soloLectura}
                        onChange={(e) => set(c.nombre, e.target.value)}
                        onBlur={alSalir}
                    />
                </CampoFormulario>
            );

        case 'select':
            return (
                <CampoFormulario etiqueta={c.etiqueta} error={error} ayuda={ayuda} requerido={requerido}>
                    <select name={c.nombre} value={texto} onChange={(e) => set(c.nombre, e.target.value)} onBlur={alSalir} className="campo">
                        <option value="">Seleccione…</option>
                        <OpcionesSelect opciones={c.opciones} />
                    </select>
                </CampoFormulario>
            );

        case 'password':
            return (
                <div className="min-w-0 space-y-4">
                    <CampoFormulario
                        etiqueta={c.etiqueta}
                        error={error}
                        requerido={requerido}
                        ayuda={ayuda ?? (esEdicion ? 'Déjela vacía para mantener la contraseña actual.' : undefined)}
                    >
                        <EntradaContrasena name={c.nombre} value={texto} autoComplete="new-password" onChange={(e) => set(c.nombre, e.target.value)} onBlur={alSalir} />
                    </CampoFormulario>
                    {c.reglas?.confirmado && (
                        <CampoFormulario etiqueta={`Confirmar ${etiquetaLimpia(c).toLowerCase()}`} error={errores[`${c.nombre}_confirmation`]} requerido={requerido}>
                            <EntradaContrasena
                                name={`${c.nombre}_confirmation`}
                                value={String(valores[`${c.nombre}_confirmation`] ?? '')}
                                autoComplete="new-password"
                                onChange={(e) => set(`${c.nombre}_confirmation`, e.target.value)}
                                onBlur={() => validarUno(c.nombre)}
                            />
                        </CampoFormulario>
                    )}
                </div>
            );

        default: {
            const tipo = TIPO_INPUT[c.tipo] ?? 'text';
            const entero = Boolean(c.reglas?.entero);

            return (
                <CampoFormulario etiqueta={c.etiqueta} error={error} ayuda={ayuda} requerido={requerido}>
                    <input
                        name={c.nombre}
                        type={tipo}
                        value={texto}
                        onChange={(e) => set(c.nombre, e.target.value)}
                        onBlur={alSalir}
                        className="campo"
                        inputMode={tipo === 'number' ? (entero ? 'numeric' : 'decimal') : undefined}
                        step={tipo === 'number' ? (entero ? 1 : 'any') : undefined}
                        min={tipo === 'number' ? (c.reglas?.entre?.[0] ?? c.reglas?.min) : undefined}
                        max={tipo === 'number' ? (c.reglas?.entre?.[1] ?? c.reglas?.max) : undefined}
                        autoComplete={tipo === 'email' ? 'email' : undefined}
                    />
                </CampoFormulario>
            );
        }
    }
}

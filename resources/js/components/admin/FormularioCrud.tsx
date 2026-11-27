import type { AxiosResponse } from 'axios';
import { LoaderCircle, Lock, Save } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Link } from 'react-router';
import { anchoCompleto, ControlCampo, datosParaEnviar, etiquetasCampos, reglasCampos, valoresIniciales } from '@/components/admin/CamposCrud';
import type { Valores } from '@/components/admin/CamposCrud';
import { Esqueleto } from '@/components/ui/Cargando';
import { useFormulario } from '@/hooks/useFormulario';
import { cn } from '@/lib/utils';
import type { Campo, ConfigCrud } from '@/types';

/** Marcador de posición mientras llega el formulario de la API. */
export function EsqueletoFormulario() {
    return (
        <div className="mx-auto max-w-5xl space-y-5" aria-busy="true">
            <div className="border-b pb-4">
                <Esqueleto className="h-4 w-32" />
                <Esqueleto className="mt-3 h-7 w-72" />
            </div>
            <div className="tarjeta grid gap-5 p-6 md:grid-cols-2">
                {Array.from({ length: 6 }, (_, i) => (
                    <div key={i}>
                        <Esqueleto className="h-4 w-28" />
                        <Esqueleto className="mt-2 h-10 w-full" />
                    </div>
                ))}
            </div>
            <span className="sr-only" role="status">
                Cargando formulario…
            </span>
        </div>
    );
}

export interface RespuestaGuardar {
    message?: string;
    registro?: unknown;
    [clave: string]: unknown;
}

interface PropsFormularioCrud {
    config: ConfigCrud;
    campos: Campo[];
    /** Valores recibidos de la API (incluye {campo}_url de imágenes y archivos). */
    valores: Valores;
    esEdicion: boolean;
    /** Sin permiso de edición: muestra los datos sin poder modificarlos. */
    soloLectura?: boolean;
    /** Ruta del listado para "Cancelar" (null en formularios sin listado, como la configuración). */
    rutaListado?: string | null;
    textoGuardar?: string;
    /** Envía los datos ya preparados (JSON o multipart) y devuelve la respuesta de Axios. */
    enviar: (datos: Valores, alProgresar: (porcentaje: number | null) => void) => Promise<AxiosResponse<RespuestaGuardar>>;
    alGuardar?: (respuesta: RespuestaGuardar) => void;
}

/**
 * Formulario genérico del CRUD: dibuja los Campo que envía la API, valida en el cliente con las
 * mismas reglas del servidor (ReglasCliente) y muestra junto a cada campo los errores 422 de la API.
 */
export function FormularioCrud({ config, campos, valores, esEdicion, soloLectura = false, rutaListado = null, textoGuardar = 'Guardar', enviar, alGuardar }: PropsFormularioCrud) {
    const [inicial] = useState(() => valoresIniciales(campos, valores));
    const reglas = useMemo(() => reglasCampos(campos, esEdicion), [campos, esEdicion]);
    const etiquetas = useMemo(() => etiquetasCampos(campos), [campos]);
    const f = useFormulario<Valores>(inicial, { reglas, etiquetas });
    const [progreso, setProgreso] = useState<number | null>(null);

    const nombres = useMemo(() => new Set(campos.flatMap((c) => [c.nombre, `${c.nombre}_confirmation`, `${c.nombre}_quitar`])), [campos]);
    const otrosErrores = Object.entries(f.errores).filter(([campo]) => !nombres.has(campo));

    const guardar = f.alEnviar(
        async (datos) => {
            try {
                return await enviar(datosParaEnviar(campos, datos), setProgreso);
            } finally {
                setProgreso(null);
            }
        },
        { onExito: (respuesta) => alGuardar?.(respuesta.data ?? {}) },
    );

    return (
        <form noValidate onSubmit={soloLectura ? (e) => e.preventDefault() : guardar} className="tarjeta overflow-hidden" aria-busy={f.enviando}>
            {soloLectura && (
                <div role="note" className="flex items-start gap-2 border-b bg-aviso/15 px-5 py-3 text-sm md:px-6">
                    <Lock className="mt-0.5 size-4 shrink-0 text-aviso" aria-hidden="true" />
                    <p>
                        <strong className="font-semibold">Solo lectura.</strong> Su rol puede consultar {config.femenino ? 'esta' : 'este'} {config.singular}, pero no
                        modificarlo.
                    </p>
                </div>
            )}

            {otrosErrores.length > 0 && (
                <div role="alert" className="border-b bg-destructive/10 px-5 py-3 text-sm text-destructive md:px-6">
                    <p className="font-semibold">Revise los siguientes datos:</p>
                    <ul className="mt-1 list-disc pl-5">
                        {otrosErrores.map(([campo, mensaje]) => (
                            <li key={campo}>{mensaje}</li>
                        ))}
                    </ul>
                </div>
            )}

            <fieldset disabled={soloLectura} className="m-0 min-w-0 border-0 p-0">
                <legend className="sr-only">{config.titulo}</legend>
                <div className="grid gap-5 p-5 md:grid-cols-2 md:p-6">
                    {campos.map((c) => (
                        <div key={c.nombre} className={cn('min-w-0', anchoCompleto(c) && 'md:col-span-2')}>
                            <ControlCampo
                                campo={c}
                                valores={f.valores}
                                originales={valores}
                                errores={f.errores}
                                set={f.set}
                                validarUno={f.validarUno}
                                esEdicion={esEdicion}
                                soloLectura={soloLectura}
                            />
                        </div>
                    ))}
                    {!campos.length && <p className="text-sm text-muted-foreground md:col-span-2">Este formulario no tiene campos para editar.</p>}
                </div>
            </fieldset>

            <div className="flex flex-wrap items-center justify-between gap-3 border-t bg-muted/40 px-5 py-4 md:px-6">
                <p className="text-xs text-muted-foreground">
                    {soloLectura ? (
                        'Solicite al administrador el permiso de edición si necesita cambiar estos datos.'
                    ) : (
                        <>
                            Los campos con <span className="text-destructive">*</span> son obligatorios. Los cambios quedan registrados en la bitácora.
                        </>
                    )}
                </p>
                <div className="flex flex-wrap gap-2">
                    {rutaListado && (
                        <Link to={rutaListado} className="boton-borde">
                            {soloLectura ? 'Volver al listado' : 'Cancelar'}
                        </Link>
                    )}
                    {!soloLectura && (
                        <button type="submit" className="boton" disabled={f.enviando}>
                            {f.enviando ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : <Save className="size-4" aria-hidden="true" />}
                            {f.enviando ? 'Guardando…' : textoGuardar}
                        </button>
                    )}
                </div>
            </div>

            {progreso !== null && (
                <div className="h-1 bg-muted" role="progressbar" aria-label="Subiendo archivos" aria-valuemin={0} aria-valuemax={100} aria-valuenow={progreso}>
                    <div className="h-1 bg-primary transition-[width]" style={{ width: `${progreso}%` }} />
                </div>
            )}
        </form>
    );
}

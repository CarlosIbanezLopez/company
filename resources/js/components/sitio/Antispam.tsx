import { CampoFormulario } from '@/components/ui/CampoFormulario';
import { cn } from '@/lib/utils';

interface PropsAntispam {
    /** Pregunta de verificación (p. ej. "¿Cuánto es 3 + 4?"); si no hay, no se muestra el campo. */
    pregunta?: string | null;
    respuesta?: string;
    onRespuesta?: (valor: string) => void;
    onSalirRespuesta?: () => void;
    /** Campo trampa: los visitantes no lo ven; si llega con datos, el servidor rechaza el envío. */
    trampa: string;
    onTrampa: (valor: string) => void;
    /** Error general de verificación (token vencido, envío demasiado rápido...). */
    error?: string | null;
    errorRespuesta?: string | null;
    className?: string;
}

/**
 * Protección de formularios públicos sin sesión (API stateless): campo trampa oculto ("sitio_web"),
 * token cifrado con el momento de carga (lo envía la página) y una pregunta de verificación opcional.
 */
export function Antispam({ pregunta, respuesta = '', onRespuesta, onSalirRespuesta, trampa, onTrampa, error, errorRespuesta, className }: PropsAntispam) {
    return (
        <div className={cn('relative', className)}>
            <div className="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
                <label>
                    No completar este campo
                    <input type="text" name="sitio_web" tabIndex={-1} autoComplete="off" value={trampa} onChange={(e) => onTrampa(e.target.value)} />
                </label>
            </div>

            {pregunta && (
                <CampoFormulario
                    etiqueta={`Verificación: ${pregunta}`}
                    requerido
                    error={errorRespuesta}
                    ayuda="Responda con un número para confirmar que no es un envío automático."
                    className="max-w-xs"
                >
                    <input
                        name="antispam_respuesta"
                        type="text"
                        inputMode="numeric"
                        autoComplete="off"
                        className="campo"
                        value={respuesta}
                        onChange={(e) => onRespuesta?.(e.target.value)}
                        onBlur={onSalirRespuesta}
                    />
                </CampoFormulario>
            )}

            {error && (
                <p id="antispam" tabIndex={-1} role="alert" className="mt-2 rounded-lg border-l-4 border-destructive bg-destructive/10 px-3 py-2 text-sm text-destructive outline-none">
                    {error}
                </p>
            )}
        </div>
    );
}

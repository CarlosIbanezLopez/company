import { cloneElement, isValidElement, useId } from 'react';
import type { ReactElement, ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface PropsCampo {
    etiqueta: ReactNode;
    /** Mensaje de error (cliente o servidor). */
    error?: string | null;
    ayuda?: ReactNode;
    requerido?: boolean;
    className?: string;
    /** Un único control (input, select, textarea) que recibe id, aria-invalid y aria-describedby. */
    children: ReactNode;
}

/**
 * Envoltorio accesible de un control de formulario: etiqueta asociada, marca de obligatorio,
 * texto de ayuda y mensaje de error anunciado a lectores de pantalla.
 *   <CampoFormulario etiqueta="Correo" error={f.errores.correo} requerido>
 *       <input name="correo" className="campo" ... />
 *   </CampoFormulario>
 */
export function CampoFormulario({ etiqueta, error, ayuda, requerido, className, children }: PropsCampo) {
    const id = useId();
    const idAyuda = ayuda ? `${id}-ayuda` : undefined;
    const idError = error ? `${id}-error` : undefined;
    const describe = [idAyuda, idError].filter(Boolean).join(' ') || undefined;

    const control = isValidElement(children)
        ? cloneElement(children as ReactElement<Record<string, unknown>>, {
              id: (children.props as { id?: string }).id ?? id,
              'aria-invalid': error ? true : undefined,
              'aria-describedby': describe,
              'aria-required': requerido || undefined,
          })
        : children;

    const idControl = isValidElement(children) ? ((children.props as { id?: string }).id ?? id) : undefined;

    return (
        <div className={cn('min-w-0', className)}>
            <label htmlFor={idControl} className="etiqueta">
                {etiqueta}
                {requerido && (
                    <span className="ml-0.5 text-destructive" aria-hidden="true">
                        *
                    </span>
                )}
            </label>
            {control}
            {ayuda && !error && (
                <p id={idAyuda} className="mt-1 text-xs text-muted-foreground">
                    {ayuda}
                </p>
            )}
            {error && (
                <p id={idError} role="alert" className="error-campo">
                    {error}
                </p>
            )}
        </div>
    );
}

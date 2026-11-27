import { Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';
import type { InputHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type PropsEntrada = Omit<InputHTMLAttributes<HTMLInputElement>, 'type'>;

/**
 * Campo de contraseña con botón para mostrarla u ocultarla.
 * Reenvía id, name y los atributos aria-* al <input> (compatible con CampoFormulario).
 */
export function EntradaContrasena({ className, disabled, ...props }: PropsEntrada) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="relative">
            <input {...props} disabled={disabled} type={visible ? 'text' : 'password'} className={cn('campo pr-11', className)} />
            <button
                type="button"
                onClick={() => setVisible((v) => !v)}
                disabled={disabled}
                aria-pressed={visible}
                aria-label={visible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                title={visible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                className="absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-lg text-muted-foreground hover:text-foreground disabled:opacity-50"
            >
                {visible ? <EyeOff className="size-4" aria-hidden="true" /> : <Eye className="size-4" aria-hidden="true" />}
            </button>
        </div>
    );
}

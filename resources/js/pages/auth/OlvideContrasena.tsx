import { CircleCheck, LoaderCircle, Send } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router';
import { CampoFormulario } from '@/components/ui/CampoFormulario';
import { useFormulario } from '@/hooks/useFormulario';
import { useTitulo } from '@/hooks/useTitulo';
import { AvisoAuth, EncabezadoAuth } from '@/layouts/AuthLayout';
import { api } from '@/lib/api';

/** Solicitud del enlace para restablecer la contraseña (la respuesta no revela si el correo existe). */
export default function OlvideContrasena() {
    useTitulo('Recuperar contraseña');
    const [enviado, setEnviado] = useState<string | null>(null);

    const f = useFormulario({ email: '' }, { reglas: { email: ['requerido', 'email', 'max:255'] }, etiquetas: { email: 'correo electrónico' } });

    const alEnviar = f.alEnviar((datos) => api.post<{ message: string }>('auth/olvide-contrasena', { email: datos.email.trim() }), {
        mensajeExito: false,
        onExito: (respuesta) => setEnviado(respuesta.data?.message || 'Si el correo está registrado, le enviamos un enlace para restablecer la contraseña.'),
    });

    return (
        <>
            <EncabezadoAuth titulo="Recuperar contraseña" descripcion="Ingrese el correo de su cuenta y le enviaremos un enlace para crear una nueva contraseña." />

            {enviado && (
                <AvisoAuth>
                    <span className="flex items-start gap-2">
                        <CircleCheck className="mt-0.5 size-4 shrink-0 text-exito" aria-hidden="true" />
                        <span>
                            {enviado} Revise también la carpeta de correo no deseado. El enlace vence en 60 minutos.
                        </span>
                    </span>
                </AvisoAuth>
            )}

            <form noValidate onSubmit={alEnviar} className="space-y-5">
                <CampoFormulario etiqueta="Correo electrónico" error={f.errores.email} requerido>
                    <input
                        name="email"
                        type="email"
                        value={f.valores.email}
                        onChange={(e) => f.set('email', e.target.value)}
                        onBlur={() => f.validarUno('email')}
                        autoComplete="email"
                        autoFocus
                        placeholder="correo@ejemplo.com"
                        className="campo"
                    />
                </CampoFormulario>

                <button type="submit" className="boton w-full" disabled={f.enviando}>
                    {f.enviando ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : <Send className="size-4" aria-hidden="true" />}
                    {f.enviando ? 'Enviando…' : enviado ? 'Enviar el enlace otra vez' : 'Enviar enlace de restablecimiento'}
                </button>
            </form>

            <p className="mt-8 text-center text-sm text-muted-foreground">
                ¿Recordó su contraseña?{' '}
                <Link to="/login" className="font-medium text-primary hover:underline">
                    Volver a iniciar sesión
                </Link>
            </p>
        </>
    );
}

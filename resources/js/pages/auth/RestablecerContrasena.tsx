import { KeyRound, LoaderCircle, TriangleAlert } from 'lucide-react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router';
import { EntradaContrasena } from '@/components/admin/EntradaContrasena';
import { CampoFormulario } from '@/components/ui/CampoFormulario';
import { useFormulario } from '@/hooks/useFormulario';
import { useTitulo } from '@/hooks/useTitulo';
import { EncabezadoAuth } from '@/layouts/AuthLayout';
import { api } from '@/lib/api';

/** Nueva contraseña a partir del enlace del correo: /restablecer-contrasena/:token?email=... */
export default function RestablecerContrasena() {
    useTitulo('Restablecer contraseña');
    const { token = '' } = useParams();
    const [params] = useSearchParams();
    const emailEnlace = params.get('email') ?? '';
    const navigate = useNavigate();

    const f = useFormulario(
        { email: emailEnlace, password: '', password_confirmation: '' },
        {
            reglas: {
                email: ['requerido', 'email', 'max:255'],
                password: ['requerido', 'min:8', 'max:72', 'confirmado'],
                password_confirmation: ['requerido'],
            },
            etiquetas: { email: 'correo electrónico', password: 'contraseña', password_confirmation: 'confirmación de la contraseña' },
        },
    );

    const alEnviar = f.alEnviar((datos) => api.post<{ message?: string }>('auth/restablecer-contrasena', { token, ...datos, email: datos.email.trim() }), {
        mensajeExito: false,
        onExito: (respuesta) =>
            void navigate('/login', {
                replace: true,
                state: { mensaje: respuesta.data?.message || 'Su contraseña fue restablecida. Ya puede iniciar sesión.' },
            }),
    });

    if (!token) {
        return (
            <>
                <EncabezadoAuth titulo="Enlace no válido" />
                <p className="flex items-start gap-2 text-sm text-muted-foreground">
                    <TriangleAlert className="mt-0.5 size-4 shrink-0 text-aviso" aria-hidden="true" /> El enlace para restablecer la contraseña está incompleto o venció.
                </p>
                <Link to="/olvide-contrasena" className="boton mt-6 w-full">
                    Solicitar un enlace nuevo
                </Link>
            </>
        );
    }

    return (
        <>
            <EncabezadoAuth titulo="Restablecer contraseña" descripcion="Elija una contraseña nueva para su cuenta. Se cerrarán las sesiones abiertas en otros dispositivos." />

            <form noValidate onSubmit={alEnviar} className="space-y-5">
                <CampoFormulario etiqueta="Correo electrónico" error={f.errores.email} requerido>
                    <input
                        name="email"
                        type="email"
                        value={f.valores.email}
                        onChange={(e) => f.set('email', e.target.value)}
                        onBlur={() => f.validarUno('email')}
                        readOnly={Boolean(emailEnlace)}
                        autoComplete="email"
                        className="campo read-only:bg-muted/60"
                    />
                </CampoFormulario>

                <CampoFormulario etiqueta="Nueva contraseña" error={f.errores.password} ayuda="Mínimo 8 caracteres." requerido>
                    <EntradaContrasena
                        name="password"
                        value={f.valores.password}
                        onChange={(e) => f.set('password', e.target.value)}
                        onBlur={() => f.validarUno('password')}
                        autoComplete="new-password"
                        autoFocus={Boolean(emailEnlace)}
                    />
                </CampoFormulario>

                <CampoFormulario etiqueta="Confirmar contraseña" error={f.errores.password_confirmation} requerido>
                    <EntradaContrasena
                        name="password_confirmation"
                        value={f.valores.password_confirmation}
                        onChange={(e) => f.set('password_confirmation', e.target.value)}
                        onBlur={() => f.validarUno('password_confirmation')}
                        autoComplete="new-password"
                    />
                </CampoFormulario>

                <button type="submit" className="boton w-full" disabled={f.enviando}>
                    {f.enviando ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : <KeyRound className="size-4" aria-hidden="true" />}
                    {f.enviando ? 'Guardando…' : 'Restablecer contraseña'}
                </button>
            </form>

            <p className="mt-8 text-center text-sm text-muted-foreground">
                <Link to="/login" className="font-medium text-primary hover:underline">
                    Volver a iniciar sesión
                </Link>
            </p>
        </>
    );
}

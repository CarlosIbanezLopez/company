import axios from 'axios';
import { CircleCheck, Info, LoaderCircle, LogIn } from 'lucide-react';
import { flushSync } from 'react-dom';
import { Link, useLocation, useNavigate, useSearchParams } from 'react-router';
import { toast } from 'sonner';
import { EntradaContrasena } from '@/components/admin/EntradaContrasena';
import { destinoPorDefecto, destinoSeguro, esRutaPanel, guardarDestinoPendiente } from '@/components/admin/panel';
import { CampoFormulario } from '@/components/ui/CampoFormulario';
import { useAuth } from '@/contexts/AuthContext';
import { useFormulario } from '@/hooks/useFormulario';
import { useTitulo } from '@/hooks/useTitulo';
import { AvisoAuth, EncabezadoAuth } from '@/layouts/AuthLayout';
import { api, estadoError } from '@/lib/api';
import type { Usuario } from '@/types';

interface RespuestaLogin {
    message?: string;
    token: string;
    usuario: Usuario;
}

/** Mensaje del bloqueo temporal (429) para mostrarlo en el campo de correo. */
function mensajeBloqueo(error: unknown): string {
    const cuerpo = axios.isAxiosError(error) ? (error.response?.data as { message?: string; errors?: { email?: string[] } } | undefined) : undefined;

    return cuerpo?.errors?.email?.[0] ?? cuerpo?.message ?? 'Demasiados intentos de inicio de sesión. Intente de nuevo en unos segundos.';
}

/**
 * CU1 - Inicio de sesión con correo y contraseña (token Bearer de Sanctum).
 * Valida en el cliente, muestra en el campo de correo los errores 422/429 del servidor y
 * redirige a ?redirigir= (si corresponde) o al inicio según el tipo de cuenta.
 */
export default function Login() {
    useTitulo('Iniciar sesión');
    const { establecerSesion } = useAuth();
    const navigate = useNavigate();
    const [params] = useSearchParams();
    const estado = useLocation().state as { mensaje?: string } | null;
    const redirigir = destinoSeguro(params.get('redirigir'));

    const f = useFormulario(
        { email: '', password: '', recordar: false },
        {
            reglas: { email: ['requerido', 'email', 'max:255'], password: ['requerido'] },
            etiquetas: { email: 'correo electrónico', password: 'contraseña' },
        },
    );

    /** Guarda la sesión y navega al destino. */
    const entrar = ({ token, usuario }: RespuestaLogin) => {
        const porDefecto = destinoPorDefecto(usuario);
        const destino = redirigir && (!esRutaPanel(redirigir) || usuario.panel) ? redirigir : porDefecto;

        // Respaldo: si la guardia de invitados envía primero a /admin, el panel completa la redirección.
        guardarDestinoPendiente(usuario.panel && destino !== porDefecto && esRutaPanel(destino) ? destino : null);

        // La sesión se aplica con un render síncrono y luego se navega: la navegación al destino
        // solicitado prevalece sobre la redirección automática de la guardia de invitados.
        flushSync(() => establecerSesion(token, usuario));
        void navigate(destino, { replace: true });
    };

    const alEnviar = f.alEnviar(
        async (datos) => {
            try {
                const { data } = await api.post<RespuestaLogin>('auth/login', {
                    email: datos.email.trim(),
                    password: datos.password,
                    recordar: datos.recordar,
                });

                return data;
            } catch (error) {
                // 429: bloqueo temporal por demasiados intentos (los 422 los asigna el formulario).
                if (estadoError(error) === 429) {
                    f.setErrores({ email: mensajeBloqueo(error) });
                    requestAnimationFrame(() => document.querySelector<HTMLInputElement>('input[name="email"]')?.focus());
                }

                throw error;
            }
        },
        {
            mensajeExito: false,
            onExito: (respuesta) => {
                toast.success(`¡Bienvenido, ${respuesta.usuario.name}!`);
                entrar(respuesta);
            },
        },
    );

    return (
        <>
            <EncabezadoAuth titulo="Iniciar sesión" descripcion="Ingrese su correo y contraseña para acceder al sistema." />

            {estado?.mensaje ? (
                <AvisoAuth>
                    <span className="flex items-start gap-2">
                        <CircleCheck className="mt-0.5 size-4 shrink-0 text-exito" aria-hidden="true" /> {estado.mensaje}
                    </span>
                </AvisoAuth>
            ) : (
                redirigir && (
                    <AvisoAuth tono="info">
                        <span className="flex items-start gap-2">
                            <Info className="mt-0.5 size-4 shrink-0 text-primary" aria-hidden="true" /> Inicie sesión para continuar con la página solicitada.
                        </span>
                    </AvisoAuth>
                )
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

                <div>
                    <CampoFormulario etiqueta="Contraseña" error={f.errores.password} requerido>
                        <EntradaContrasena
                            name="password"
                            value={f.valores.password}
                            onChange={(e) => f.set('password', e.target.value)}
                            onBlur={() => f.validarUno('password')}
                            autoComplete="current-password"
                            placeholder="Contraseña"
                        />
                    </CampoFormulario>
                    <p className="mt-1.5 text-right">
                        <Link to="/olvide-contrasena" className="text-sm font-medium text-primary hover:underline">
                            ¿Olvidó su contraseña?
                        </Link>
                    </p>
                </div>

                <label className="flex cursor-pointer items-start gap-3 text-sm">
                    <input
                        type="checkbox"
                        name="recordar"
                        checked={f.valores.recordar}
                        onChange={(e) => f.set('recordar', e.target.checked)}
                        className="mt-0.5 size-4 shrink-0 accent-[var(--primary)]"
                    />
                    <span>
                        Recordarme en este equipo
                        <span className="block text-xs text-muted-foreground">Mantiene la sesión abierta hasta 30 días. No lo active en equipos compartidos.</span>
                    </span>
                </label>

                <button type="submit" className="boton w-full" disabled={f.enviando}>
                    {f.enviando ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : <LogIn className="size-4" aria-hidden="true" />}
                    {f.enviando ? 'Ingresando…' : 'Ingresar'}
                </button>
            </form>

            <p className="mt-8 text-center text-sm text-muted-foreground">
                ¿Es graduado de la Carrera?{' '}
                <Link to="/graduados" className="font-medium text-primary hover:underline">
                    Regístrese en el Programa Alumni
                </Link>
            </p>
        </>
    );
}

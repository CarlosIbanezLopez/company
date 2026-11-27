<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\Bitacora;
use App\Models\InicioSesion;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaContrasena;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Autenticación de la API con tokens Bearer de Laravel Sanctum (RF-18).
 *
 * Cada intento de inicio de sesión (exitoso, fallido o bloqueado) se inserta en `inicios_sesion`;
 * el trigger `trg_inicios_sesion_usuario` de la base de datos actualiza `users.ultimo_acceso`
 * e `users.intentos_fallidos` (la aplicación no los modifica por su cuenta).
 */
class AuthController extends Controller
{
    /** Intentos fallidos permitidos por minuto para un mismo correo + IP. */
    public const MAX_INTENTOS = 5;

    /** Duración de la ventana del límite de intentos (segundos). */
    public const VENTANA_SEGUNDOS = 60;

    public const MENSAJE_CREDENCIALES = 'Las credenciales no coinciden con nuestros registros.';

    public const MENSAJE_DESHABILITADA = 'Su cuenta está deshabilitada. Contacte al administrador.';

    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'recordar' => ['nullable', 'boolean'],
        ]);

        $email = Str::lower(trim($datos['email']));
        $usuario = $this->buscarPorCorreo($email);
        $clave = 'login|'.Str::transliterate($email).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            $segundos = max(1, RateLimiter::availableIn($clave));
            $this->registrarIntento($request, $email, $usuario, false, 'Bloqueo temporal por demasiados intentos');
            $mensaje = "Demasiados intentos de inicio de sesión. Intente de nuevo en {$segundos} segundos.";

            return response()->json(['message' => $mensaje, 'errors' => ['email' => [$mensaje]]], 429, ['Retry-After' => $segundos]);
        }

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            RateLimiter::hit($clave, self::VENTANA_SEGUNDOS);
            $this->registrarIntento($request, $email, $usuario, false, $usuario ? 'Credenciales incorrectas' : 'Correo no registrado');

            throw ValidationException::withMessages(['email' => self::MENSAJE_CREDENCIALES]);
        }

        if (! $usuario->activo) {
            RateLimiter::hit($clave, self::VENTANA_SEGUNDOS);
            $this->registrarIntento($request, $email, $usuario, false, 'Cuenta deshabilitada');

            throw ValidationException::withMessages(['email' => self::MENSAJE_DESHABILITADA]);
        }

        RateLimiter::clear($clave);
        $this->registrarIntento($request, $email, $usuario, true, null);

        $token = $usuario->emitirToken($request->boolean('recordar'), $request->userAgent());

        Bitacora::registrar('inicio_sesion', 'Autenticación', 'Inició sesión: '.$usuario->email, $usuario->id, $usuario->id);

        // La bitácora de tráfico (RegistrarAcceso) asocia esta solicitud al usuario que acaba de entrar.
        $request->attributes->set('acceso.user_id', $usuario->id);

        return response()->json([
            'message' => 'Sesión iniciada correctamente.',
            'token' => $token,
            // refresh(): el trigger acaba de actualizar ultimo_acceso e intentos_fallidos.
            'usuario' => UsuarioResource::make($usuario->refresh())->resolve(),
        ]);
    }

    /**
     * Cierra la sesión revocando el token actual (o todos los del usuario con `todos=true`).
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();

        if ($request->boolean('todos')) {
            $usuario->revocarTokens();
        } else {
            $usuario->revocarTokenActual();
        }

        Bitacora::registrar('cierre_sesion', 'Autenticación', 'Cerró sesión: '.$usuario->email, $usuario->id, $usuario->id);

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function usuario(Request $request): JsonResponse
    {
        return response()->json(['usuario' => UsuarioResource::make($request->user())->resolve()]);
    }

    /**
     * Envía el enlace para restablecer la contraseña. La respuesta es siempre la misma
     * para no revelar qué correos están registrados.
     */
    public function olvide(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $usuario = $this->buscarPorCorreo($datos['email']);

        if ($usuario?->activo) {
            try {
                Password::broker()->sendResetLink(['email' => $usuario->email]);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return response()->json(['message' => 'Si el correo está registrado, le enviamos un enlace para restablecer la contraseña.']);
    }

    /**
     * Restablece la contraseña con el token recibido por correo y revoca todas las sesiones abiertas.
     */
    public function restablecer(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', ReglaContrasena::defaults()],
        ]);

        $credenciales = [
            'email' => $this->buscarPorCorreo($datos['email'])->email ?? $datos['email'],
            'token' => $datos['token'],
            'password' => $datos['password'],
        ];

        $estado = Password::broker()->reset($credenciales, function (User $usuario, string $clave) {
            $usuario->forceFill(['password' => $clave, 'remember_token' => Str::random(60)])->saveQuietly();
            $usuario->revocarTokens();

            Bitacora::registrar('editar', 'Autenticación', 'Restableció su contraseña con el enlace enviado por correo: '.$usuario->email, $usuario->id, $usuario->id);

            event(new PasswordReset($usuario));
        });

        if ($estado !== Password::PASSWORD_RESET) {
            // Correo inexistente o token inválido: el mismo mensaje para no revelar cuentas.
            throw ValidationException::withMessages(['email' => __('passwords.token')]);
        }

        return response()->json(['message' => __('passwords.reset')]);
    }

    /** Búsqueda del usuario sin distinguir mayúsculas (PostgreSQL compara texto de forma exacta). */
    private function buscarPorCorreo(string $email): ?User
    {
        return User::query()
            ->whereRaw('lower(email) = ?', [Str::lower(trim($email))])
            ->orderBy('id')
            ->first();
    }

    private function registrarIntento(Request $request, string $email, ?User $usuario, bool $exitoso, ?string $motivo): void
    {
        InicioSesion::query()->forceCreate([
            'user_id' => $usuario?->id,
            'email' => Str::limit($email, 150, ''),
            'exitoso' => $exitoso,
            'motivo' => $motivo,
            'ip' => $request->ip(),
            'agente' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);
    }
}

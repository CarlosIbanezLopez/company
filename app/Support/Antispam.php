<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Protección de formularios públicos contra envíos automatizados (RF-15) para una API sin sesión:
 * campo trampa oculto ("sitio_web"), tiempo mínimo de llenado y una pregunta aritmética opcional.
 *
 * El token es un texto cifrado y firmado con la clave de la aplicación (Crypt) que contiene el
 * momento en que se generó, el formulario al que pertenece y la respuesta esperada de la pregunta:
 * el navegador no puede leerlo ni alterarlo. Tras un envío exitoso el token se "consume" (queda
 * anotado en la caché hasta que vence) para que no pueda reutilizarse.
 */
class Antispam
{
    /** Segundos mínimos entre la carga del formulario y su envío. */
    public const SEGUNDOS_MINIMOS = 3;

    /** Vigencia del token en segundos (2 horas, igual que la antigua sesión). */
    public const SEGUNDOS_MAXIMOS = 7200;

    private const MENSAJE = 'No pudimos verificar el envío. Recargue la página e intente de nuevo.';

    /**
     * Datos que la página entrega al formulario y que este debe reenviar
     * (antispam_token y, si hay pregunta, antispam_respuesta).
     *
     * @return array{token: string, pregunta?: string}
     */
    public static function generar(string $formulario, bool $conPregunta = true): array
    {
        $respuesta = null;
        $pregunta = null;

        if ($conPregunta) {
            $a = random_int(1, 9);
            $b = random_int(1, 9);
            $respuesta = $a + $b;
            $pregunta = "¿Cuánto es $a + $b?";
        }

        $token = Crypt::encryptString((string) json_encode([
            'momento' => now()->getTimestamp(),
            'formulario' => $formulario,
            'respuesta' => $respuesta,
            'id' => Str::random(24),
        ]));

        return $pregunta === null ? ['token' => $token] : ['token' => $token, 'pregunta' => $pregunta];
    }

    /**
     * Comprueba el campo trampa, el token (formulario, tiempo mínimo y vigencia) y la respuesta.
     * Si algo falla lanza una ValidationException (respuesta 422 con mensajes en español).
     */
    public static function validar(Request $request, string $formulario, bool $conPregunta = true): void
    {
        if (filled($request->input('sitio_web'))) {
            self::rechazar('antispam', self::MENSAJE);
        }

        $datos = self::leer($request->input('antispam_token'));

        if ($datos === null || $datos['formulario'] !== $formulario || ($conPregunta && $datos['respuesta'] === null)) {
            self::rechazar('antispam', self::MENSAJE);
        }

        $transcurrido = now()->getTimestamp() - $datos['momento'];

        if ($transcurrido < self::SEGUNDOS_MINIMOS) {
            self::rechazar('antispam', 'El formulario se envió demasiado rápido. Intente nuevamente.');
        }

        if ($transcurrido > self::SEGUNDOS_MAXIMOS || Cache::has(self::claveUsado($datos['id']))) {
            self::rechazar('antispam', 'El formulario expiró. Recargue la página e intente de nuevo.');
        }

        if ($datos['respuesta'] !== null && filter_var($request->input('antispam_respuesta'), FILTER_VALIDATE_INT) !== $datos['respuesta']) {
            self::rechazar('antispam_respuesta', 'La respuesta de verificación no es correcta.');
        }
    }

    /** Marca el token del envío como usado (llamar después de guardar con éxito). */
    public static function consumir(Request $request): void
    {
        $datos = self::leer($request->input('antispam_token'));

        if ($datos !== null) {
            Cache::put(self::claveUsado($datos['id']), true, self::SEGUNDOS_MAXIMOS);
        }
    }

    /**
     * Descifra un token. Devuelve null si no es válido o fue alterado.
     *
     * @return array{momento: int, formulario: string, respuesta: int|null, id: string}|null
     */
    public static function leer(mixed $token): ?array
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        try {
            $datos = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($datos) || ! isset($datos['momento'], $datos['formulario'], $datos['id'])) {
            return null;
        }

        return [
            'momento' => (int) $datos['momento'],
            'formulario' => (string) $datos['formulario'],
            'respuesta' => isset($datos['respuesta']) ? (int) $datos['respuesta'] : null,
            'id' => (string) $datos['id'],
        ];
    }

    private static function claveUsado(string $id): string
    {
        return 'antispam.usado.'.$id;
    }

    private static function rechazar(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}

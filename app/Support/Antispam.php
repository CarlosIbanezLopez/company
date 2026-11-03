<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/**
 * Protección de formularios públicos contra mensajes automatizados (RF-15):
 * campo trampa oculto, tiempo mínimo de llenado y una pregunta aritmética.
 */
class Antispam
{
    public const SEGUNDOS_MINIMOS = 3;

    /** Datos que el formulario debe reenviar. */
    public static function generar(string $formulario, bool $conPregunta = true): array
    {
        $datos = ['token' => Crypt::encryptString((string) time())];

        if ($conPregunta) {
            $a = random_int(1, 9);
            $b = random_int(1, 9);
            session()->put("antispam.$formulario", $a + $b);
            $datos['pregunta'] = "¿Cuánto es $a + $b?";
        }

        return $datos;
    }

    public static function validar(Request $request, string $formulario, bool $conPregunta = true): void
    {
        $mensaje = 'No pudimos verificar el envío. Recargue la página e intente de nuevo.';

        if (filled($request->input('sitio_web'))) {
            throw ValidationException::withMessages(['antispam' => $mensaje]);
        }

        try {
            $momento = (int) Crypt::decryptString((string) $request->input('antispam_token'));
        } catch (DecryptException) {
            throw ValidationException::withMessages(['antispam' => $mensaje]);
        }

        if (time() - $momento < self::SEGUNDOS_MINIMOS) {
            throw ValidationException::withMessages(['antispam' => 'El formulario se envió demasiado rápido. Intente nuevamente.']);
        }

        if ($conPregunta) {
            $esperado = session()->pull("antispam.$formulario");

            if ($esperado === null || (int) $request->input('antispam_respuesta') !== (int) $esperado) {
                throw ValidationException::withMessages(['antispam_respuesta' => 'La respuesta de verificación no es correcta.']);
            }
        }
    }
}

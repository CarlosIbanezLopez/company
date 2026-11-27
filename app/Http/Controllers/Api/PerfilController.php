<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Cuenta del usuario autenticado: datos personales, contraseña y preferencias de accesibilidad.
 */
class PerfilController extends Controller
{
    public const TEMAS = ['ninos', 'jovenes', 'adultos'];

    public const MODOS = ['auto', 'dia', 'noche'];

    public function update(Request $request): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();

        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        }

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'telefono' => ['nullable', 'string', 'max:20'],
        ]);

        $usuario->fill($datos);

        if ($usuario->isDirty('email')) {
            $usuario->email_verified_at = null;
        }

        $usuario->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'usuario' => UsuarioResource::make($usuario)->resolve(),
        ]);
    }

    public function contrasena(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password:sanctum'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::defaults()],
        ]);

        /** @var User $usuario */
        $usuario = $request->user();
        $usuario->forceFill(['password' => $request->input('password')])->saveQuietly();

        // Las demás sesiones abiertas (otros dispositivos) se cierran; la actual se conserva.
        $usuario->revocarTokens(conservarActual: true);

        Bitacora::registrar('editar', 'Usuarios', 'Cambió su contraseña: '.$usuario->email, $usuario->id, $usuario->id);

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    /**
     * Guarda las preferencias de apariencia (tema por edad, modo día/noche, tamaño de fuente y alto contraste).
     * Acepta cambios parciales: los campos omitidos conservan su valor anterior.
     */
    public function preferencias(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'tema' => ['sometimes', 'required', 'string', Rule::in(self::TEMAS)],
            'modo' => ['sometimes', 'required', 'string', Rule::in(self::MODOS)],
            'fuente' => ['sometimes', 'required', 'integer', 'between:85,150'],
            'contraste' => ['sometimes', 'required', 'boolean'],
        ]);

        /** @var User $usuario */
        $usuario = $request->user();

        $preferencias = UsuarioResource::preferencias(array_merge(
            UsuarioResource::PREFERENCIAS_POR_DEFECTO,
            $usuario->preferencias ?? [],
            $datos,
        ));

        // Sin eventos de modelo: un cambio de apariencia no es una operación administrativa para la bitácora.
        $usuario->forceFill(['preferencias' => $preferencias])->saveQuietly();

        return response()->json([
            'message' => 'Preferencias guardadas correctamente.',
            'preferencias' => $preferencias,
        ]);
    }
}

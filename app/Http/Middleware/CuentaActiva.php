<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\AuthController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta las sesiones de cuentas deshabilitadas por el Administrador después de iniciar sesión:
 * revoca el token usado y responde 403 (se aplica a las rutas autenticadas fuera del panel).
 */
class CuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->activo) {
            $usuario->revocarTokenActual();

            return response()->json(['message' => AuthController::MENSAJE_DESHABILITADA], 403);
        }

        return $next($request);
    }
}

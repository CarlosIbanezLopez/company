<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo deja pasar al panel de administración a cuentas habilitadas cuyo rol tiene acceso.
 * Si la cuenta fue deshabilitada, además revoca el token con el que se hizo la solicitud.
 */
class AccesoPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            throw new AuthenticationException;
        }

        if (! $usuario->activo) {
            $usuario->revocarTokenActual();

            return response()->json(['message' => 'Su cuenta está deshabilitada.'], 403);
        }

        if (! $usuario->accesoPanel()) {
            return response()->json(['message' => 'Su rol no tiene acceso al panel de administración.'], 403);
        }

        return $next($request);
    }
}

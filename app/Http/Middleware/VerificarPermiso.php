<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Matriz de control de acceso (RBAC): restringe una ruta a los roles que tienen el permiso
 * "{recurso}.{accion}" indicado, por ejemplo `permiso:noticias.editar` (RF-18).
 * Con varios permisos (`permiso:noticias.ver,eventos.ver`) basta con tener uno.
 */
class VerificarPermiso
{
    public const MENSAJE = 'No tiene permiso para realizar esta acción.';

    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            throw new AuthenticationException;
        }

        foreach ($permisos as $permiso) {
            if ($usuario->tienePermiso(trim($permiso))) {
                return $next($request);
            }
        }

        return response()->json(['message' => self::MENSAJE], 403);
    }
}

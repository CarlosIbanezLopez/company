<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a los usuarios cuyo rol tiene el permiso indicado (RF-18).
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        abort_unless($request->user()?->tienePermiso($permiso), 403, 'No tiene permiso para acceder a esta sección.');

        return $next($request);
    }
}

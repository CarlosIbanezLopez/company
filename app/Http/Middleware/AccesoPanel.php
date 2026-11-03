<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solo permite entrar al panel a cuentas habilitadas cuyo rol tiene acceso.
 * Los graduados son enviados a su ficha personal.
 */
class AccesoPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario?->activo) {
            Auth::guard('web')->logout();

            return redirect()->route('login')->withErrors(['email' => 'Su cuenta está deshabilitada.']);
        }

        if (! $usuario->accesoPanel()) {
            return redirect()->route('graduados.ficha');
        }

        return $next($request);
    }
}

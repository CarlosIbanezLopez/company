<?php

namespace App\Http\Middleware;

use App\Models\ContadorVisita;
use App\Models\VisitaDiaria;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Contador de visitas por página: suma una visita cada vez que se abre una página
 * y deja el total disponible para mostrarlo en el pie de página.
 */
class ContarVisita
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->debeContar($request)) {
            $ruta = Str::limit('/'.ltrim($request->path(), '/'), 190, '');

            try {
                $contador = ContadorVisita::query()->firstOrCreate(['ruta' => $ruta], ['total' => 0]);
                $contador->increment('total', 1, ['ultima_visita' => now()]);

                $diaria = VisitaDiaria::query()->firstOrCreate(['fecha' => today()->toDateString(), 'ruta' => $ruta], ['total' => 0]);
                $diaria->increment('total');

                // Se comparte con Vue para mostrarlo en el pie de página.
                Inertia::share('visitas', $contador->total);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $next($request);
    }

    private function debeContar(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        // Las precargas y recargas parciales de Inertia no son visitas nuevas.
        if ($request->header('Purpose') === 'prefetch' || $request->header('X-Inertia-Partial-Data') || $request->header('X-Inertia-Partial-Component')) {
            return false;
        }

        return true;
    }
}

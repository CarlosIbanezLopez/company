<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\ContadorVisita;
use App\Models\Visita;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Contador de visitas por página (RF-20). La SPA informa cada página que se abre;
 * aquí solo se inserta la visita en la tabla "visitas": el trigger de la base de datos
 * (trg_visitas_contador) actualiza contador_visitas y visitas_diarias.
 */
class VisitaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ruta' => ['required', 'string', 'max:191'],
        ]);

        $ruta = self::normalizar($request->string('ruta')->toString());

        Visita::query()->create([
            'ruta' => $ruta,
            'user_id' => $request->user('sanctum')?->getAuthIdentifier(),
            'ip' => $request->ip(),
            'agente' => Str::limit((string) $request->userAgent(), 255, ''),
        ]);

        return response()->json([
            'ruta' => $ruta,
            'total' => (int) ContadorVisita::query()->where('ruta', $ruta)->value('total'),
        ], 201);
    }

    /**
     * Deja la ruta como "/seccion/slug": sin dominio, sin parámetros (?q=) ni fragmento (#),
     * con barra inicial, sin barras repetidas ni barra final y con 191 caracteres como máximo.
     */
    public static function normalizar(string $ruta): string
    {
        $ruta = trim($ruta);
        $ruta = preg_replace('#^[a-z][a-z0-9+.\-]*://[^/?\#]*#i', '', $ruta) ?? $ruta;
        $ruta = preg_replace('/[?#].*$/s', '', $ruta) ?? $ruta;
        $ruta = preg_replace('#/+#', '/', $ruta) ?? $ruta;
        $ruta = '/'.trim($ruta, '/ ');

        return mb_substr($ruta, 0, 191);
    }
}

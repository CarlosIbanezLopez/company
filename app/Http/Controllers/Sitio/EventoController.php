<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Support\Catalogos;
use App\Support\Markdown;
use Illuminate\Http\JsonResponse;

/**
 * Eventos académicos: los próximos se muestran automáticamente y los finalizados pasan al historial (RF-13).
 */
class EventoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'proximos' => Evento::query()->where('publicado', true)->whereDate('fecha', '>=', today())
                ->orderBy('fecha')->orderBy('hora_inicio')->get(),
            'historial' => Evento::query()->where('publicado', true)->whereDate('fecha', '<', today())
                ->orderByDesc('fecha')->orderByDesc('id')->paginate(9),
            'tipos' => Catalogos::TIPOS_EVENTO,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $evento = Evento::query()->where('slug', $slug)->where('publicado', true)->firstOrFail();

        return response()->json([
            'evento' => [
                ...$evento->toArray(),
                'html' => Markdown::html($evento->descripcion),
                'finalizado' => $evento->fecha->isBefore(today()),
            ],
            'tipos' => Catalogos::TIPOS_EVENTO,
        ]);
    }
}

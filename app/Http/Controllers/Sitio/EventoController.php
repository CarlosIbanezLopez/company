<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Support\Catalogos;
use App\Support\Markdown;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Eventos académicos: los próximos se muestran automáticamente y los finalizados pasan al historial (RF-13).
 */
class EventoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('sitio/Eventos', [
            'proximos' => Evento::query()->where('publicado', true)->whereDate('fecha', '>=', today())->orderBy('fecha')->orderBy('hora_inicio')->get(),
            'historial' => Evento::query()->where('publicado', true)->whereDate('fecha', '<', today())->orderByDesc('fecha')->paginate(9),
            'tipos' => Catalogos::TIPOS_EVENTO,
        ]);
    }

    public function show(string $slug): Response
    {
        $evento = Evento::query()->where('slug', $slug)->where('publicado', true)->firstOrFail();

        return Inertia::render('sitio/Evento', [
            'evento' => [...$evento->toArray(), 'html' => Markdown::html($evento->descripcion), 'finalizado' => $evento->fecha->isBefore(today())],
            'tipos' => Catalogos::TIPOS_EVENTO,
        ]);
    }
}

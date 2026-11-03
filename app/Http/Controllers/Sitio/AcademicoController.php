<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\ActividadCalendario;
use App\Models\PreguntaFrecuente;
use App\Support\Catalogos;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Calendario académico y preguntas frecuentes (RF-05).
 */
class AcademicoController extends Controller
{
    public function calendario(Request $request): Response
    {
        $gestiones = ActividadCalendario::query()->distinct()->orderByDesc('gestion')->pluck('gestion');
        $gestion = $request->query('gestion', $gestiones->first());

        return Inertia::render('sitio/Calendario', [
            'actividades' => ActividadCalendario::query()->where('gestion', $gestion)->orderBy('fecha_inicio')->get(),
            'gestiones' => $gestiones,
            'gestion' => $gestion,
            'tipos' => Catalogos::TIPOS_CALENDARIO,
        ]);
    }

    public function preguntas(): Response
    {
        return Inertia::render('sitio/Preguntas', [
            'grupos' => PreguntaFrecuente::query()->where('activo', true)->orderBy('orden')->get()
                ->groupBy(fn ($p) => $p->categoria ?: 'General'),
        ]);
    }
}

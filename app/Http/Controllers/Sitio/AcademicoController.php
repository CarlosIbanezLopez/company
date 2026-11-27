<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sitio\Concerns\FiltrosConsulta;
use App\Models\ActividadCalendario;
use App\Models\PreguntaFrecuente;
use App\Support\Catalogos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Calendario académico y preguntas frecuentes (RF-05).
 */
class AcademicoController extends Controller
{
    use FiltrosConsulta;

    public function calendario(Request $request): JsonResponse
    {
        $gestiones = ActividadCalendario::query()->distinct()->orderByDesc('gestion')->pluck('gestion');
        $gestion = $this->texto($request, 'gestion', 20) ?? $gestiones->first();

        return response()->json([
            'actividades' => ActividadCalendario::query()->where('gestion', $gestion)->orderBy('fecha_inicio')->orderBy('id')->get(),
            'gestiones' => $gestiones,
            'gestion' => $gestion,
            'tipos' => Catalogos::TIPOS_CALENDARIO,
        ]);
    }

    public function preguntas(): JsonResponse
    {
        $grupos = PreguntaFrecuente::query()->where('activo', true)->orderBy('orden')->orderBy('id')->get()
            ->groupBy(fn (PreguntaFrecuente $p) => $p->categoria ?: 'General');

        return response()->json([
            // Objeto { categoría: [preguntas] } (también cuando está vacío).
            'grupos' => (object) $grupos->all(),
        ]);
    }
}

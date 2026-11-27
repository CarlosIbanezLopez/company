<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sitio\Concerns\FiltrosConsulta;
use App\Models\LineaInvestigacion;
use App\Models\ProyectoInvestigacion;
use App\Support\Catalogos;
use App\Support\Markdown;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Líneas y proyectos de investigación (RF-07).
 */
class InvestigacionController extends Controller
{
    use FiltrosConsulta;

    public function index(Request $request): JsonResponse
    {
        $tipo = $this->texto($request, 'tipo', 20);
        $linea = $this->entero($request, 'linea');
        $estado = $this->texto($request, 'estado', 20);

        return response()->json([
            'lineas' => LineaInvestigacion::query()->where('activo', true)
                ->withCount(['proyectos' => fn ($q) => $q->where('publicado', true)])
                ->orderBy('nombre')->get(),
            'proyectos' => ProyectoInvestigacion::query()->with('linea:id,nombre')->where('publicado', true)
                ->when($tipo, fn ($q, $tipo) => $q->where('tipo', $tipo))
                ->when($linea, fn ($q, $linea) => $q->where('linea_investigacion_id', $linea))
                ->when($estado, fn ($q, $estado) => $q->where('estado', $estado))
                // Sin fecha al final también en PostgreSQL (que por defecto ordena los NULL primero en DESC).
                ->orderByRaw('fecha_inicio is null')->orderByDesc('fecha_inicio')->orderByDesc('id')->get(),
            'estados' => Catalogos::ESTADOS_PROYECTO,
            'tipos' => Catalogos::TIPOS_INVESTIGACION,
            // Los filtros se devuelven como texto, igual que llegan en la URL.
            'filtros' => (object) array_filter([
                'tipo' => $tipo,
                'linea' => $linea !== null ? (string) $linea : null,
                'estado' => $estado,
            ]),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $proyecto = ProyectoInvestigacion::query()->with('linea')->where('slug', $slug)->where('publicado', true)->firstOrFail();

        return response()->json([
            'proyecto' => [...$proyecto->toArray(), 'html' => Markdown::html($proyecto->descripcion)],
            'estados' => Catalogos::ESTADOS_PROYECTO,
            'tipos' => Catalogos::TIPOS_INVESTIGACION,
        ]);
    }
}

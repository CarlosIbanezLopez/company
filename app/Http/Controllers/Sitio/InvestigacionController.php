<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\LineaInvestigacion;
use App\Models\ProyectoInvestigacion;
use App\Support\Catalogos;
use App\Support\Markdown;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Líneas y proyectos de investigación (RF-07).
 */
class InvestigacionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('sitio/Investigacion', [
            'lineas' => LineaInvestigacion::query()->where('activo', true)->withCount(['proyectos' => fn ($q) => $q->where('publicado', true)])->orderBy('nombre')->get(),
            'proyectos' => ProyectoInvestigacion::query()->with('linea:id,nombre')->where('publicado', true)
                ->when($request->query('tipo'), fn ($q, $tipo) => $q->where('tipo', $tipo))
                ->when($request->query('linea'), fn ($q, $linea) => $q->where('linea_investigacion_id', $linea))
                ->when($request->query('estado'), fn ($q, $estado) => $q->where('estado', $estado))
                ->orderByDesc('fecha_inicio')->get(),
            'estados' => Catalogos::ESTADOS_PROYECTO,
            'tipos' => Catalogos::TIPOS_INVESTIGACION,
            'filtros' => $request->only(['tipo', 'linea', 'estado']),
        ]);
    }

    public function show(string $slug): Response
    {
        $proyecto = ProyectoInvestigacion::query()->with('linea')->where('slug', $slug)->where('publicado', true)->firstOrFail();

        return Inertia::render('sitio/Proyecto', [
            'proyecto' => [...$proyecto->toArray(), 'html' => Markdown::html($proyecto->descripcion)],
            'estados' => Catalogos::ESTADOS_PROYECTO,
            'tipos' => Catalogos::TIPOS_INVESTIGACION,
        ]);
    }
}

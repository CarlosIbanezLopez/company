<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Malla curricular interactiva por semestre con prerrequisitos y materias posteriores (RF-04).
 */
class MallaController extends Controller
{
    public function index(): Response
    {
        $materias = Materia::query()->with(['prerequisitos:id', 'posteriores:id'])->orderBy('semestre')->orderBy('sigla')->get()
            ->map(fn (Materia $m) => [
                'id' => $m->id,
                'sigla' => $m->sigla,
                'nombre' => $m->nombre,
                'semestre' => $m->semestre,
                'creditos' => $m->creditos,
                'horas_teoricas' => $m->horas_teoricas,
                'horas_practicas' => $m->horas_practicas,
                'tipo' => $m->tipo,
                'descripcion' => $m->descripcion,
                'prerequisitos' => $m->prerequisitos->pluck('id'),
                'posteriores' => $m->posteriores->pluck('id'),
            ]);

        return Inertia::render('sitio/Malla', [
            'materias' => $materias,
            'semestres' => $materias->pluck('semestre')->unique()->sort()->values(),
        ]);
    }
}

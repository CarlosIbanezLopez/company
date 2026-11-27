<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use Illuminate\Http\JsonResponse;

/**
 * Malla curricular interactiva por semestre con prerrequisitos y materias posteriores (RF-04).
 * La SPA resalta la materia indicada en /malla-curricular?materia={id}.
 */
class MallaController extends Controller
{
    public function index(): JsonResponse
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
                'prerequisitos' => $m->prerequisitos->pluck('id')->values(),
                'posteriores' => $m->posteriores->pluck('id')->values(),
            ]);

        return response()->json([
            'materias' => $materias,
            'semestres' => $materias->pluck('semestre')->unique()->sort()->values(),
        ]);
    }
}

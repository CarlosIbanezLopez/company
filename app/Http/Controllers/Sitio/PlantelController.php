<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Autoridades y docentes (RF-03). Solo se publica la información autorizada.
 */
class PlantelController extends Controller
{
    public function index(Request $request): Response
    {
        $docentes = Docente::query()->where('visible', true)
            ->when($request->query('q'), fn ($q, $texto) => $q->where(fn ($w) => $w->whereLike('nombre', "%$texto%")->orWhereLike('especialidad', "%$texto%")->orWhereLike('materias', "%$texto%")))
            ->orderBy('orden')->orderBy('nombre')->get()
            ->map(fn (Docente $d) => [
                'id' => $d->id,
                'nombre' => $d->nombre,
                'tipo' => $d->tipo,
                'cargo' => $d->cargo,
                'especialidad' => $d->especialidad,
                'materias' => $d->materias,
                'formacion' => $d->mostrar_formacion ? $d->formacion : null,
                'correo' => $d->mostrar_correo ? $d->correo : null,
                'foto_url' => $d->toArray()['foto_url'],
            ]);

        return Inertia::render('sitio/Plantel', [
            'autoridades' => $docentes->where('tipo', 'autoridad')->values(),
            'docentes' => $docentes->where('tipo', '!=', 'autoridad')->values(),
            'q' => $request->query('q'),
        ]);
    }
}

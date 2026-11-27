<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sitio\Concerns\FiltrosConsulta;
use App\Models\Docente;
use App\Support\Archivo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Autoridades y docentes (RF-03). Solo se publica la información autorizada.
 */
class PlantelController extends Controller
{
    use FiltrosConsulta;

    public function index(Request $request): JsonResponse
    {
        $texto = $this->texto($request, 'q');

        $docentes = Docente::query()->where('visible', true)
            ->when($texto, fn ($q, $texto) => $this->contiene($q, ['nombre', 'especialidad', 'materias'], $texto))
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
                'foto_url' => Archivo::url($d->foto),
            ]);

        return response()->json([
            'autoridades' => $docentes->where('tipo', 'autoridad')->values(),
            'docentes' => $docentes->where('tipo', '!=', 'autoridad')->values(),
            'q' => $texto,
        ]);
    }
}

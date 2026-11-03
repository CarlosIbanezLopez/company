<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Convenio;
use App\Support\Catalogos;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Convenios institucionales y relaciones externas (RF-09).
 */
class VinculacionController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('sitio/Vinculacion', [
            'convenios' => Convenio::query()->where('publicado', true)
                ->when($request->query('tipo'), fn ($q, $tipo) => $q->where('tipo', $tipo))
                ->orderByRaw("case when estado = 'vigente' then 0 else 1 end")
                ->orderByDesc('fecha_inicio')->get(),
            'tipos' => Catalogos::TIPOS_CONVENIO,
            'estados' => Catalogos::ESTADOS_CONVENIO,
            'tipo' => $request->query('tipo'),
        ]);
    }
}

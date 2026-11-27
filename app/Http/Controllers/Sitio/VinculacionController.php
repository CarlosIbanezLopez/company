<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sitio\Concerns\FiltrosConsulta;
use App\Models\Convenio;
use App\Support\Catalogos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Convenios institucionales y relaciones externas (RF-09).
 */
class VinculacionController extends Controller
{
    use FiltrosConsulta;

    public function index(Request $request): JsonResponse
    {
        $tipo = $this->texto($request, 'tipo', 30);

        return response()->json([
            'convenios' => Convenio::query()->where('publicado', true)
                ->when($tipo, fn ($q, $tipo) => $q->where('tipo', $tipo))
                ->orderByRaw("case when estado = 'vigente' then 0 else 1 end")
                ->orderByRaw('fecha_inicio is null')
                ->orderByDesc('fecha_inicio')
                ->orderByDesc('id')
                ->get(),
            'tipos' => Catalogos::TIPOS_CONVENIO,
            'estados' => Catalogos::ESTADOS_CONVENIO,
            'tipo' => $tipo,
        ]);
    }
}

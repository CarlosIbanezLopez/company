<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Pagina;
use App\Models\RevistaEdicion;
use App\Support\Archivo;
use App\Support\Markdown;
use Illuminate\Http\JsonResponse;

/**
 * Revista Académica INNOVAR: ediciones, artículos y normas para autores (RF-08).
 */
class RevistaController extends Controller
{
    public function index(): JsonResponse
    {
        $presentacion = Pagina::query()->where('seccion', 'revista')->where('slug', 'revista-innovar')->first();

        return response()->json([
            'ediciones' => RevistaEdicion::query()->where('publicado', true)->withCount('articulos')->orderByDesc('numero')->get(),
            'presentacion' => $presentacion ? Markdown::html($presentacion->contenido) : null,
        ]);
    }

    public function show(int $numero): JsonResponse
    {
        $edicion = RevistaEdicion::query()->where('numero', $numero)->where('publicado', true)
            ->with(['articulos' => fn ($q) => $q->orderBy('orden')->orderBy('id')])->firstOrFail();

        return response()->json([
            'edicion' => $edicion,
            'otras' => RevistaEdicion::query()->where('publicado', true)->whereKeyNot($edicion->id)
                ->orderByDesc('numero')->limit(4)->get(['id', 'numero', 'titulo', 'portada'])
                ->map(fn (RevistaEdicion $e) => [
                    'id' => $e->id,
                    'numero' => $e->numero,
                    'titulo' => $e->titulo,
                    'portada' => $e->portada,
                    'portada_url' => Archivo::url($e->portada),
                ]),
        ]);
    }

    /** Normas para autores: se muestra con el mismo formato de página de contenido. */
    public function normas(): JsonResponse
    {
        $pagina = Pagina::query()->where('seccion', 'revista')->where('slug', 'normas-para-autores')->where('publicado', true)->firstOrFail();

        return response()->json([
            'pagina' => [...$pagina->toArray(), 'html' => Markdown::html($pagina->contenido)],
            'seccion' => 'Revista INNOVAR',
            'hermanas' => [],
            'prefijo' => 'revista-innovar',
        ]);
    }
}

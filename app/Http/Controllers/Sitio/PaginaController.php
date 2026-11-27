<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Pagina;
use App\Support\Markdown;
use Illuminate\Http\JsonResponse;

/**
 * Páginas de contenido institucional (RF-02) y académico (RF-05).
 * Rutas de la SPA: /institucional/{slug} y /academico/{slug} (prefijo = primer segmento).
 */
class PaginaController extends Controller
{
    public function institucional(string $slug): JsonResponse
    {
        return $this->mostrar('institucional', $slug, 'Institucional');
    }

    public function academico(string $slug): JsonResponse
    {
        return $this->mostrar('academico', $slug, 'Académico');
    }

    private function mostrar(string $seccion, string $slug, string $etiqueta): JsonResponse
    {
        $pagina = Pagina::query()->where('seccion', $seccion)->where('slug', $slug)->where('publicado', true)->firstOrFail();

        return response()->json([
            'pagina' => [...$pagina->toArray(), 'html' => Markdown::html($pagina->contenido)],
            'seccion' => $etiqueta,
            'hermanas' => Pagina::query()->where('seccion', $seccion)->where('publicado', true)
                ->orderBy('orden')->orderBy('id')->get(['slug', 'titulo'])
                ->map(fn (Pagina $p) => ['slug' => $p->slug, 'titulo' => $p->titulo]),
            'prefijo' => $seccion,
        ]);
    }
}

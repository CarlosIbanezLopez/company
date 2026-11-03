<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Pagina;
use App\Support\Markdown;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Páginas de contenido institucional (RF-02) y académico (RF-05).
 */
class PaginaController extends Controller
{
    public function institucional(string $slug): Response
    {
        return $this->mostrar('institucional', $slug, 'Institucional');
    }

    public function academico(string $slug): Response
    {
        return $this->mostrar('academico', $slug, 'Académico');
    }

    private function mostrar(string $seccion, string $slug, string $etiqueta): Response
    {
        $pagina = Pagina::query()->where('seccion', $seccion)->where('slug', $slug)->where('publicado', true)->firstOrFail();

        return Inertia::render('sitio/Pagina', [
            'pagina' => [...$pagina->toArray(), 'html' => Markdown::html($pagina->contenido)],
            'seccion' => $etiqueta,
            'hermanas' => Pagina::query()->where('seccion', $seccion)->where('publicado', true)->orderBy('orden')->get(['slug', 'titulo']),
            'prefijo' => $seccion,
        ]);
    }
}

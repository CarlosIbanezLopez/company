<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Pagina;
use App\Models\RevistaEdicion;
use App\Support\Markdown;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Revista Académica INNOVAR: ediciones, artículos y normas para autores (RF-08).
 */
class RevistaController extends Controller
{
    public function index(): Response
    {
        $presentacion = Pagina::query()->where('seccion', 'revista')->where('slug', 'revista-innovar')->first();

        return Inertia::render('sitio/Revista', [
            'ediciones' => RevistaEdicion::query()->where('publicado', true)->withCount('articulos')->orderByDesc('numero')->get(),
            'presentacion' => $presentacion ? Markdown::html($presentacion->contenido) : null,
        ]);
    }

    public function show(int $numero): Response
    {
        $edicion = RevistaEdicion::query()->where('numero', $numero)->where('publicado', true)
            ->with(['articulos' => fn ($q) => $q->orderBy('orden')])->firstOrFail();

        return Inertia::render('sitio/RevistaEdicion', [
            'edicion' => $edicion,
            'otras' => RevistaEdicion::query()->where('publicado', true)->whereKeyNot($edicion->id)->orderByDesc('numero')->limit(4)->get(['id', 'numero', 'titulo', 'portada']),
        ]);
    }

    public function normas(): Response
    {
        $pagina = Pagina::query()->where('seccion', 'revista')->where('slug', 'normas-para-autores')->where('publicado', true)->firstOrFail();

        return Inertia::render('sitio/Pagina', [
            'pagina' => [...$pagina->toArray(), 'html' => Markdown::html($pagina->contenido)],
            'seccion' => 'Revista INNOVAR',
            'hermanas' => [],
            'prefijo' => 'revista',
        ]);
    }
}

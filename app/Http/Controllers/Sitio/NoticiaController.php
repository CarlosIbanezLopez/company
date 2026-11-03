<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\CategoriaNoticia;
use App\Models\Noticia;
use App\Support\Markdown;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Noticias y comunicados publicados (RF-12).
 */
class NoticiaController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('sitio/Noticias', [
            'noticias' => Noticia::query()->with('categoria')->where('estado', 'publicado')
                ->when($request->query('categoria'), fn ($q, $slug) => $q->whereHas('categoria', fn ($c) => $c->where('slug', $slug)))
                ->orderByDesc('fecha_publicacion')->orderByDesc('id')
                ->paginate(9)->withQueryString(),
            'categorias' => CategoriaNoticia::query()->orderBy('nombre')->get(['nombre', 'slug']),
            'categoria' => $request->query('categoria'),
        ]);
    }

    public function show(string $slug): Response
    {
        $noticia = Noticia::query()->with(['categoria', 'autor:id,name'])->where('slug', $slug)->where('estado', 'publicado')->firstOrFail();

        return Inertia::render('sitio/Noticia', [
            'noticia' => [...$noticia->toArray(), 'html' => Markdown::html($noticia->contenido)],
            'relacionadas' => Noticia::query()->where('estado', 'publicado')->whereKeyNot($noticia->id)
                ->when($noticia->categoria_noticia_id, fn ($q, $c) => $q->where('categoria_noticia_id', $c))
                ->orderByDesc('fecha_publicacion')->limit(3)->get(),
        ]);
    }
}

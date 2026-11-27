<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sitio\Concerns\FiltrosConsulta;
use App\Models\CategoriaNoticia;
use App\Models\Noticia;
use App\Support\Markdown;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Noticias y comunicados publicados (RF-12).
 */
class NoticiaController extends Controller
{
    use FiltrosConsulta;

    public function index(Request $request): JsonResponse
    {
        $categoria = $this->texto($request, 'categoria');

        return response()->json([
            'noticias' => Noticia::query()->with('categoria')->where('estado', 'publicado')
                ->when($categoria, fn ($q, $slug) => $q->whereHas('categoria', fn ($c) => $c->where('slug', $slug)))
                ->orderByDesc('fecha_publicacion')->orderByDesc('id')
                ->paginate(9)->withQueryString(),
            'categorias' => CategoriaNoticia::query()->orderBy('nombre')->get(['nombre', 'slug']),
            'categoria' => $categoria,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $noticia = Noticia::query()->with(['categoria', 'autor:id,name'])->where('slug', $slug)->where('estado', 'publicado')->firstOrFail();

        return response()->json([
            'noticia' => [...$noticia->toArray(), 'html' => Markdown::html($noticia->contenido)],
            'relacionadas' => Noticia::query()->where('estado', 'publicado')->whereKeyNot($noticia->id)
                ->when($noticia->categoria_noticia_id, fn ($q, $c) => $q->where('categoria_noticia_id', $c))
                ->orderByDesc('fecha_publicacion')->orderByDesc('id')->limit(3)->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Video;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Galería multimedia: álbumes de fotografías y videos (RF-14).
 */
class GaleriaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('sitio/Galeria', [
            'albumes' => Album::query()->where('publicado', true)->withCount('fotos')->orderByDesc('fecha')->get(),
            'videos' => Video::query()->where('publicado', true)->latest()->limit(12)->get(),
        ]);
    }

    public function show(string $slug): Response
    {
        $album = Album::query()->where('slug', $slug)->where('publicado', true)
            ->with(['fotos' => fn ($q) => $q->orderBy('orden'), 'videos' => fn ($q) => $q->where('publicado', true)])
            ->firstOrFail();

        return Inertia::render('sitio/Album', ['album' => $album]);
    }
}

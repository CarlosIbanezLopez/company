<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\Video;
use Illuminate\Http\JsonResponse;

/**
 * Galería multimedia: álbumes de fotografías y videos (RF-14).
 */
class GaleriaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'albumes' => Album::query()->where('publicado', true)->withCount('fotos')
                ->orderByRaw('fecha is null')->orderByDesc('fecha')->orderByDesc('id')->get(),
            'videos' => Video::query()->where('publicado', true)->latest()->orderByDesc('id')->limit(12)->get(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $album = Album::query()->where('slug', $slug)->where('publicado', true)
            ->with([
                'fotos' => fn ($q) => $q->orderBy('orden')->orderBy('id'),
                'videos' => fn ($q) => $q->where('publicado', true),
            ])
            ->firstOrFail();

        return response()->json(['album' => $album]);
    }
}

<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\CategoriaDocumento;
use App\Models\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Biblioteca digital: consultar, filtrar y descargar documentos PDF (RF-06).
 */
class DocumentoController extends Controller
{
    public function index(Request $request): Response
    {
        $documentos = Documento::query()->with('categoria')->where('publicado', true)
            ->when($request->query('categoria'), fn ($q, $slug) => $q->whereHas('categoria', fn ($c) => $c->where('slug', $slug)))
            ->when($request->query('q'), fn ($q, $texto) => $q->where(fn ($w) => $w->whereLike('titulo', "%$texto%")->orWhereLike('descripcion', "%$texto%")))
            ->orderByDesc('fecha_publicacion')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('sitio/Documentos', [
            'documentos' => $documentos,
            'categorias' => CategoriaDocumento::query()->withCount(['documentos' => fn ($q) => $q->where('publicado', true)])->orderBy('nombre')->get(),
            'filtros' => $request->only(['q', 'categoria']),
        ]);
    }

    public function descargar(int $id): StreamedResponse
    {
        $documento = Documento::query()->where('publicado', true)->findOrFail($id);

        abort_unless($documento->archivo && Storage::disk('public')->exists($documento->archivo), 404);

        Documento::withoutEvents(fn () => $documento->increment('descargas'));

        return Storage::disk('public')->download($documento->archivo, Str::slug($documento->titulo).'.pdf');
    }
}

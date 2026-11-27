<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sitio\Concerns\FiltrosConsulta;
use App\Models\CategoriaDocumento;
use App\Models\Documento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Biblioteca digital: consultar, filtrar y descargar documentos PDF (RF-06).
 */
class DocumentoController extends Controller
{
    use FiltrosConsulta;

    public function index(Request $request): JsonResponse
    {
        $texto = $this->texto($request, 'q');
        $categoria = $this->texto($request, 'categoria');

        $documentos = Documento::query()->with('categoria')->where('publicado', true)
            ->when($categoria, fn ($q, $slug) => $q->whereHas('categoria', fn ($c) => $c->where('slug', $slug)))
            ->when($texto, fn ($q, $texto) => $this->contiene($q, ['titulo', 'descripcion'], $texto))
            ->orderByRaw('fecha_publicacion is null')
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        // Enlace directo (absoluto) a la descarga pública que también suma el contador.
        $documentos->through(fn (Documento $d) => [
            ...$d->toArray(),
            'descargar_url' => route('api.sitio.documentos.descargar', $d->id),
        ]);

        return response()->json([
            'documentos' => $documentos,
            'categorias' => CategoriaDocumento::query()
                ->withCount(['documentos' => fn ($q) => $q->where('publicado', true)])
                ->orderBy('nombre')->get(),
            'filtros' => (object) array_filter(['q' => $texto, 'categoria' => $categoria]),
        ]);
    }

    public function descargar(int $id): StreamedResponse|RedirectResponse
    {
        $documento = Documento::query()->where('publicado', true)->findOrFail($id);
        $archivo = (string) $documento->archivo;

        // Documento alojado en otro sitio: se cuenta la descarga y se redirige al enlace.
        if (str_starts_with($archivo, 'http://') || str_starts_with($archivo, 'https://')) {
            Documento::withoutEvents(fn () => $documento->increment('descargas'));

            return redirect()->away($archivo);
        }

        abort_unless($archivo !== '' && Storage::disk('public')->exists($archivo), 404);

        Documento::withoutEvents(fn () => $documento->increment('descargas'));

        $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) ?: 'pdf';
        $nombre = (Str::slug($documento->titulo) ?: 'documento-'.$documento->id).'.'.$extension;

        return Storage::disk('public')->download($archivo, $nombre);
    }
}

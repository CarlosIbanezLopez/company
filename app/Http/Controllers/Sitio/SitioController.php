<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use App\Models\Pagina;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Datos compartidos del portal que usan el encabezado y el pie de página de la SPA:
 * nombre de la carrera, contacto, redes sociales y menú de páginas institucionales y académicas.
 */
class SitioController extends Controller
{
    /** Redes sociales que se publican si tienen enlace configurado. */
    private const REDES = ['facebook', 'instagram', 'youtube', 'tiktok', 'linkedin'];

    public function index(): JsonResponse
    {
        $c = Configuracion::todas();

        // Se invalida al guardar o eliminar una página (AppServiceProvider).
        $menu = Cache::remember('sitio.menu', 600, fn () => Pagina::query()
            ->where('publicado', true)
            ->whereIn('seccion', ['institucional', 'academico'])
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['seccion', 'slug', 'titulo'])
            ->groupBy('seccion')
            ->map(fn ($paginas) => $paginas->map(fn (Pagina $p) => ['slug' => $p->slug, 'titulo' => $p->titulo])->values()->all())
            ->all());

        return response()->json([
            'carrera' => $c['nombre_carrera'] ?? 'Administración de Empresas',
            'facultad' => $c['nombre_facultad'] ?? null,
            'universidad' => $c['nombre_universidad'] ?? null,
            'contacto' => [
                'direccion' => $c['direccion'] ?? null,
                'telefonos' => $c['telefonos'] ?? null,
                'whatsapp' => $c['whatsapp'] ?? null,
                'correo' => $c['correo'] ?? null,
                'horario' => $c['horario'] ?? null,
            ],
            // Objeto JSON aunque no haya redes configuradas ({} en lugar de []).
            'redes' => (object) collect(self::REDES)
                ->mapWithKeys(fn (string $red) => [$red => $c[$red] ?? null])
                ->filter()
                ->all(),
            'menu' => [
                'institucional' => $menu['institucional'] ?? [],
                'academico' => $menu['academico'] ?? [],
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Menú lateral del panel cargado desde la tabla `menu_items` y filtrado por los permisos del rol.
 * Cada opción apunta a una ruta de la SPA (por ejemplo /admin/noticias).
 */
class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['menu' => self::construir($request->user())]);
    }

    /**
     * Un grupo se muestra si su propia ruta está permitida o si tiene al menos una opción permitida.
     *
     * @return array<int, array{titulo: string, icono: ?string, url: ?string, items: array<int, array{titulo: string, icono: ?string, url: string}>}>
     */
    public static function construir(User $usuario): array
    {
        if (! $usuario->activo || ! $usuario->accesoPanel()) {
            return [];
        }

        $permitido = fn (MenuItem $item) => blank($item->permiso) || $usuario->tienePermiso($item->permiso);

        // Una sola consulta: grupos (sin padre) y opciones activas, en el orden configurado.
        $opciones = MenuItem::query()->where('activo', true)->orderBy('orden')->orderBy('id')->get();
        $hijos = $opciones->whereNotNull('padre_id')->groupBy('padre_id');

        return $opciones
            ->whereNull('padre_id')
            ->filter($permitido)
            ->map(fn (MenuItem $grupo) => [
                'titulo' => $grupo->etiqueta,
                'icono' => $grupo->icono,
                'url' => self::url($grupo->ruta),
                'items' => $hijos->get($grupo->id, collect())
                    ->filter($permitido)
                    ->map(fn (MenuItem $item) => [
                        'titulo' => $item->etiqueta,
                        'icono' => $item->icono,
                        'url' => self::url($item->ruta),
                    ])
                    ->filter(fn (array $item) => $item['url'] !== null)
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $grupo) => $grupo['url'] !== null || count($grupo['items']) > 0)
            ->values()
            ->all();
    }

    /** Ruta de la SPA tal como se guardó (se asegura la barra inicial); los enlaces externos se respetan. */
    public static function url(?string $ruta): ?string
    {
        $ruta = trim((string) $ruta);

        if ($ruta === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $ruta)) {
            return $ruta;
        }

        return '/'.ltrim($ruta, '/');
    }
}

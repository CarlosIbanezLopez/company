<?php

namespace App\Http\Middleware;

use App\Models\Configuracion;
use App\Models\MenuItem;
use App\Models\Pagina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Datos compartidos con todas las páginas Vue.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $usuario = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'base' => rtrim(parse_url(url('/'), PHP_URL_PATH) ?? '', '/'),
            'auth' => [
                'user' => $usuario ? [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'email_verified_at' => $usuario->email_verified_at,
                    'two_factor_enabled' => ! is_null($usuario->two_factor_confirmed_at),
                    'created_at' => $usuario->created_at,
                    'updated_at' => $usuario->updated_at,
                    'rol' => $usuario->rol?->nombre,
                    'panel' => $usuario->accesoPanel(),
                    'permisos' => $usuario->permisos(),
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'visitas' => null,
            'sitio' => fn () => $this->datosSitio(),
            'menuAdmin' => fn () => $usuario && $request->is('admin', 'admin/*', 'settings/*') ? $this->menuAdmin($request) : [],
        ];
    }

    /** Datos del portal que usan el encabezado y el pie de página. */
    private function datosSitio(): array
    {
        $c = Configuracion::todas();

        $menu = Cache::remember('sitio.menu', 600, fn () => Pagina::query()
            ->where('publicado', true)
            ->whereIn('seccion', ['institucional', 'academico'])
            ->orderBy('orden')
            ->get(['seccion', 'slug', 'titulo'])
            ->groupBy('seccion')
            ->toArray());

        return [
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
            'redes' => collect(['facebook', 'instagram', 'youtube', 'tiktok', 'linkedin'])
                ->mapWithKeys(fn ($red) => [$red => $c[$red] ?? null])
                ->filter()
                ->all(),
            'menu' => [
                'institucional' => $menu['institucional'] ?? [],
                'academico' => $menu['academico'] ?? [],
            ],
        ];
    }

    /** Menú lateral del panel cargado desde la base de datos según los permisos del usuario. */
    private function menuAdmin(Request $request): array
    {
        $usuario = $request->user();
        $permitido = fn (MenuItem $item) => ! $item->permiso || $usuario->tienePermiso($item->permiso);
        $url = fn (?string $ruta) => $ruta && Route::has($ruta) ? route($ruta) : null;

        return MenuItem::query()
            ->whereNull('padre_id')
            ->where('activo', true)
            ->with(['hijos' => fn ($q) => $q->where('activo', true)->orderBy('orden')])
            ->orderBy('orden')
            ->get()
            ->filter($permitido)
            ->map(fn (MenuItem $grupo) => [
                'titulo' => $grupo->etiqueta,
                'icono' => $grupo->icono,
                'url' => $url($grupo->ruta),
                'items' => $grupo->hijos->filter($permitido)->map(fn (MenuItem $item) => [
                    'titulo' => $item->etiqueta,
                    'icono' => $item->icono,
                    'url' => $url($item->ruta),
                ])->filter(fn ($i) => $i['url'])->values()->all(),
            ])
            ->filter(fn ($g) => $g['url'] || count($g['items']) > 0)
            ->values()
            ->all();
    }
}

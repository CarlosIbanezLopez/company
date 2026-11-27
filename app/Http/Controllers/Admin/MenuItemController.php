<?php

namespace App\Http\Controllers\Admin;

use App\Models\MenuItem;
use App\Models\Permiso;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Menú dinámico del panel: cada opción apunta a una ruta de la SPA y se muestra
 * solo a los roles que tienen el permiso indicado.
 *
 * @extends CrudController<MenuItem>
 */
class MenuItemController extends CrudController
{
    protected string $modelo = MenuItem::class;

    protected string $titulo = 'Menú del panel';

    protected string $singular = 'opción de menú';

    protected bool $femenino = true;

    protected string $recurso = 'menu';

    protected string $uri = 'menu';

    protected ?string $descripcion = 'Opciones del menú lateral del panel, cargadas desde la base de datos según los permisos de cada rol.';

    protected array $buscarEn = ['etiqueta', 'ruta', 'permiso'];

    protected array $con = ['padre'];

    protected string $ordenarPor = 'orden';

    protected string $direccion = 'asc';

    /**
     * Cada grupo seguido de sus opciones, en el orden del menú.
     *
     * @return Builder<MenuItem>
     */
    protected function consulta(): Builder
    {
        return MenuItem::query()
            ->with('padre')
            ->orderByRaw('coalesce((select p.orden from menu_items p where p.id = menu_items.padre_id), menu_items.orden)')
            ->orderByRaw('coalesce(menu_items.padre_id, menu_items.id)')
            ->orderByRaw('case when menu_items.padre_id is null then 0 else 1 end');
    }

    protected function campos(): array
    {
        return [
            ['nombre' => 'etiqueta', 'etiqueta' => 'Etiqueta', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'ruta', 'etiqueta' => 'Ruta de la página (SPA)', 'tipo' => 'text', 'ayuda' => 'Ejemplo: /admin/noticias. Déjela vacía para un grupo que solo agrupa opciones.'],
            ['nombre' => 'icono', 'etiqueta' => 'Ícono', 'tipo' => 'select', 'opciones' => array_map(fn ($i) => ['value' => $i, 'label' => $i], Catalogos::ICONOS_MENU)],
            ['nombre' => 'permiso', 'etiqueta' => 'Permiso requerido', 'tipo' => 'select', 'ayuda' => 'Sin permiso, la opción la ven todos los usuarios del panel.', 'opciones' => $this->opcionesPermiso()],
            ['nombre' => 'padre_id', 'etiqueta' => 'Grupo (opción padre)', 'tipo' => 'select', 'opciones' => MenuItem::query()->whereNull('padre_id')->orderBy('orden')->get()->map(fn ($m) => ['value' => $m->id, 'label' => $m->etiqueta])->all()],
            ['nombre' => 'orden', 'etiqueta' => 'Orden', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'activo', 'etiqueta' => 'Activo', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'etiqueta', 'etiqueta' => 'Etiqueta'],
            ['clave' => 'padre.etiqueta', 'etiqueta' => 'Grupo'],
            ['clave' => 'ruta', 'etiqueta' => 'Ruta'],
            ['clave' => 'permiso', 'etiqueta' => 'Permiso', 'tipo' => 'badge'],
            ['clave' => 'orden', 'etiqueta' => 'Orden'],
            ['clave' => 'activo', 'etiqueta' => 'Activo', 'tipo' => 'bool'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'padre_id', 'etiqueta' => 'Grupo', 'tipo' => 'select', 'opciones' => MenuItem::query()->whereNull('padre_id')->orderBy('orden')->get()->map(fn ($m) => ['value' => $m->id, 'label' => $m->etiqueta])->all()],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        // Solo un nivel de anidamiento: el padre debe ser un grupo y no puede ser la propia opción.
        $reglasPadre = ['nullable', 'integer', Rule::exists('menu_items', 'id')->whereNull('padre_id')];

        if ($registro) {
            $reglasPadre[] = Rule::notIn([$registro->getKey()]);
        }

        return [
            'etiqueta' => ['required', 'string', 'max:80'],
            // Ruta de la SPA ("/admin/noticias", admite query string) o enlace externo http(s); nunca "javascript:".
            'ruta' => ['nullable', 'string', 'max:120', 'regex:#^(/[A-Za-z0-9/_\-:.?=&%]*|https?://\S+)$#'],
            'icono' => ['nullable', 'string', 'max:40'],
            'permiso' => ['nullable', 'string', 'max:80', Rule::exists('permisos', 'nombre')],
            'padre_id' => $reglasPadre,
            'orden' => ['nullable', 'integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }

    protected function prepararDatos(array $datos, Request $request, ?Model $registro): array
    {
        $datos['ruta'] = filled($datos['ruta'] ?? null) ? trim($datos['ruta']) : null;
        $datos['permiso'] = filled($datos['permiso'] ?? null) ? $datos['permiso'] : null;
        $datos['orden'] = (int) ($datos['orden'] ?? 0);

        return $datos;
    }

    /** @return array<int, array{value: string, label: string, grupo: string}> */
    private function opcionesPermiso(): array
    {
        return Permiso::query()->orderBy('modulo')->orderBy('nombre')->get()
            ->map(fn (Permiso $p) => [
                'value' => $p->nombre,
                'label' => $p->nombre.($p->descripcion ? ' — '.$p->descripcion : ''),
                'grupo' => $p->modulo,
            ])
            ->all();
    }
}

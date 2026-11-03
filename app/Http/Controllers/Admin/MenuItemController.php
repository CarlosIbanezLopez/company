<?php

namespace App\Http\Controllers\Admin;

use App\Models\MenuItem;
use App\Models\Permiso;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Menú dinámico del panel: cada opción se muestra según los permisos del usuario.
 */
class MenuItemController extends CrudController
{
    protected string $modelo = MenuItem::class;

    protected string $titulo = 'Menú del panel';

    protected string $singular = 'opción de menú';

    protected bool $femenino = true;

    protected string $ruta = 'admin.menu';

    protected ?string $descripcion = 'Opciones del menú lateral, cargadas desde la base de datos.';

    protected array $buscarEn = ['etiqueta', 'ruta'];

    protected array $con = ['padre'];

    protected string $ordenarPor = 'orden';

    protected string $direccion = 'asc';

    protected function consulta(): Builder
    {
        return MenuItem::query()->with('padre')->orderByRaw('coalesce(padre_id, id)');
    }

    protected function campos(): array
    {
        return [
            ['nombre' => 'etiqueta', 'etiqueta' => 'Etiqueta', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'ruta', 'etiqueta' => 'Nombre de ruta Laravel', 'tipo' => 'text', 'ayuda' => 'Ejemplo: admin.noticias.index. Vacío para un grupo.'],
            ['nombre' => 'icono', 'etiqueta' => 'Ícono', 'tipo' => 'select', 'opciones' => array_map(fn ($i) => ['value' => $i, 'label' => $i], Catalogos::ICONOS_MENU)],
            ['nombre' => 'permiso', 'etiqueta' => 'Permiso requerido', 'tipo' => 'select', 'opciones' => Permiso::query()->orderBy('nombre')->get()->map(fn ($p) => ['value' => $p->nombre, 'label' => $p->nombre])->all()],
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

    protected function reglas(?Model $registro): array
    {
        return [
            'etiqueta' => ['required', 'string', 'max:80'],
            'ruta' => ['nullable', 'string', 'max:120'],
            'icono' => ['nullable', 'string', 'max:40'],
            'permiso' => ['nullable', 'string', 'exists:permisos,nombre'],
            'padre_id' => ['nullable', 'exists:menu_items,id'],
            'orden' => ['integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }
}

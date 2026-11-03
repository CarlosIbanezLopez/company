<?php

namespace App\Http\Controllers\Admin;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * CU1 - Roles y asignación de permisos específicos por función (RF-18).
 */
class RolController extends CrudController
{
    protected string $modelo = Rol::class;

    protected string $titulo = 'Roles y permisos';

    protected string $singular = 'rol';

    protected string $ruta = 'admin.roles';

    protected ?string $descripcion = 'Defina qué módulos puede administrar cada rol.';

    protected array $buscarEn = ['nombre'];

    protected string $ordenarPor = 'nombre';

    protected string $direccion = 'asc';

    protected function consulta(): Builder
    {
        return Rol::query()->withCount(['permisos', 'usuarios']);
    }

    protected function campos(): array
    {
        $permisos = Permiso::query()->orderBy('modulo')->orderBy('nombre')->get()
            ->map(fn (Permiso $p) => ['value' => $p->id, 'label' => $p->descripcion ?? $p->nombre, 'grupo' => $p->modulo])
            ->all();

        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Nombre del rol', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'text'],
            ['nombre' => 'acceso_panel', 'etiqueta' => 'Puede ingresar al panel de administración', 'tipo' => 'checkbox', 'defecto' => true],
            ['nombre' => 'permisos', 'etiqueta' => 'Permisos', 'tipo' => 'checkboxes', 'opciones' => $permisos, 'virtual' => true, 'defecto' => [], 'ancho' => 'full'],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'nombre', 'etiqueta' => 'Rol'],
            ['clave' => 'descripcion', 'etiqueta' => 'Descripción'],
            ['clave' => 'permisos_count', 'etiqueta' => 'Permisos'],
            ['clave' => 'usuarios_count', 'etiqueta' => 'Usuarios'],
            ['clave' => 'acceso_panel', 'etiqueta' => 'Panel', 'tipo' => 'bool'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'nombre' => ['required', 'string', 'max:60', Rule::unique('roles', 'nombre')->ignore($registro?->getKey())],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'acceso_panel' => ['boolean'],
            'permisos' => ['array'],
            'permisos.*' => ['integer', 'exists:permisos,id'],
        ];
    }

    protected function valores(Model $registro): array
    {
        return [...$registro->toArray(), 'permisos' => $registro->permisos()->pluck('permisos.id')->all()];
    }

    protected function despuesDeGuardar(Model $registro, Request $request): void
    {
        $registro->permisos()->sync($request->input('permisos', []));
    }

    public function destroy(int $id): RedirectResponse
    {
        $rol = Rol::query()->findOrFail($id);

        if (in_array($rol->nombre, ['Administrador', 'Graduado'], true)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Este rol es parte del sistema y no se puede eliminar.']);

            return back();
        }

        return parent::destroy($id);
    }
}

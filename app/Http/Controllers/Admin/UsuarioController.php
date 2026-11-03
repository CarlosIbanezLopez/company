<?php

namespace App\Http\Controllers\Admin;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/**
 * CU1 - Gestión de Usuarios: crear, actualizar y deshabilitar cuentas (RF-18).
 */
class UsuarioController extends CrudController
{
    protected string $modelo = User::class;

    protected string $titulo = 'Usuarios';

    protected string $singular = 'usuario';

    protected string $ruta = 'admin.usuarios';

    protected ?string $descripcion = 'Cuentas de acceso al sistema y su rol asignado.';

    protected array $buscarEn = ['name', 'email'];

    protected array $con = ['rol'];

    protected function campos(): array
    {
        return [
            ['nombre' => 'name', 'etiqueta' => 'Nombre completo', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'email', 'etiqueta' => 'Correo electrónico', 'tipo' => 'email', 'requerido' => true],
            ['nombre' => 'telefono', 'etiqueta' => 'Teléfono', 'tipo' => 'text'],
            ['nombre' => 'rol_id', 'etiqueta' => 'Rol', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->opciones(Rol::class)],
            ['nombre' => 'password', 'etiqueta' => 'Contraseña', 'tipo' => 'password', 'ayuda' => 'Al editar, déjela vacía para mantener la actual.'],
            ['nombre' => 'activo', 'etiqueta' => 'Cuenta habilitada', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'name', 'etiqueta' => 'Nombre'],
            ['clave' => 'email', 'etiqueta' => 'Correo'],
            ['clave' => 'rol.nombre', 'etiqueta' => 'Rol', 'tipo' => 'badge'],
            ['clave' => 'activo', 'etiqueta' => 'Habilitado', 'tipo' => 'bool'],
            ['clave' => 'created_at', 'etiqueta' => 'Registro', 'tipo' => 'date'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'rol_id', 'etiqueta' => 'Rol', 'opciones' => $this->opciones(Rol::class)],
            ['clave' => 'activo', 'etiqueta' => 'Estado', 'opciones' => [['value' => '1', 'label' => 'Habilitados'], ['value' => '0', 'label' => 'Deshabilitados']]],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($registro?->getKey())],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol_id' => ['required', 'exists:roles,id'],
            'password' => [$registro ? 'nullable' : 'required', 'string', Password::min(8)],
            'activo' => ['boolean'],
        ];
    }

    protected function prepararDatos(array $datos, Request $request, ?Model $registro): array
    {
        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        if (! $registro) {
            $datos['email_verified_at'] = now();
        }

        if ($registro && $registro->is($request->user())) {
            $datos['activo'] = true;
        }

        return $datos;
    }

    protected function valores(Model $registro): array
    {
        return [...$registro->toArray(), 'password' => ''];
    }

    public function destroy(int $id): RedirectResponse
    {
        if ($id === request()->user()->id) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No puede eliminar su propia cuenta.']);

            return back();
        }

        return parent::destroy($id);
    }
}

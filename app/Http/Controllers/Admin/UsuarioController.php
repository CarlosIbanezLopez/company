<?php

namespace App\Http\Controllers\Admin;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * CU1 - Gestión de Usuarios: crear, actualizar y deshabilitar cuentas (RF-18).
 * Muestra además el último acceso y los intentos fallidos que mantiene el trigger de inicios de sesión.
 *
 * Para evitar escalar privilegios, solo un Administrador puede asignar el rol Administrador
 * o modificar y eliminar cuentas de administradores.
 *
 * @extends CrudController<User>
 */
class UsuarioController extends CrudController
{
    protected string $modelo = User::class;

    protected string $titulo = 'Usuarios';

    protected string $singular = 'usuario';

    protected string $recurso = 'usuarios';

    protected string $uri = 'usuarios';

    protected ?string $descripcion = 'Cuentas de acceso al sistema, su rol asignado y su actividad de inicio de sesión.';

    protected array $buscarEn = ['name', 'email'];

    protected array $con = ['rol'];

    protected function campos(): array
    {
        $roles = array_values(array_filter(
            $this->opciones(Rol::class),
            fn (array $opcion) => $this->puedeAsignarAdministrador() || $opcion['label'] !== 'Administrador',
        ));

        return [
            ['nombre' => 'name', 'etiqueta' => 'Nombre completo', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'email', 'etiqueta' => 'Correo electrónico', 'tipo' => 'email', 'requerido' => true],
            ['nombre' => 'telefono', 'etiqueta' => 'Teléfono', 'tipo' => 'text'],
            ['nombre' => 'rol_id', 'etiqueta' => 'Rol', 'tipo' => 'select', 'requerido' => true, 'opciones' => $roles],
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
            ['clave' => 'ultimo_acceso', 'etiqueta' => 'Último acceso', 'tipo' => 'datetime'],
            ['clave' => 'intentos_fallidos', 'etiqueta' => 'Intentos fallidos', 'tipo' => 'text'],
            ['clave' => 'created_at', 'etiqueta' => 'Registro', 'tipo' => 'date'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'rol_id', 'etiqueta' => 'Rol', 'tipo' => 'select', 'opciones' => $this->opciones(Rol::class)],
            ['clave' => 'activo', 'etiqueta' => 'Estado', 'tipo' => 'select', 'opciones' => [['value' => '1', 'label' => 'Habilitados'], ['value' => '0', 'label' => 'Deshabilitados']]],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        // Los correos se guardan en minúsculas: así la regla unique (exacta en PostgreSQL)
        // también detecta duplicados que solo cambian mayúsculas.
        if (is_string(request()->input('email'))) {
            request()->merge(['email' => Str::lower(trim(request()->input('email')))]);
        }

        $reglasRol = ['required', 'integer', 'exists:roles,id'];
        $rolAdministrador = $this->puedeAsignarAdministrador() ? null : Rol::query()->where('nombre', 'Administrador')->value('id');

        if ($rolAdministrador) {
            $reglasRol[] = Rule::notIn([$rolAdministrador]);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($registro?->getKey())],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol_id' => $reglasRol,
            'password' => [$registro ? 'nullable' : 'required', 'string', Password::defaults()],
            'activo' => ['boolean'],
        ];
    }

    protected function mensajes(): array
    {
        return ['rol_id.not_in' => 'Solo un Administrador puede asignar el rol Administrador.'];
    }

    protected function prepararDatos(array $datos, Request $request, ?Model $registro): array
    {
        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        if (! $registro) {
            $datos['email_verified_at'] = now();
        }

        // Nadie puede deshabilitar su propia cuenta.
        if ($registro && $registro->is($request->user())) {
            $datos['activo'] = true;
        }

        return $datos;
    }

    protected function despuesDeGuardar(Model $registro, Request $request): void
    {
        if ($registro->wasRecentlyCreated) {
            return;
        }

        // Al deshabilitar la cuenta o cambiarle la contraseña se cierran sus sesiones abiertas
        // (si es la propia cuenta, se conserva la sesión actual).
        if (! $registro->activo || $registro->wasChanged('password')) {
            $actual = $registro->is($request->user()) ? $request->user()?->tokenActual() : null;
            $idActual = $actual instanceof PersonalAccessToken ? $actual->getKey() : null;

            $registro->tokens()
                ->when($idActual !== null, fn ($consulta) => $consulta->whereKeyNot($idActual))
                ->delete();
        }
    }

    protected function valores(Model $registro): array
    {
        return [...$registro->toArray(), 'password' => ''];
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->bloqueoAdministrador($id) ?? parent::update($request, $id);
    }

    public function destroy(int $id): JsonResponse
    {
        if ($id === (int) request()->user()?->getAuthIdentifier()) {
            return response()->json(['message' => 'No puede eliminar su propia cuenta.'], 422);
        }

        return $this->bloqueoAdministrador($id) ?? parent::destroy($id);
    }

    private function puedeAsignarAdministrador(): bool
    {
        return (bool) request()->user()?->esAdministrador();
    }

    /** 403 si quien no es Administrador intenta modificar o eliminar la cuenta de un Administrador. */
    private function bloqueoAdministrador(int $id): ?JsonResponse
    {
        if ($this->puedeAsignarAdministrador()) {
            return null;
        }

        $objetivo = User::query()->with('rol')->find($id);

        return $objetivo?->esAdministrador()
            ? response()->json(['message' => 'Solo un Administrador puede modificar o eliminar la cuenta de un Administrador.'], 403)
            : null;
    }
}

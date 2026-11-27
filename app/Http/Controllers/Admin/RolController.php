<?php

namespace App\Http\Controllers\Admin;

use App\Models\Bitacora;
use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * CU1 - Roles y matriz de control de acceso: qué acciones (ver, crear, editar, eliminar)
 * puede realizar cada rol sobre cada recurso del panel (RF-18).
 *
 * @extends CrudController<Rol>
 */
class RolController extends CrudController
{
    /** Roles que usa el sistema por su nombre: no se eliminan ni se renombran. */
    public const ROLES_SISTEMA = ['Administrador', 'Graduado'];

    protected string $modelo = Rol::class;

    protected string $titulo = 'Roles y permisos';

    protected string $singular = 'rol';

    protected string $recurso = 'roles';

    protected string $uri = 'roles';

    protected ?string $descripcion = 'Defina qué acciones puede realizar cada rol sobre cada módulo del panel.';

    protected array $buscarEn = ['nombre', 'descripcion'];

    protected string $ordenarPor = 'nombre';

    protected string $direccion = 'asc';

    /** @return Builder<Rol> */
    protected function consulta(): Builder
    {
        return Rol::query()->withCount(['permisos', 'usuarios']);
    }

    protected function campos(): array
    {
        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Nombre del rol', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'text'],
            ['nombre' => 'acceso_panel', 'etiqueta' => 'Puede ingresar al panel de administración', 'tipo' => 'checkbox', 'defecto' => true],
            [
                'nombre' => 'permisos',
                'etiqueta' => 'Matriz de permisos',
                'tipo' => 'matriz',
                'ayuda' => 'Marque las acciones que el rol puede realizar en cada módulo. El Administrador tiene todos los permisos.',
                'acciones' => Permiso::ACCIONES,
                'filas' => $this->filas(Permiso::query()->get(), fn (Permiso $permiso) => $permiso->id),
                'virtual' => true,
                'defecto' => [],
                'ancho' => 'full',
            ],
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
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['integer', 'distinct', 'exists:permisos,id'],
        ];
    }

    protected function prepararDatos(array $datos, Request $request, ?Model $registro): array
    {
        // Los roles del sistema conservan su nombre y el Administrador siempre entra al panel.
        if ($registro && in_array($registro->getOriginal('nombre'), self::ROLES_SISTEMA, true)) {
            $datos['nombre'] = $registro->getOriginal('nombre');

            if ($datos['nombre'] === 'Administrador') {
                $datos['acceso_panel'] = true;
            }
        }

        return $datos;
    }

    protected function valores(Model $registro): array
    {
        return [...$registro->toArray(), 'permisos' => $registro->permisos()->pluck('permisos.id')->map(fn ($id) => (int) $id)->all()];
    }

    protected function despuesDeGuardar(Model $registro, Request $request): void
    {
        $ids = array_values(array_unique(array_map('intval', array_filter((array) ($request->input('permisos') ?? []), 'is_numeric'))));
        $cambios = $registro->permisos()->sync($ids);

        // La tabla pivote no dispara eventos de modelo: el cambio de permisos se deja en la bitácora aquí.
        if ($cambios['attached'] || $cambios['detached']) {
            $nombres = fn (array $ids) => Permiso::query()->whereKey($ids)->orderBy('nombre')->pluck('nombre')->implode(', ');
            $partes = array_filter([
                $cambios['attached'] ? 'asignó '.$nombres($cambios['attached']) : null,
                $cambios['detached'] ? 'quitó '.$nombres($cambios['detached']) : null,
            ]);

            Bitacora::registrar('editar', 'Usuarios', "Permisos del rol {$registro->nombre}: ".implode('; ', $partes), $registro->getKey());
        }
    }

    /**
     * Matriz de acceso de solo lectura: roles × recursos × acciones.
     */
    public function matriz(): JsonResponse
    {
        $roles = Rol::query()->orderBy('id')->get();
        $administradores = $roles->where('nombre', 'Administrador')->pluck('id')->map(fn ($id) => (int) $id)->all();

        $asignados = DB::table('rol_permiso')->get(['rol_id', 'permiso_id'])
            ->groupBy('permiso_id')
            ->map(fn ($filas) => $filas->pluck('rol_id')->map(fn ($id) => (int) $id)->all());

        return response()->json([
            'acciones' => Permiso::ACCIONES,
            'roles' => $roles->map(fn (Rol $rol) => [
                'id' => $rol->id,
                'nombre' => $rol->nombre,
                'acceso_panel' => (bool) $rol->acceso_panel,
                'es_admin' => $rol->nombre === 'Administrador',
            ])->values(),
            'filas' => $this->filas(Permiso::query()->get(), fn (Permiso $permiso) => [
                'id' => $permiso->id,
                // El Administrador tiene todos los permisos aunque no estén asignados en la tabla pivote.
                'roles' => array_values(array_unique([...$administradores, ...($asignados[$permiso->id] ?? [])])),
            ]),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->bloqueoEscalamiento($request, $id) ?? parent::update($request, $id);
    }

    public function destroy(int $id): JsonResponse
    {
        $rol = Rol::query()->withCount('usuarios')->findOrFail($id);

        if ($bloqueo = $this->bloqueoEscalamiento(request(), $id)) {
            return $bloqueo;
        }

        if (in_array($rol->nombre, self::ROLES_SISTEMA, true)) {
            return response()->json(['message' => 'Este rol es parte del sistema y no se puede eliminar.'], 422);
        }

        if ($rol->usuarios_count > 0) {
            return response()->json(['message' => "No se puede eliminar: el rol tiene {$rol->usuarios_count} usuario(s) asignado(s). Asígneles otro rol primero."], 409);
        }

        return parent::destroy($id);
    }

    /**
     * Evita escalar privilegios: quien no es Administrador no puede modificar el rol Administrador
     * ni el rol que él mismo tiene asignado.
     */
    private function bloqueoEscalamiento(Request $request, int $id): ?JsonResponse
    {
        $usuario = $request->user();

        if (! $usuario || $usuario->esAdministrador()) {
            return null;
        }

        $rol = Rol::query()->find($id);

        if ($rol?->nombre === 'Administrador') {
            return response()->json(['message' => 'Solo un Administrador puede modificar el rol Administrador.'], 403);
        }

        if ($rol && (int) $usuario->rol_id === (int) $rol->id) {
            return response()->json(['message' => 'No puede modificar los permisos de su propio rol.'], 403);
        }

        return null;
    }

    /**
     * Filas de la matriz: un recurso por fila (en el orden de Permiso::RECURSOS) y una celda por acción.
     *
     * @param  Collection<int, Permiso>  $permisos
     * @param  callable(Permiso): mixed  $celda
     * @return array<int, array{recurso: string, etiqueta: string, modulo: string, permisos: array<string, mixed>}>
     */
    private function filas(Collection $permisos, callable $celda): array
    {
        $porRecurso = [];

        foreach ($permisos as $permiso) {
            $recurso = $permiso->recurso ?: Str::before($permiso->nombre, '.');
            $accion = $permiso->accion ?: Str::after($permiso->nombre, '.');
            $porRecurso[$recurso]['modulo'] ??= $permiso->modulo ?: (Permiso::RECURSOS[$recurso]['modulo'] ?? 'Otros');
            $porRecurso[$recurso]['acciones'][$accion] = $permiso;
        }

        $orden = array_flip(array_keys(Permiso::RECURSOS));
        uksort($porRecurso, fn ($a, $b) => [$orden[$a] ?? PHP_INT_MAX, $a] <=> [$orden[$b] ?? PHP_INT_MAX, $b]);

        $filas = [];

        foreach ($porRecurso as $recurso => $datos) {
            $celdas = [];

            foreach (Permiso::ACCIONES as $accion) {
                $celdas[$accion] = isset($datos['acciones'][$accion]) ? $celda($datos['acciones'][$accion]) : null;
            }

            $filas[] = [
                'recurso' => (string) $recurso,
                'etiqueta' => Permiso::etiquetaRecurso((string) $recurso),
                'modulo' => $datos['modulo'],
                'permisos' => $celdas,
            ];
        }

        return $filas;
    }
}

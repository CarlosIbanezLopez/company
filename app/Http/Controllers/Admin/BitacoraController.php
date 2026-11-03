<?php

namespace App\Http\Controllers\Admin;

use App\Models\Bitacora;
use App\Models\User;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;

/**
 * CU2 - Bitácora de auditoría: consulta y filtros del historial de acciones (RF-19).
 */
class BitacoraController extends CrudController
{
    protected string $modelo = Bitacora::class;

    protected string $titulo = 'Bitácora de auditoría';

    protected string $singular = 'registro';

    protected string $ruta = 'admin.bitacora';

    protected ?string $descripcion = 'Historial de operaciones realizadas por usuarios con permisos administrativos.';

    protected array $buscarEn = ['descripcion', 'modulo'];

    protected array $con = ['usuario'];

    protected bool $puedeCrear = false;

    protected bool $puedeEditar = false;

    protected bool $puedeEliminar = false;

    protected function campos(): array
    {
        return [];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'created_at', 'etiqueta' => 'Fecha y hora', 'tipo' => 'datetime'],
            ['clave' => 'usuario.name', 'etiqueta' => 'Usuario'],
            ['clave' => 'accion', 'etiqueta' => 'Acción', 'tipo' => 'badge'],
            ['clave' => 'modulo', 'etiqueta' => 'Módulo'],
            ['clave' => 'descripcion', 'etiqueta' => 'Contenido afectado'],
            ['clave' => 'ip', 'etiqueta' => 'IP'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'user_id', 'etiqueta' => 'Usuario', 'opciones' => $this->opciones(User::class, 'name', 'name')],
            ['clave' => 'accion', 'etiqueta' => 'Acción', 'opciones' => $this->lista(Catalogos::ACCIONES_BITACORA)],
            ['clave' => 'modulo', 'etiqueta' => 'Módulo', 'opciones' => Bitacora::query()->distinct()->orderBy('modulo')->pluck('modulo')->map(fn ($m) => ['value' => $m, 'label' => $m])->all()],
            ['clave' => 'desde', 'etiqueta' => 'Desde', 'tipo' => 'date', 'columna' => 'created_at', 'operador' => '>='],
            ['clave' => 'hasta', 'etiqueta' => 'Hasta', 'tipo' => 'date', 'columna' => 'created_at', 'operador' => '<='],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [];
    }
}

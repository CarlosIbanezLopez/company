<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActividadCalendario;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * CU3 - Calendario académico (RF-05).
 *
 * @extends CrudController<ActividadCalendario>
 */
class ActividadCalendarioController extends CrudController
{
    protected string $modelo = ActividadCalendario::class;

    protected string $titulo = 'Calendario académico';

    protected string $singular = 'actividad';

    protected bool $femenino = true;

    protected string $recurso = 'academico';

    protected string $uri = 'calendario';

    protected array $buscarEn = ['titulo', 'descripcion'];

    protected string $ordenarPor = 'fecha_inicio';

    protected string $direccion = 'asc';

    protected function campos(): array
    {
        return [
            ['nombre' => 'titulo', 'etiqueta' => 'Actividad', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'select', 'opciones' => $this->lista(Catalogos::TIPOS_CALENDARIO), 'defecto' => 'academico'],
            ['nombre' => 'fecha_inicio', 'etiqueta' => 'Fecha de inicio', 'tipo' => 'date', 'requerido' => true],
            ['nombre' => 'fecha_fin', 'etiqueta' => 'Fecha de fin', 'tipo' => 'date'],
            ['nombre' => 'gestion', 'etiqueta' => 'Gestión (ej. 2-2026)', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'text', 'ancho' => 'full'],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'fecha_inicio', 'etiqueta' => 'Inicio', 'tipo' => 'date'],
            ['clave' => 'fecha_fin', 'etiqueta' => 'Fin', 'tipo' => 'date'],
            ['clave' => 'titulo', 'etiqueta' => 'Actividad'],
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'badge'],
            ['clave' => 'gestion', 'etiqueta' => 'Gestión'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'gestion', 'etiqueta' => 'Gestión', 'opciones' => ActividadCalendario::query()->distinct()->orderByDesc('gestion')->pluck('gestion')->map(fn ($g) => ['value' => $g, 'label' => $g])->all()],
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'opciones' => $this->lista(Catalogos::TIPOS_CALENDARIO)],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'tipo' => ['required', Rule::in(array_keys(Catalogos::TIPOS_CALENDARIO))],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'gestion' => ['required', 'string', 'max:20'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}

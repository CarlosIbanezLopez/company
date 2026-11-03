<?php

namespace App\Http\Controllers\Admin;

use App\Models\LineaInvestigacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * CU4 - Líneas de investigación de la Carrera (RF-07).
 */
class LineaInvestigacionController extends CrudController
{
    protected string $modelo = LineaInvestigacion::class;

    protected string $titulo = 'Líneas de investigación';

    protected string $singular = 'línea de investigación';

    protected bool $femenino = true;

    protected string $ruta = 'admin.lineas';

    protected array $buscarEn = ['nombre', 'descripcion'];

    protected string $ordenarPor = 'nombre';

    protected string $direccion = 'asc';

    protected function consulta(): Builder
    {
        return LineaInvestigacion::query()->withCount('proyectos');
    }

    protected function campos(): array
    {
        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'textarea', 'ancho' => 'full'],
            ['nombre' => 'activo', 'etiqueta' => 'Vigente', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'nombre', 'etiqueta' => 'Línea'],
            ['clave' => 'proyectos_count', 'etiqueta' => 'Proyectos'],
            ['clave' => 'activo', 'etiqueta' => 'Vigente', 'tipo' => 'bool'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['boolean'],
        ];
    }
}

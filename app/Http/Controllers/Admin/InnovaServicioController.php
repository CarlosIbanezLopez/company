<?php

namespace App\Http\Controllers\Admin;

use App\Models\InnovaServicio;
use Illuminate\Database\Eloquent\Model;

/**
 * CU5 - Servicios ofrecidos por la Empresa Junior INNOVA (RF-10).
 *
 * @extends CrudController<InnovaServicio>
 */
class InnovaServicioController extends CrudController
{
    protected string $modelo = InnovaServicio::class;

    protected string $titulo = 'INNOVA - Servicios';

    protected string $singular = 'servicio';

    protected string $recurso = 'innova';

    protected string $uri = 'innova-servicios';

    protected array $buscarEn = ['nombre'];

    protected string $ordenarPor = 'orden';

    protected string $direccion = 'asc';

    protected function campos(): array
    {
        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Servicio', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'orden', 'etiqueta' => 'Orden', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'textarea', 'ancho' => 'full'],
            ['nombre' => 'activo', 'etiqueta' => 'Visible', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'nombre', 'etiqueta' => 'Servicio'],
            ['clave' => 'orden', 'etiqueta' => 'Orden'],
            ['clave' => 'activo', 'etiqueta' => 'Visible', 'tipo' => 'bool'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'orden' => ['integer', 'min:0'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['boolean'],
        ];
    }
}

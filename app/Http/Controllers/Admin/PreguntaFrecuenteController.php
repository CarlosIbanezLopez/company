<?php

namespace App\Http\Controllers\Admin;

use App\Models\PreguntaFrecuente;
use Illuminate\Database\Eloquent\Model;

/**
 * CU3 - Preguntas frecuentes de los estudiantes (RF-05).
 *
 * @extends CrudController<PreguntaFrecuente>
 */
class PreguntaFrecuenteController extends CrudController
{
    protected string $modelo = PreguntaFrecuente::class;

    protected string $titulo = 'Preguntas frecuentes';

    protected string $singular = 'pregunta';

    protected bool $femenino = true;

    protected string $recurso = 'academico';

    protected string $uri = 'preguntas';

    protected array $buscarEn = ['pregunta', 'respuesta', 'categoria'];

    protected string $ordenarPor = 'orden';

    protected string $direccion = 'asc';

    protected function campos(): array
    {
        return [
            ['nombre' => 'pregunta', 'etiqueta' => 'Pregunta', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'respuesta', 'etiqueta' => 'Respuesta', 'tipo' => 'textarea', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'categoria', 'etiqueta' => 'Categoría', 'tipo' => 'text'],
            ['nombre' => 'orden', 'etiqueta' => 'Orden', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'activo', 'etiqueta' => 'Visible', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'pregunta', 'etiqueta' => 'Pregunta'],
            ['clave' => 'categoria', 'etiqueta' => 'Categoría', 'tipo' => 'badge'],
            ['clave' => 'orden', 'etiqueta' => 'Orden'],
            ['clave' => 'activo', 'etiqueta' => 'Visible', 'tipo' => 'bool'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'pregunta' => ['required', 'string', 'max:255'],
            'respuesta' => ['required', 'string'],
            'categoria' => ['nullable', 'string', 'max:60'],
            'orden' => ['integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }
}

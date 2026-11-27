<?php

namespace App\Http\Controllers\Admin;

use App\Models\InnovaIntegrante;
use Illuminate\Database\Eloquent\Model;

/**
 * CU5 - Integrantes de la Empresa Junior INNOVA (RF-10).
 *
 * @extends CrudController<InnovaIntegrante>
 */
class InnovaIntegranteController extends CrudController
{
    protected string $modelo = InnovaIntegrante::class;

    protected string $titulo = 'INNOVA - Integrantes';

    protected string $singular = 'integrante';

    protected string $recurso = 'innova';

    protected string $uri = 'innova-integrantes';

    protected array $buscarEn = ['nombre', 'cargo'];

    protected string $ordenarPor = 'orden';

    protected string $direccion = 'asc';

    protected string $carpeta = 'innova';

    protected function campos(): array
    {
        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Nombre completo', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'cargo', 'etiqueta' => 'Cargo en INNOVA', 'tipo' => 'text'],
            ['nombre' => 'semestre', 'etiqueta' => 'Semestre que cursa', 'tipo' => 'text'],
            ['nombre' => 'orden', 'etiqueta' => 'Orden', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'foto', 'etiqueta' => 'Fotografía', 'tipo' => 'image'],
            ['nombre' => 'activo', 'etiqueta' => 'Integrante activo', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'foto_url', 'etiqueta' => 'Foto', 'tipo' => 'image'],
            ['clave' => 'nombre', 'etiqueta' => 'Nombre'],
            ['clave' => 'cargo', 'etiqueta' => 'Cargo'],
            ['clave' => 'activo', 'etiqueta' => 'Activo', 'tipo' => 'bool'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'semestre' => ['nullable', 'string', 'max:30'],
            'orden' => ['integer', 'min:0'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'activo' => ['boolean'],
        ];
    }
}

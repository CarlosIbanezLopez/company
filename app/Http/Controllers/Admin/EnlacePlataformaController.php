<?php

namespace App\Http\Controllers\Admin;

use App\Models\EnlacePlataforma;
use Illuminate\Database\Eloquent\Model;

/**
 * CU2 - Enlaces directos a las plataformas de la Universidad (RF-01).
 */
class EnlacePlataformaController extends CrudController
{
    protected string $modelo = EnlacePlataforma::class;

    protected string $titulo = 'Enlaces a plataformas';

    protected string $singular = 'enlace';

    protected string $ruta = 'admin.enlaces';

    protected ?string $descripcion = 'Accesos directos a sistemas de la Universidad.';

    protected array $buscarEn = ['nombre', 'url'];

    protected string $ordenarPor = 'orden';

    protected string $direccion = 'asc';

    protected function campos(): array
    {
        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'url', 'etiqueta' => 'URL', 'tipo' => 'url', 'requerido' => true],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'text', 'ancho' => 'full'],
            ['nombre' => 'orden', 'etiqueta' => 'Orden', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'activo', 'etiqueta' => 'Visible', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'nombre', 'etiqueta' => 'Nombre'],
            ['clave' => 'url', 'etiqueta' => 'URL'],
            ['clave' => 'orden', 'etiqueta' => 'Orden'],
            ['clave' => 'activo', 'etiqueta' => 'Visible', 'tipo' => 'bool'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'url' => ['required', 'url', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'orden' => ['integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }
}

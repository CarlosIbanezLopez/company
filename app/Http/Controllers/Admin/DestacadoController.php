<?php

namespace App\Http\Controllers\Admin;

use App\Models\Destacado;
use Illuminate\Database\Eloquent\Model;

/**
 * CU2 - Información destacada del inicio (cifras y datos clave) (RF-01).
 */
class DestacadoController extends CrudController
{
    protected string $modelo = Destacado::class;

    protected string $titulo = 'Información destacada';

    protected string $singular = 'destacado';

    protected string $ruta = 'admin.destacados';

    protected ?string $descripcion = 'Cifras y datos clave que se muestran en la página de inicio.';

    protected array $buscarEn = ['titulo'];

    protected string $ordenarPor = 'orden';

    protected string $direccion = 'asc';

    protected function campos(): array
    {
        return [
            ['nombre' => 'valor', 'etiqueta' => 'Valor o cifra (ej. 2.500+)', 'tipo' => 'text'],
            ['nombre' => 'titulo', 'etiqueta' => 'Título', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'text', 'ancho' => 'full'],
            ['nombre' => 'enlace', 'etiqueta' => 'Enlace', 'tipo' => 'text'],
            ['nombre' => 'orden', 'etiqueta' => 'Orden', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'activo', 'etiqueta' => 'Visible', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'valor', 'etiqueta' => 'Valor'],
            ['clave' => 'titulo', 'etiqueta' => 'Título'],
            ['clave' => 'orden', 'etiqueta' => 'Orden'],
            ['clave' => 'activo', 'etiqueta' => 'Visible', 'tipo' => 'bool'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'valor' => ['nullable', 'string', 'max:40'],
            'titulo' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'enlace' => ['nullable', 'string', 'max:255'],
            'orden' => ['integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }
}

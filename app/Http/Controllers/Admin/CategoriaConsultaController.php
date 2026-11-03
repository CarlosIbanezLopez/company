<?php

namespace App\Http\Controllers\Admin;

use App\Models\CategoriaConsulta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * CU7 - Categorías de consulta del formulario de contacto (RF-15).
 */
class CategoriaConsultaController extends CrudController
{
    protected string $modelo = CategoriaConsulta::class;

    protected string $titulo = 'Categorías de consulta';

    protected string $singular = 'categoría';

    protected bool $femenino = true;

    protected string $ruta = 'admin.categorias-consulta';

    protected array $buscarEn = ['nombre'];

    protected string $ordenarPor = 'nombre';

    protected string $direccion = 'asc';

    protected function consulta(): Builder
    {
        return CategoriaConsulta::query()->withCount('consultas');
    }

    protected function campos(): array
    {
        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Categoría', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'correo_destino', 'etiqueta' => 'Correo que recibe la notificación', 'tipo' => 'email', 'ayuda' => 'Si se deja vacío se usa el correo general de notificaciones.'],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'nombre', 'etiqueta' => 'Categoría'],
            ['clave' => 'correo_destino', 'etiqueta' => 'Correo destino'],
            ['clave' => 'consultas_count', 'etiqueta' => 'Consultas'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80'],
            'correo_destino' => ['nullable', 'email', 'max:150'],
        ];
    }
}

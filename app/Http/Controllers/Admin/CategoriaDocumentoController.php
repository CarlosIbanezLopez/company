<?php

namespace App\Http\Controllers\Admin;

use App\Models\CategoriaDocumento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * CU4 - Categorías de la biblioteca digital (reglamentos, instructivos, formularios, convocatorias) (RF-06).
 */
class CategoriaDocumentoController extends CrudController
{
    protected string $modelo = CategoriaDocumento::class;

    protected string $titulo = 'Categorías de documentos';

    protected string $singular = 'categoría';

    protected bool $femenino = true;

    protected string $ruta = 'admin.categorias-documento';

    protected array $buscarEn = ['nombre'];

    protected string $ordenarPor = 'nombre';

    protected string $direccion = 'asc';

    protected ?string $slugDesde = 'nombre';

    protected function consulta(): Builder
    {
        return CategoriaDocumento::query()->withCount('documentos');
    }

    protected function campos(): array
    {
        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'text', 'ancho' => 'full'],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'nombre', 'etiqueta' => 'Categoría'],
            ['clave' => 'descripcion', 'etiqueta' => 'Descripción'],
            ['clave' => 'documentos_count', 'etiqueta' => 'Documentos'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}

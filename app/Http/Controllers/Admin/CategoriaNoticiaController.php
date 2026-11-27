<?php

namespace App\Http\Controllers\Admin;

use App\Models\CategoriaNoticia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * CU6 - Categorías de noticias y comunicados (RF-12).
 *
 * @extends CrudController<CategoriaNoticia>
 */
class CategoriaNoticiaController extends CrudController
{
    protected string $modelo = CategoriaNoticia::class;

    protected string $titulo = 'Categorías de noticias';

    protected string $singular = 'categoría';

    protected bool $femenino = true;

    protected string $recurso = 'noticias';

    protected string $uri = 'categorias-noticia';

    protected array $buscarEn = ['nombre'];

    protected string $ordenarPor = 'nombre';

    protected string $direccion = 'asc';

    protected ?string $slugDesde = 'nombre';

    /** @return Builder<CategoriaNoticia> */
    protected function consulta(): Builder
    {
        return CategoriaNoticia::query()->withCount('noticias');
    }

    protected function campos(): array
    {
        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'nombre', 'etiqueta' => 'Categoría'],
            ['clave' => 'noticias_count', 'etiqueta' => 'Noticias'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return ['nombre' => ['required', 'string', 'max:80']];
    }
}

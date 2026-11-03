<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pagina;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * CU3 - Contenido institucional y académico editable (RF-02, RF-05):
 * historia, misión, visión, perfiles, admisión, titulación, normas de la revista, etc.
 */
class PaginaController extends CrudController
{
    protected string $modelo = Pagina::class;

    protected string $titulo = 'Páginas de contenido';

    protected string $singular = 'página';

    protected bool $femenino = true;

    protected string $ruta = 'admin.paginas';

    protected ?string $descripcion = 'Información institucional y académica publicada en el sitio.';

    protected array $buscarEn = ['titulo', 'slug', 'contenido'];

    protected string $ordenarPor = 'orden';

    protected string $direccion = 'asc';

    protected ?string $slugDesde = 'titulo';

    protected string $carpeta = 'paginas';

    protected function campos(): array
    {
        return [
            ['nombre' => 'seccion', 'etiqueta' => 'Sección', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->lista(Catalogos::SECCIONES_PAGINA)],
            ['nombre' => 'titulo', 'etiqueta' => 'Título', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'slug', 'etiqueta' => 'Dirección (slug)', 'tipo' => 'text', 'ayuda' => 'Se genera a partir del título si se deja vacío.'],
            ['nombre' => 'orden', 'etiqueta' => 'Orden en el menú', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'resumen', 'etiqueta' => 'Resumen', 'tipo' => 'text', 'ancho' => 'full'],
            ['nombre' => 'contenido', 'etiqueta' => 'Contenido', 'tipo' => 'markdown', 'ancho' => 'full'],
            ['nombre' => 'imagen', 'etiqueta' => 'Imagen (ej. organigrama)', 'tipo' => 'image'],
            ['nombre' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'titulo', 'etiqueta' => 'Título'],
            ['clave' => 'seccion', 'etiqueta' => 'Sección', 'tipo' => 'badge'],
            ['clave' => 'slug', 'etiqueta' => 'Dirección'],
            ['clave' => 'orden', 'etiqueta' => 'Orden'],
            ['clave' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'bool'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'seccion', 'etiqueta' => 'Sección', 'opciones' => $this->lista(Catalogos::SECCIONES_PAGINA)],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'seccion' => ['required', Rule::in(array_keys(Catalogos::SECCIONES_PAGINA))],
            'titulo' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', Rule::unique('paginas', 'slug')->ignore($registro?->getKey())],
            'orden' => ['integer', 'min:0'],
            'resumen' => ['nullable', 'string', 'max:255'],
            'contenido' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'publicado' => ['boolean'],
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\RevistaEdicion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * CU4 - Ediciones de la Revista Académica INNOVAR (RF-08).
 *
 * @extends CrudController<RevistaEdicion>
 */
class RevistaEdicionController extends CrudController
{
    protected string $modelo = RevistaEdicion::class;

    protected string $titulo = 'Revista INNOVAR - Ediciones';

    protected string $singular = 'edición';

    protected bool $femenino = true;

    protected string $recurso = 'revista';

    protected string $uri = 'ediciones';

    protected array $buscarEn = ['titulo'];

    protected string $ordenarPor = 'numero';

    protected string $carpeta = 'revista';

    /** @return Builder<RevistaEdicion> */
    protected function consulta(): Builder
    {
        return RevistaEdicion::query()->withCount('articulos');
    }

    protected function accionesFila(): array
    {
        return [['etiqueta' => 'Artículos', 'url' => '/admin/articulos?revista_edicion_id=__ID__', 'icono' => 'file-text', 'tipo' => 'enlace']];
    }

    protected function campos(): array
    {
        return [
            ['nombre' => 'numero', 'etiqueta' => 'Número de edición', 'tipo' => 'number', 'requerido' => true],
            ['nombre' => 'fecha_publicacion', 'etiqueta' => 'Fecha de publicación', 'tipo' => 'date'],
            ['nombre' => 'titulo', 'etiqueta' => 'Título de la edición', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'descripcion', 'etiqueta' => 'Presentación', 'tipo' => 'textarea', 'ancho' => 'full'],
            ['nombre' => 'portada', 'etiqueta' => 'Imagen de portada', 'tipo' => 'image'],
            ['nombre' => 'archivo_pdf', 'etiqueta' => 'Revista completa (PDF)', 'tipo' => 'file', 'accept' => 'application/pdf'],
            ['nombre' => 'publicado', 'etiqueta' => 'Publicada', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'portada_url', 'etiqueta' => 'Portada', 'tipo' => 'image'],
            ['clave' => 'numero', 'etiqueta' => 'N°'],
            ['clave' => 'titulo', 'etiqueta' => 'Título'],
            ['clave' => 'fecha_publicacion', 'etiqueta' => 'Fecha', 'tipo' => 'date'],
            ['clave' => 'articulos_count', 'etiqueta' => 'Artículos'],
            ['clave' => 'publicado', 'etiqueta' => 'Publicada', 'tipo' => 'bool'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'numero' => ['required', 'integer', 'min:1', Rule::unique('revista_ediciones', 'numero')->ignore($registro?->getKey())],
            'fecha_publicacion' => ['nullable', 'date'],
            'titulo' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'portada' => ['nullable', 'image', 'max:4096'],
            'archivo_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:40960'],
            'publicado' => ['boolean'],
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\RevistaArticulo;
use App\Models\RevistaEdicion;
use Illuminate\Database\Eloquent\Model;

/**
 * CU4 - Artículos publicados en cada edición de la Revista INNOVAR (RF-08).
 *
 * @extends CrudController<RevistaArticulo>
 */
class RevistaArticuloController extends CrudController
{
    protected string $modelo = RevistaArticulo::class;

    protected string $titulo = 'Revista INNOVAR - Artículos';

    protected string $singular = 'artículo';

    protected string $recurso = 'revista';

    protected string $uri = 'articulos';

    protected array $buscarEn = ['titulo', 'autores', 'palabras_clave'];

    protected array $con = ['edicion'];

    protected string $carpeta = 'revista/articulos';

    /** @return array<int, array{value: int, label: string}> */
    private function ediciones(): array
    {
        return RevistaEdicion::query()->orderByDesc('numero')->get()
            ->map(fn ($e) => ['value' => $e->id, 'label' => 'N° '.$e->numero.' - '.$e->titulo])->all();
    }

    protected function campos(): array
    {
        return [
            ['nombre' => 'revista_edicion_id', 'etiqueta' => 'Edición', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->ediciones()],
            ['nombre' => 'orden', 'etiqueta' => 'Orden en la edición', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'titulo', 'etiqueta' => 'Título', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'autores', 'etiqueta' => 'Autores', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'resumen', 'etiqueta' => 'Resumen', 'tipo' => 'textarea', 'ancho' => 'full'],
            ['nombre' => 'palabras_clave', 'etiqueta' => 'Palabras clave (separadas por coma)', 'tipo' => 'text', 'ancho' => 'full'],
            ['nombre' => 'archivo_pdf', 'etiqueta' => 'Artículo (PDF)', 'tipo' => 'file', 'accept' => 'application/pdf'],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'edicion.numero', 'etiqueta' => 'Edición'],
            ['clave' => 'titulo', 'etiqueta' => 'Título'],
            ['clave' => 'autores', 'etiqueta' => 'Autores'],
            ['clave' => 'archivo_pdf_url', 'etiqueta' => 'PDF', 'tipo' => 'link'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'revista_edicion_id', 'etiqueta' => 'Edición', 'opciones' => $this->ediciones()],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'revista_edicion_id' => ['required', 'exists:revista_ediciones,id'],
            'orden' => ['integer', 'min:0'],
            'titulo' => ['required', 'string', 'max:255'],
            'autores' => ['required', 'string', 'max:255'],
            'resumen' => ['nullable', 'string'],
            'palabras_clave' => ['nullable', 'string', 'max:255'],
            'archivo_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }
}

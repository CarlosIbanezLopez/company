<?php

namespace App\Http\Controllers\Admin;

use App\Models\CategoriaDocumento;
use App\Models\Documento;
use Illuminate\Database\Eloquent\Model;

/**
 * CU4 - Repositorio documental: subir, organizar y publicar PDFs (RF-06).
 */
class DocumentoController extends CrudController
{
    protected string $modelo = Documento::class;

    protected string $titulo = 'Biblioteca digital';

    protected string $singular = 'documento';

    protected string $ruta = 'admin.documentos';

    protected ?string $descripcion = 'Documentos PDF agrupados por categoría.';

    protected array $buscarEn = ['titulo', 'descripcion'];

    protected array $con = ['categoria'];

    protected string $carpeta = 'documentos';

    protected function campos(): array
    {
        return [
            ['nombre' => 'titulo', 'etiqueta' => 'Título', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'categoria_documento_id', 'etiqueta' => 'Categoría', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->opciones(CategoriaDocumento::class)],
            ['nombre' => 'fecha_publicacion', 'etiqueta' => 'Fecha de publicación', 'tipo' => 'date', 'defecto' => now()->toDateString()],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'textarea', 'ancho' => 'full'],
            ['nombre' => 'archivo', 'etiqueta' => 'Archivo PDF', 'tipo' => 'file', 'accept' => 'application/pdf'],
            ['nombre' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'titulo', 'etiqueta' => 'Título'],
            ['clave' => 'categoria.nombre', 'etiqueta' => 'Categoría', 'tipo' => 'badge'],
            ['clave' => 'fecha_publicacion', 'etiqueta' => 'Fecha', 'tipo' => 'date'],
            ['clave' => 'descargas', 'etiqueta' => 'Descargas'],
            ['clave' => 'archivo_url', 'etiqueta' => 'PDF', 'tipo' => 'link'],
            ['clave' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'bool'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'categoria_documento_id', 'etiqueta' => 'Categoría', 'opciones' => $this->opciones(CategoriaDocumento::class)],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'categoria_documento_id' => ['required', 'exists:categorias_documento,id'],
            'fecha_publicacion' => ['nullable', 'date'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'archivo' => [$registro ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:20480'],
            'publicado' => ['boolean'],
        ];
    }
}

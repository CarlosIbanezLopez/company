<?php

namespace App\Http\Controllers\Admin;

use App\Models\Album;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;

/**
 * CU6 - Vínculos a videos de acontecimientos, proyectos o actividades (RF-14).
 */
class VideoController extends CrudController
{
    protected string $modelo = Video::class;

    protected string $titulo = 'Galería - Videos';

    protected string $singular = 'video';

    protected string $ruta = 'admin.videos';

    protected array $buscarEn = ['titulo'];

    protected array $con = ['album'];

    protected function campos(): array
    {
        return [
            ['nombre' => 'titulo', 'etiqueta' => 'Título', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'url', 'etiqueta' => 'Enlace del video (YouTube, Facebook, etc.)', 'tipo' => 'url', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'album_id', 'etiqueta' => 'Álbum relacionado', 'tipo' => 'select', 'opciones' => $this->opciones(Album::class, 'titulo', 'titulo')],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'textarea', 'ancho' => 'full'],
            ['nombre' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'titulo', 'etiqueta' => 'Video'],
            ['clave' => 'album.titulo', 'etiqueta' => 'Álbum'],
            ['clave' => 'url', 'etiqueta' => 'Enlace', 'tipo' => 'link'],
            ['clave' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'bool'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'url' => ['required', 'url', 'max:255'],
            'album_id' => ['nullable', 'exists:albumes,id'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'publicado' => ['boolean'],
        ];
    }
}

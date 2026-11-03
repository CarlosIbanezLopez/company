<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Model;

/**
 * CU2 - Banners y carrusel de imágenes de la página de inicio (RF-01).
 */
class BannerController extends CrudController
{
    protected string $modelo = Banner::class;

    protected string $titulo = 'Banners del carrusel';

    protected string $singular = 'banner';

    protected string $ruta = 'admin.banners';

    protected ?string $descripcion = 'Imágenes y mensajes del carrusel principal del inicio.';

    protected array $buscarEn = ['titulo', 'subtitulo'];

    protected string $ordenarPor = 'orden';

    protected string $direccion = 'asc';

    protected string $carpeta = 'banners';

    protected function campos(): array
    {
        return [
            ['nombre' => 'titulo', 'etiqueta' => 'Título', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'subtitulo', 'etiqueta' => 'Subtítulo', 'tipo' => 'text'],
            ['nombre' => 'imagen', 'etiqueta' => 'Imagen (1600x600 recomendado)', 'tipo' => 'image', 'ancho' => 'full'],
            ['nombre' => 'enlace', 'etiqueta' => 'Enlace del botón', 'tipo' => 'text', 'ayuda' => 'URL completa o ruta interna, por ejemplo /noticias'],
            ['nombre' => 'texto_boton', 'etiqueta' => 'Texto del botón', 'tipo' => 'text'],
            ['nombre' => 'orden', 'etiqueta' => 'Orden', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'activo', 'etiqueta' => 'Visible en el inicio', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'imagen_url', 'etiqueta' => 'Imagen', 'tipo' => 'image'],
            ['clave' => 'titulo', 'etiqueta' => 'Título'],
            ['clave' => 'orden', 'etiqueta' => 'Orden'],
            ['clave' => 'activo', 'etiqueta' => 'Visible', 'tipo' => 'bool'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'subtitulo' => ['nullable', 'string', 'max:255'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'enlace' => ['nullable', 'string', 'max:255'],
            'texto_boton' => ['nullable', 'string', 'max:40'],
            'orden' => ['integer', 'min:0'],
            'activo' => ['boolean'],
        ];
    }
}

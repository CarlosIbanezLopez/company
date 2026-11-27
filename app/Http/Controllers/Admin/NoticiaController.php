<?php

namespace App\Http\Controllers\Admin;

use App\Models\CategoriaNoticia;
use App\Models\Noticia;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CU6 - Noticias y comunicados: crear, editar, archivar, eliminar y destacar en el inicio (RF-12).
 *
 * @extends CrudController<Noticia>
 */
class NoticiaController extends CrudController
{
    protected string $modelo = Noticia::class;

    protected string $titulo = 'Noticias y comunicados';

    protected string $singular = 'noticia';

    protected bool $femenino = true;

    protected string $recurso = 'noticias';

    protected string $uri = 'noticias';

    protected array $buscarEn = ['titulo', 'resumen'];

    protected array $con = ['categoria', 'autor:id,name'];

    protected string $ordenarPor = 'fecha_publicacion';

    protected ?string $slugDesde = 'titulo';

    protected string $carpeta = 'noticias';

    protected function campos(): array
    {
        return [
            ['nombre' => 'titulo', 'etiqueta' => 'Título', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'categoria_noticia_id', 'etiqueta' => 'Categoría', 'tipo' => 'select', 'opciones' => $this->opciones(CategoriaNoticia::class)],
            ['nombre' => 'fecha_publicacion', 'etiqueta' => 'Fecha', 'tipo' => 'date', 'requerido' => true, 'defecto' => now()->toDateString()],
            ['nombre' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->lista(Catalogos::ESTADOS_NOTICIA), 'defecto' => 'publicado'],
            ['nombre' => 'destacado', 'etiqueta' => 'Mostrar como destacado en el inicio', 'tipo' => 'checkbox', 'defecto' => false],
            ['nombre' => 'resumen', 'etiqueta' => 'Descripción breve', 'tipo' => 'textarea', 'ancho' => 'full'],
            ['nombre' => 'contenido', 'etiqueta' => 'Contenido detallado', 'tipo' => 'markdown', 'ancho' => 'full'],
            ['nombre' => 'imagen', 'etiqueta' => 'Imagen principal', 'tipo' => 'image'],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'imagen_url', 'etiqueta' => 'Imagen', 'tipo' => 'image'],
            ['clave' => 'titulo', 'etiqueta' => 'Título'],
            ['clave' => 'categoria.nombre', 'etiqueta' => 'Categoría'],
            ['clave' => 'fecha_publicacion', 'etiqueta' => 'Fecha', 'tipo' => 'date'],
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'badge'],
            ['clave' => 'destacado', 'etiqueta' => 'Destacada', 'tipo' => 'bool'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'opciones' => $this->lista(Catalogos::ESTADOS_NOTICIA)],
            ['clave' => 'categoria_noticia_id', 'etiqueta' => 'Categoría', 'opciones' => $this->opciones(CategoriaNoticia::class)],
            ['clave' => 'destacado', 'etiqueta' => 'Destacadas', 'opciones' => [['value' => '1', 'label' => 'Sí'], ['value' => '0', 'label' => 'No']]],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'categoria_noticia_id' => ['nullable', 'exists:categorias_noticia,id'],
            'fecha_publicacion' => ['required', 'date'],
            'estado' => ['required', Rule::in(array_keys(Catalogos::ESTADOS_NOTICIA))],
            'destacado' => ['boolean'],
            'resumen' => ['nullable', 'string', 'max:300'],
            'contenido' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function prepararDatos(array $datos, Request $request, ?Model $registro): array
    {
        if (! $registro) {
            $datos['user_id'] = $request->user()->id;
        }

        return $datos;
    }
}

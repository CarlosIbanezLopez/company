<?php

namespace App\Http\Controllers\Admin;

use App\Models\InnovaProyecto;
use Illuminate\Database\Eloquent\Model;

/**
 * CU5 - Proyectos desarrollados por la Empresa Junior INNOVA (RF-10).
 */
class InnovaProyectoController extends CrudController
{
    protected string $modelo = InnovaProyecto::class;

    protected string $titulo = 'INNOVA - Proyectos';

    protected string $singular = 'proyecto';

    protected string $ruta = 'admin.innova-proyectos';

    protected array $buscarEn = ['titulo', 'cliente'];

    protected string $carpeta = 'innova';

    protected function campos(): array
    {
        return [
            ['nombre' => 'titulo', 'etiqueta' => 'Proyecto', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'cliente', 'etiqueta' => 'Empresa cliente', 'tipo' => 'text'],
            ['nombre' => 'fecha', 'etiqueta' => 'Fecha', 'tipo' => 'date'],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'textarea', 'ancho' => 'full'],
            ['nombre' => 'imagen', 'etiqueta' => 'Imagen', 'tipo' => 'image'],
            ['nombre' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'titulo', 'etiqueta' => 'Proyecto'],
            ['clave' => 'cliente', 'etiqueta' => 'Cliente'],
            ['clave' => 'fecha', 'etiqueta' => 'Fecha', 'tipo' => 'date'],
            ['clave' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'bool'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'cliente' => ['nullable', 'string', 'max:150'],
            'fecha' => ['nullable', 'date'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'publicado' => ['boolean'],
        ];
    }
}

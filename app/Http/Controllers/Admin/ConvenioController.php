<?php

namespace App\Http\Controllers\Admin;

use App\Models\Convenio;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * CU5 - Convenios, prácticas, proyectos con empresas, consultorías e interacción social (RF-09).
 *
 * @extends CrudController<Convenio>
 */
class ConvenioController extends CrudController
{
    protected string $modelo = Convenio::class;

    protected string $titulo = 'Convenios y relaciones externas';

    protected string $singular = 'convenio';

    protected string $recurso = 'vinculacion';

    protected string $uri = 'convenios';

    protected array $buscarEn = ['institucion', 'finalidad'];

    protected string $carpeta = 'convenios';

    protected function campos(): array
    {
        return [
            ['nombre' => 'institucion', 'etiqueta' => 'Institución participante', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'tipo', 'etiqueta' => 'Tipo de relación', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->lista(Catalogos::TIPOS_CONVENIO), 'defecto' => 'convenio'],
            ['nombre' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->lista(Catalogos::ESTADOS_CONVENIO), 'defecto' => 'vigente'],
            ['nombre' => 'fecha_inicio', 'etiqueta' => 'Vigente desde', 'tipo' => 'date'],
            ['nombre' => 'fecha_fin', 'etiqueta' => 'Vigente hasta', 'tipo' => 'date'],
            ['nombre' => 'finalidad', 'etiqueta' => 'Finalidad', 'tipo' => 'textarea', 'ancho' => 'full'],
            ['nombre' => 'logo', 'etiqueta' => 'Logotipo', 'tipo' => 'image'],
            ['nombre' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'logo_url', 'etiqueta' => 'Logo', 'tipo' => 'image'],
            ['clave' => 'institucion', 'etiqueta' => 'Institución'],
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'badge'],
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'badge'],
            ['clave' => 'fecha_fin', 'etiqueta' => 'Vence', 'tipo' => 'date'],
            ['clave' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'bool'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'opciones' => $this->lista(Catalogos::TIPOS_CONVENIO)],
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'opciones' => $this->lista(Catalogos::ESTADOS_CONVENIO)],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'institucion' => ['required', 'string', 'max:200'],
            'tipo' => ['required', Rule::in(array_keys(Catalogos::TIPOS_CONVENIO))],
            'estado' => ['required', Rule::in(array_keys(Catalogos::ESTADOS_CONVENIO))],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'finalidad' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'publicado' => ['boolean'],
        ];
    }
}

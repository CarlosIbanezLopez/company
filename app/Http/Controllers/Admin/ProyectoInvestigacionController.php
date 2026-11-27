<?php

namespace App\Http\Controllers\Admin;

use App\Models\LineaInvestigacion;
use App\Models\ProyectoInvestigacion;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * CU4 - Proyectos de investigación, PIA, semilleros y producción académica (RF-07).
 *
 * @extends CrudController<ProyectoInvestigacion>
 */
class ProyectoInvestigacionController extends CrudController
{
    protected string $modelo = ProyectoInvestigacion::class;

    protected string $titulo = 'Proyectos, PIA y semilleros';

    protected string $singular = 'proyecto';

    protected string $recurso = 'investigacion';

    protected string $uri = 'proyectos';

    protected array $buscarEn = ['titulo', 'responsables', 'descripcion'];

    protected array $con = ['linea'];

    protected ?string $slugDesde = 'titulo';

    protected string $carpeta = 'investigacion';

    protected function campos(): array
    {
        return [
            ['nombre' => 'titulo', 'etiqueta' => 'Título', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'tipo', 'etiqueta' => 'Tipo de trabajo', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->lista(Catalogos::TIPOS_INVESTIGACION), 'defecto' => 'proyecto'],
            ['nombre' => 'linea_investigacion_id', 'etiqueta' => 'Línea de investigación', 'tipo' => 'select', 'opciones' => $this->opciones(LineaInvestigacion::class)],
            ['nombre' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'select', 'opciones' => $this->lista(Catalogos::ESTADOS_PROYECTO), 'defecto' => 'en_curso'],
            ['nombre' => 'responsables', 'etiqueta' => 'Responsables', 'tipo' => 'text', 'ancho' => 'full'],
            ['nombre' => 'fecha_inicio', 'etiqueta' => 'Inicio', 'tipo' => 'date'],
            ['nombre' => 'fecha_fin', 'etiqueta' => 'Fin', 'tipo' => 'date'],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'markdown', 'ancho' => 'full'],
            ['nombre' => 'resultados', 'etiqueta' => 'Resultados obtenidos', 'tipo' => 'textarea', 'ancho' => 'full'],
            ['nombre' => 'enlace_publicacion', 'etiqueta' => 'Enlace a publicación', 'tipo' => 'url'],
            ['nombre' => 'enlace_repositorio', 'etiqueta' => 'Enlace a repositorio', 'tipo' => 'url'],
            ['nombre' => 'imagen', 'etiqueta' => 'Imagen', 'tipo' => 'image'],
            ['nombre' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'titulo', 'etiqueta' => 'Proyecto'],
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'badge'],
            ['clave' => 'linea.nombre', 'etiqueta' => 'Línea'],
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'badge'],
            ['clave' => 'fecha_inicio', 'etiqueta' => 'Inicio', 'tipo' => 'date'],
            ['clave' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'bool'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'opciones' => $this->lista(Catalogos::TIPOS_INVESTIGACION)],
            ['clave' => 'linea_investigacion_id', 'etiqueta' => 'Línea', 'opciones' => $this->opciones(LineaInvestigacion::class)],
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'opciones' => $this->lista(Catalogos::ESTADOS_PROYECTO)],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'tipo' => ['required', Rule::in(array_keys(Catalogos::TIPOS_INVESTIGACION))],
            'titulo' => ['required', 'string', 'max:200'],
            'linea_investigacion_id' => ['nullable', 'exists:lineas_investigacion,id'],
            'estado' => ['required', Rule::in(array_keys(Catalogos::ESTADOS_PROYECTO))],
            'responsables' => ['nullable', 'string', 'max:255'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'descripcion' => ['nullable', 'string'],
            'resultados' => ['nullable', 'string'],
            'enlace_publicacion' => ['nullable', 'url', 'max:255'],
            'enlace_repositorio' => ['nullable', 'url', 'max:255'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'publicado' => ['boolean'],
        ];
    }
}

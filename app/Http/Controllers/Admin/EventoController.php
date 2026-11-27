<?php

namespace App\Http\Controllers\Admin;

use App\Models\Evento;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CU6 - Actividades y eventos académicos (RF-13). Los próximos se muestran
 * automáticamente y los finalizados pasan al historial según la fecha.
 *
 * @extends CrudController<Evento>
 */
class EventoController extends CrudController
{
    protected string $modelo = Evento::class;

    protected string $titulo = 'Eventos académicos';

    protected string $singular = 'evento';

    protected string $recurso = 'eventos';

    protected string $uri = 'eventos';

    protected array $buscarEn = ['titulo', 'lugar'];

    protected string $ordenarPor = 'fecha';

    protected ?string $slugDesde = 'titulo';

    protected string $carpeta = 'eventos';

    protected function campos(): array
    {
        return [
            ['nombre' => 'titulo', 'etiqueta' => 'Título', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->lista(Catalogos::TIPOS_EVENTO), 'defecto' => 'conferencia'],
            ['nombre' => 'fecha', 'etiqueta' => 'Fecha', 'tipo' => 'date', 'requerido' => true],
            ['nombre' => 'hora_inicio', 'etiqueta' => 'Hora de inicio', 'tipo' => 'time'],
            ['nombre' => 'hora_fin', 'etiqueta' => 'Hora de fin', 'tipo' => 'time'],
            ['nombre' => 'lugar', 'etiqueta' => 'Lugar', 'tipo' => 'text', 'ancho' => 'full'],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'markdown', 'ancho' => 'full'],
            ['nombre' => 'enlace_inscripcion', 'etiqueta' => 'Enlace de inscripción', 'tipo' => 'url'],
            ['nombre' => 'afiche', 'etiqueta' => 'Afiche promocional', 'tipo' => 'image'],
            ['nombre' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'afiche_url', 'etiqueta' => 'Afiche', 'tipo' => 'image'],
            ['clave' => 'titulo', 'etiqueta' => 'Evento'],
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'badge'],
            ['clave' => 'fecha', 'etiqueta' => 'Fecha', 'tipo' => 'date'],
            ['clave' => 'periodo', 'etiqueta' => 'Situación', 'tipo' => 'badge'],
            ['clave' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'bool'],
        ];
    }

    protected function fila(Model $registro): array
    {
        return [...$registro->toArray(), 'periodo' => $registro->fecha->isBefore(today()) ? 'finalizado' : 'próximo'];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'opciones' => $this->lista(Catalogos::TIPOS_EVENTO)],
            ['clave' => 'periodo', 'etiqueta' => 'Situación', 'opciones' => [['value' => 'proximos', 'label' => 'Próximos'], ['value' => 'finalizados', 'label' => 'Finalizados']]],
        ];
    }

    /** @param  Builder<Evento>  $consulta */
    protected function aplicarFiltros(Builder $consulta, Request $request): void
    {
        if ($tipo = $request->query('tipo')) {
            $consulta->where('tipo', $tipo);
        }

        match ($request->query('periodo')) {
            'proximos' => $consulta->whereDate('fecha', '>=', today()),
            'finalizados' => $consulta->whereDate('fecha', '<', today()),
            default => null,
        };
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'tipo' => ['required', Rule::in(array_keys(Catalogos::TIPOS_EVENTO))],
            'fecha' => ['required', 'date'],
            'hora_inicio' => ['nullable', 'date_format:H:i,H:i:s'],
            'hora_fin' => ['nullable', 'date_format:H:i,H:i:s'],
            'lugar' => ['nullable', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'enlace_inscripcion' => ['nullable', 'url', 'max:255'],
            'afiche' => ['nullable', 'image', 'max:4096'],
            'publicado' => ['boolean'],
        ];
    }
}

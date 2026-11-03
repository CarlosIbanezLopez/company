<?php

namespace App\Http\Controllers\Admin;

use App\Models\Materia;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CU3 - Malla curricular: materias por semestre y sus prerrequisitos (RF-04).
 */
class MateriaController extends CrudController
{
    protected string $modelo = Materia::class;

    protected string $titulo = 'Malla curricular';

    protected string $singular = 'materia';

    protected bool $femenino = true;

    protected string $ruta = 'admin.materias';

    protected ?string $descripcion = 'Materias del plan de estudios y sus prerrequisitos.';

    protected array $buscarEn = ['sigla', 'nombre'];

    protected array $con = ['prerequisitos'];

    protected string $ordenarPor = 'semestre';

    protected string $direccion = 'asc';

    protected function campos(): array
    {
        $semestres = [];
        for ($i = 1; $i <= 10; $i++) {
            $semestres[] = ['value' => $i, 'label' => $i.'° semestre'];
        }

        return [
            ['nombre' => 'sigla', 'etiqueta' => 'Sigla', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'semestre', 'etiqueta' => 'Semestre', 'tipo' => 'select', 'requerido' => true, 'opciones' => $semestres],
            ['nombre' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'select', 'opciones' => $this->lista(Catalogos::TIPOS_MATERIA), 'defecto' => 'obligatoria'],
            ['nombre' => 'creditos', 'etiqueta' => 'Créditos', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'horas_teoricas', 'etiqueta' => 'Horas teóricas', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'horas_practicas', 'etiqueta' => 'Horas prácticas', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción / contenido mínimo', 'tipo' => 'textarea', 'ancho' => 'full'],
            ['nombre' => 'prerequisitos', 'etiqueta' => 'Prerrequisitos', 'tipo' => 'multiselect', 'virtual' => true, 'defecto' => [], 'ancho' => 'full',
                'opciones' => Materia::query()->orderBy('semestre')->orderBy('sigla')->get()->map(fn ($m) => ['value' => $m->id, 'label' => $m->sigla.' - '.$m->nombre, 'grupo' => $m->semestre.'° semestre'])->all()],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'semestre', 'etiqueta' => 'Sem.'],
            ['clave' => 'sigla', 'etiqueta' => 'Sigla'],
            ['clave' => 'nombre', 'etiqueta' => 'Materia'],
            ['clave' => 'creditos', 'etiqueta' => 'Créditos'],
            ['clave' => 'prerequisitos_texto', 'etiqueta' => 'Prerrequisitos'],
        ];
    }

    protected function filtros(): array
    {
        $semestres = [];
        for ($i = 1; $i <= 10; $i++) {
            $semestres[] = ['value' => (string) $i, 'label' => $i.'° semestre'];
        }

        return [
            ['clave' => 'semestre', 'etiqueta' => 'Semestre', 'opciones' => $semestres],
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'opciones' => $this->lista(Catalogos::TIPOS_MATERIA)],
        ];
    }

    protected function fila(Model $registro): array
    {
        return [...$registro->toArray(), 'prerequisitos_texto' => $registro->prerequisitos->pluck('sigla')->implode(', ') ?: '—'];
    }

    protected function valores(Model $registro): array
    {
        return [...$registro->toArray(), 'prerequisitos' => $registro->prerequisitos->pluck('id')->all()];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'sigla' => ['required', 'string', 'max:20', Rule::unique('materias', 'sigla')->ignore($registro?->getKey())],
            'nombre' => ['required', 'string', 'max:150'],
            'semestre' => ['required', 'integer', 'between:1,10'],
            'tipo' => ['required', Rule::in(array_keys(Catalogos::TIPOS_MATERIA))],
            'creditos' => ['integer', 'min:0', 'max:30'],
            'horas_teoricas' => ['integer', 'min:0', 'max:40'],
            'horas_practicas' => ['integer', 'min:0', 'max:40'],
            'descripcion' => ['nullable', 'string'],
            'prerequisitos' => ['array'],
            'prerequisitos.*' => ['integer', 'exists:materias,id', Rule::notIn([$registro?->getKey()])],
        ];
    }

    protected function despuesDeGuardar(Model $registro, Request $request): void
    {
        $registro->prerequisitos()->sync($request->input('prerequisitos', []));
    }
}

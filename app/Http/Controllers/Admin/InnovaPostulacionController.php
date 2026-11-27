<?php

namespace App\Http\Controllers\Admin;

use App\Models\InnovaPostulacion;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * CU5 - Postulaciones de estudiantes para integrar INNOVA (RF-10).
 *
 * @extends CrudController<InnovaPostulacion>
 */
class InnovaPostulacionController extends CrudController
{
    protected string $modelo = InnovaPostulacion::class;

    protected string $titulo = 'INNOVA - Postulaciones';

    protected string $singular = 'postulación';

    protected bool $femenino = true;

    protected string $recurso = 'innova';

    protected string $uri = 'innova-postulaciones';

    protected array $buscarEn = ['nombre', 'registro', 'correo'];

    protected bool $puedeCrear = false;

    protected function campos(): array
    {
        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Postulante', 'tipo' => 'readonly'],
            ['nombre' => 'registro', 'etiqueta' => 'Registro universitario', 'tipo' => 'readonly'],
            ['nombre' => 'correo', 'etiqueta' => 'Correo', 'tipo' => 'readonly'],
            ['nombre' => 'telefono', 'etiqueta' => 'Teléfono', 'tipo' => 'readonly'],
            ['nombre' => 'semestre', 'etiqueta' => 'Semestre', 'tipo' => 'readonly'],
            ['nombre' => 'fecha', 'etiqueta' => 'Fecha de postulación', 'tipo' => 'readonly'],
            ['nombre' => 'motivacion', 'etiqueta' => 'Motivación', 'tipo' => 'readonly', 'ancho' => 'full'],
            ['nombre' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->lista(Catalogos::ESTADOS_POSTULACION)],
            ['nombre' => 'observacion', 'etiqueta' => 'Observaciones internas', 'tipo' => 'textarea', 'ancho' => 'full'],
        ];
    }

    protected function valores(Model $registro): array
    {
        return [...$registro->toArray(), 'fecha' => $registro->created_at?->format('d/m/Y H:i')];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'created_at', 'etiqueta' => 'Fecha', 'tipo' => 'date'],
            ['clave' => 'nombre', 'etiqueta' => 'Postulante'],
            ['clave' => 'registro', 'etiqueta' => 'Registro'],
            ['clave' => 'semestre', 'etiqueta' => 'Semestre'],
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'badge'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'opciones' => $this->lista(Catalogos::ESTADOS_POSTULACION)],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'estado' => ['required', Rule::in(array_keys(Catalogos::ESTADOS_POSTULACION))],
            'observacion' => ['nullable', 'string'],
        ];
    }
}

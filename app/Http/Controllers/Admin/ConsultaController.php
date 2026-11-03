<?php

namespace App\Http\Controllers\Admin;

use App\Models\CategoriaConsulta;
use App\Models\Consulta;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

/**
 * CU7 - Revisión administrativa de las consultas recibidas (RF-15).
 */
class ConsultaController extends CrudController
{
    protected string $modelo = Consulta::class;

    protected string $titulo = 'Consultas recibidas';

    protected string $singular = 'consulta';

    protected bool $femenino = true;

    protected string $ruta = 'admin.consultas';

    protected ?string $descripcion = 'Mensajes enviados por visitantes desde el formulario de contacto.';

    protected array $buscarEn = ['nombre', 'correo', 'mensaje'];

    protected array $con = ['categoria'];

    protected bool $puedeCrear = false;

    protected function campos(): array
    {
        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'tipo' => 'readonly'],
            ['nombre' => 'correo', 'etiqueta' => 'Correo', 'tipo' => 'readonly'],
            ['nombre' => 'telefono', 'etiqueta' => 'Teléfono', 'tipo' => 'readonly'],
            ['nombre' => 'categoria_nombre', 'etiqueta' => 'Categoría', 'tipo' => 'readonly'],
            ['nombre' => 'fecha', 'etiqueta' => 'Recibida', 'tipo' => 'readonly'],
            ['nombre' => 'mensaje', 'etiqueta' => 'Mensaje', 'tipo' => 'readonly', 'ancho' => 'full'],
            ['nombre' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->lista(Catalogos::ESTADOS_CONSULTA)],
            ['nombre' => 'respuesta', 'etiqueta' => 'Respuesta / notas de seguimiento', 'tipo' => 'textarea', 'ancho' => 'full'],
        ];
    }

    public function edit(Request $request, int $id): Response
    {
        $consulta = Consulta::query()->findOrFail($id);

        if ($consulta->estado === 'nueva') {
            $consulta->update(['estado' => 'en_revision']);
        }

        return parent::edit($request, $id);
    }

    protected function valores(Model $registro): array
    {
        return [...$registro->toArray(), 'categoria_nombre' => $registro->categoria?->nombre, 'fecha' => $registro->created_at?->format('d/m/Y H:i')];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'created_at', 'etiqueta' => 'Fecha', 'tipo' => 'datetime'],
            ['clave' => 'nombre', 'etiqueta' => 'Nombre'],
            ['clave' => 'correo', 'etiqueta' => 'Correo'],
            ['clave' => 'categoria.nombre', 'etiqueta' => 'Categoría'],
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'badge'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'opciones' => $this->lista(Catalogos::ESTADOS_CONSULTA)],
            ['clave' => 'categoria_consulta_id', 'etiqueta' => 'Categoría', 'opciones' => $this->opciones(CategoriaConsulta::class)],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'estado' => ['required', Rule::in(array_keys(Catalogos::ESTADOS_CONSULTA))],
            'respuesta' => ['nullable', 'string'],
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\InnovaServicio;
use App\Models\InnovaSolicitud;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * CU5 - Solicitudes de servicio enviadas por empresas a INNOVA (RF-10).
 *
 * @extends CrudController<InnovaSolicitud>
 */
class InnovaSolicitudController extends CrudController
{
    protected string $modelo = InnovaSolicitud::class;

    protected string $titulo = 'INNOVA - Solicitudes de servicio';

    protected string $singular = 'solicitud';

    protected bool $femenino = true;

    protected string $recurso = 'innova';

    protected string $uri = 'innova-solicitudes';

    protected array $buscarEn = ['empresa', 'contacto', 'correo'];

    protected array $con = ['servicio'];

    protected bool $puedeCrear = false;

    protected function campos(): array
    {
        return [
            ['nombre' => 'empresa', 'etiqueta' => 'Empresa', 'tipo' => 'readonly'],
            ['nombre' => 'contacto', 'etiqueta' => 'Persona de contacto', 'tipo' => 'readonly'],
            ['nombre' => 'correo', 'etiqueta' => 'Correo', 'tipo' => 'readonly'],
            ['nombre' => 'telefono', 'etiqueta' => 'Teléfono', 'tipo' => 'readonly'],
            ['nombre' => 'servicio_nombre', 'etiqueta' => 'Servicio solicitado', 'tipo' => 'readonly'],
            ['nombre' => 'fecha', 'etiqueta' => 'Fecha de envío', 'tipo' => 'readonly'],
            ['nombre' => 'mensaje', 'etiqueta' => 'Mensaje', 'tipo' => 'readonly', 'ancho' => 'full'],
            ['nombre' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->lista(Catalogos::ESTADOS_SOLICITUD)],
            ['nombre' => 'observacion', 'etiqueta' => 'Observaciones internas', 'tipo' => 'textarea', 'ancho' => 'full'],
        ];
    }

    protected function valores(Model $registro): array
    {
        return [...$registro->toArray(), 'servicio_nombre' => $registro->servicio->nombre ?? 'Otro', 'fecha' => $registro->created_at?->format('d/m/Y H:i')];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'created_at', 'etiqueta' => 'Fecha', 'tipo' => 'date'],
            ['clave' => 'empresa', 'etiqueta' => 'Empresa'],
            ['clave' => 'contacto', 'etiqueta' => 'Contacto'],
            ['clave' => 'servicio.nombre', 'etiqueta' => 'Servicio'],
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'tipo' => 'badge'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'estado', 'etiqueta' => 'Estado', 'opciones' => $this->lista(Catalogos::ESTADOS_SOLICITUD)],
            ['clave' => 'innova_servicio_id', 'etiqueta' => 'Servicio', 'opciones' => $this->opciones(InnovaServicio::class)],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'estado' => ['required', Rule::in(array_keys(Catalogos::ESTADOS_SOLICITUD))],
            'observacion' => ['nullable', 'string'],
        ];
    }
}

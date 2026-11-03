<?php

namespace App\Http\Controllers\Admin;

use App\Models\Bitacora;
use App\Models\Graduado;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CU5 - Seguimiento a graduados: revisar, buscar, filtrar y exportar (RF-11).
 */
class GraduadoController extends CrudController
{
    protected string $modelo = Graduado::class;

    protected string $titulo = 'Seguimiento a graduados';

    protected string $singular = 'graduado';

    protected string $ruta = 'admin.graduados';

    protected ?string $descripcion = 'Registros que los titulados completan desde el sitio web.';

    protected array $buscarEn = ['nombre', 'ci', 'correo', 'empresa', 'cargo'];

    protected string $ordenarPor = 'anio_graduacion';

    protected function accionesExtra(Request $request): array
    {
        return [['etiqueta' => 'Exportar CSV', 'icono' => 'download', 'url' => route('admin.graduados.exportar', $request->query()), 'externo' => true]];
    }

    protected function campos(): array
    {
        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Nombre completo', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'ci', 'etiqueta' => 'Carnet de identidad', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'registro', 'etiqueta' => 'Registro universitario', 'tipo' => 'text'],
            ['nombre' => 'anio_graduacion', 'etiqueta' => 'Año de graduación', 'tipo' => 'number', 'requerido' => true],
            ['nombre' => 'modalidad_titulacion', 'etiqueta' => 'Modalidad de titulación', 'tipo' => 'select', 'opciones' => $this->lista(Catalogos::MODALIDADES_TITULACION)],
            ['nombre' => 'situacion_laboral', 'etiqueta' => 'Situación laboral', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->lista(Catalogos::SITUACIONES_LABORALES)],
            ['nombre' => 'empresa', 'etiqueta' => 'Empresa / institución', 'tipo' => 'text'],
            ['nombre' => 'cargo', 'etiqueta' => 'Cargo', 'tipo' => 'text'],
            ['nombre' => 'ciudad', 'etiqueta' => 'Ciudad', 'tipo' => 'text'],
            ['nombre' => 'correo', 'etiqueta' => 'Correo', 'tipo' => 'email', 'requerido' => true],
            ['nombre' => 'telefono', 'etiqueta' => 'Teléfono', 'tipo' => 'text'],
            ['nombre' => 'linkedin', 'etiqueta' => 'LinkedIn', 'tipo' => 'url'],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'nombre', 'etiqueta' => 'Nombre'],
            ['clave' => 'anio_graduacion', 'etiqueta' => 'Año'],
            ['clave' => 'situacion_laboral', 'etiqueta' => 'Situación laboral', 'tipo' => 'badge'],
            ['clave' => 'empresa', 'etiqueta' => 'Empresa'],
            ['clave' => 'correo', 'etiqueta' => 'Correo'],
            ['clave' => 'updated_at', 'etiqueta' => 'Actualizado', 'tipo' => 'date'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'anio_graduacion', 'etiqueta' => 'Año', 'opciones' => Graduado::query()->distinct()->orderByDesc('anio_graduacion')->pluck('anio_graduacion')->map(fn ($a) => ['value' => (string) $a, 'label' => (string) $a])->all()],
            ['clave' => 'situacion_laboral', 'etiqueta' => 'Situación laboral', 'opciones' => $this->lista(Catalogos::SITUACIONES_LABORALES)],
            ['clave' => 'modalidad_titulacion', 'etiqueta' => 'Modalidad', 'opciones' => $this->lista(Catalogos::MODALIDADES_TITULACION)],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'ci' => ['required', 'string', 'max:20'],
            'registro' => ['nullable', 'string', 'max:20'],
            'anio_graduacion' => ['required', 'integer', 'between:1970,'.(date('Y') + 1)],
            'modalidad_titulacion' => ['nullable', Rule::in(array_keys(Catalogos::MODALIDADES_TITULACION))],
            'situacion_laboral' => ['required', Rule::in(array_keys(Catalogos::SITUACIONES_LABORALES))],
            'empresa' => ['nullable', 'string', 'max:150'],
            'cargo' => ['nullable', 'string', 'max:120'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'linkedin' => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * Exporta los graduados filtrados a CSV (compatible con Excel).
     */
    public function exportar(Request $request): StreamedResponse
    {
        $consulta = Graduado::query();
        $texto = trim((string) $request->query('q', ''));

        if ($texto !== '') {
            $this->aplicarBusqueda($consulta, $texto);
        }

        $this->aplicarFiltros($consulta, $request);

        Bitacora::registrar('exportar', 'Graduados', 'Exportó el listado de graduados a CSV');

        return response()->streamDownload(function () use ($consulta) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Nombre', 'CI', 'Registro', 'Año de graduación', 'Modalidad', 'Situación laboral', 'Empresa', 'Cargo', 'Ciudad', 'Correo', 'Teléfono', 'LinkedIn', 'Última actualización'], ';');

            $consulta->orderBy('anio_graduacion')->orderBy('nombre')->chunk(200, function ($graduados) use ($salida) {
                foreach ($graduados as $g) {
                    fputcsv($salida, [
                        $g->nombre, $g->ci, $g->registro, $g->anio_graduacion,
                        Catalogos::MODALIDADES_TITULACION[$g->modalidad_titulacion] ?? '',
                        Catalogos::SITUACIONES_LABORALES[$g->situacion_laboral] ?? $g->situacion_laboral,
                        $g->empresa, $g->cargo, $g->ciudad, $g->correo, $g->telefono, $g->linkedin,
                        $g->updated_at?->format('d/m/Y'),
                    ], ';');
                }
            });

            fclose($salida);
        }, 'graduados_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

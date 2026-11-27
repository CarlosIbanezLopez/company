<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ConsultasAnaliticas;
use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\CategoriaConsulta;
use App\Models\Consulta;
use App\Models\ContadorVisita;
use App\Models\Convenio;
use App\Models\Docente;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Foto;
use App\Models\Graduado;
use App\Models\InnovaPostulacion;
use App\Models\InnovaSolicitud;
use App\Models\Materia;
use App\Models\Noticia;
use App\Models\ProyectoInvestigacion;
use App\Models\RevistaArticulo;
use App\Models\RevistaEdicion;
use App\Models\VisitaDiaria;
use App\Support\Catalogos;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CU8 - Reportes y estadísticas del portal (mismos datos que la página de reportes original).
 */
class ReporteController extends Controller
{
    use ConsultasAnaliticas;

    public function index(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->rango($request);
        $inicio = $desde->startOfDay();
        $fin = $hasta->endOfDay();

        $serie = $this->serieDiaria($desde, $hasta, VisitaDiaria::query()
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('fecha, sum(total) as total')
            ->groupBy('fecha')
            ->pluck('total', 'fecha'));

        $consultas = fn () => Consulta::query()->whereBetween('created_at', [$inicio, $fin]);

        return response()->json([
            'filtros' => ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()],
            'resumen' => [
                'visitas_periodo' => array_sum(array_column($serie, 'total')),
                'visitas_historicas' => (int) ContadorVisita::query()->sum('total'),
                'paginas_registradas' => ContadorVisita::query()->count(),
                'consultas_periodo' => $consultas()->count(),
            ],
            'visitasPorDia' => $serie,
            'paginasPeriodo' => $this->paginasPeriodo($desde, $hasta)
                ->take(15)
                ->map(fn ($fila) => ['ruta' => $fila->ruta, 'total' => (int) $fila->total])
                ->values(),
            'paginasHistorico' => ContadorVisita::query()->orderByDesc('total')->orderBy('ruta')->limit(15)->get(['ruta', 'total', 'ultima_visita']),
            'consultasPorEstado' => $this->conEtiquetas(
                $consultas()->selectRaw('estado as clave, count(*) as total')->groupBy('estado')->pluck('total', 'clave'),
                Catalogos::ESTADOS_CONSULTA,
            ),
            'consultasPorCategoria' => CategoriaConsulta::query()
                ->withCount(['consultas' => fn ($q) => $q->whereBetween('created_at', [$inicio, $fin])])
                ->orderBy('nombre')
                ->get()
                ->map(fn (CategoriaConsulta $c) => ['etiqueta' => $c->nombre, 'total' => (int) $c->consultas_count])
                ->values(),
            'graduadosPorSituacion' => $this->conEtiquetas(
                Graduado::query()->selectRaw('situacion_laboral as clave, count(*) as total')->groupBy('situacion_laboral')->pluck('total', 'clave'),
                Catalogos::SITUACIONES_LABORALES,
            ),
            'graduadosPorAnio' => Graduado::query()
                ->selectRaw('anio_graduacion as etiqueta, count(*) as total')
                ->groupBy('anio_graduacion')
                ->orderBy('anio_graduacion')
                ->toBase()
                ->get()
                ->map(fn ($fila) => ['etiqueta' => $fila->etiqueta, 'total' => (int) $fila->total])
                ->values(),
            'documentosMasDescargados' => Documento::query()
                ->with('categoria:id,nombre')
                ->orderByDesc('descargas')
                ->orderBy('titulo')
                ->limit(10)
                ->get(['id', 'titulo', 'descargas', 'categoria_documento_id']),
            'contenido' => [
                ['etiqueta' => 'Noticias', 'total' => Noticia::query()->count()],
                ['etiqueta' => 'Eventos', 'total' => Evento::query()->count()],
                ['etiqueta' => 'Documentos', 'total' => Documento::query()->count()],
                ['etiqueta' => 'Proyectos de investigación', 'total' => ProyectoInvestigacion::query()->count()],
                ['etiqueta' => 'Ediciones INNOVAR', 'total' => RevistaEdicion::query()->count()],
                ['etiqueta' => 'Artículos INNOVAR', 'total' => RevistaArticulo::query()->count()],
                ['etiqueta' => 'Convenios', 'total' => Convenio::query()->count()],
                ['etiqueta' => 'Docentes y autoridades', 'total' => Docente::query()->count()],
                ['etiqueta' => 'Materias', 'total' => Materia::query()->count()],
                ['etiqueta' => 'Fotografías', 'total' => Foto::query()->count()],
            ],
            'innova' => [
                'solicitudes' => $this->conEtiquetas(
                    InnovaSolicitud::query()->selectRaw('estado as clave, count(*) as total')->groupBy('estado')->pluck('total', 'clave'),
                    Catalogos::ESTADOS_SOLICITUD,
                ),
                'postulaciones' => $this->conEtiquetas(
                    InnovaPostulacion::query()->selectRaw('estado as clave, count(*) as total')->groupBy('estado')->pluck('total', 'clave'),
                    Catalogos::ESTADOS_POSTULACION,
                ),
            ],
            'actividadUsuarios' => Bitacora::query()
                ->whereBetween('bitacora.created_at', [$inicio, $fin])
                ->join('users', 'users.id', '=', 'bitacora.user_id')
                ->select('users.name as etiqueta', DB::raw('count(*) as total'))
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total')
                ->orderBy('users.name')
                ->limit(10)
                ->toBase()
                ->get()
                ->map(fn ($fila) => ['etiqueta' => $fila->etiqueta, 'total' => (int) $fila->total])
                ->values(),
        ]);
    }

    /**
     * Exporta a CSV (separado por punto y coma, UTF-8 con BOM para Excel) las visitas por página del período.
     */
    public function exportarVisitas(Request $request): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        Bitacora::registrar('exportar', 'Reportes', "Exportó visitas por página del {$desde->toDateString()} al {$hasta->toDateString()}");

        $filas = $this->paginasPeriodo($desde, $hasta);

        return response()->streamDownload(function () use ($filas, $desde, $hasta) {
            $salida = fopen('php://output', 'w');

            if ($salida === false) {
                return;
            }

            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Reporte de visitas por página', $desde->format('d/m/Y').' - '.$hasta->format('d/m/Y')], ';', '"', '');
            fputcsv($salida, ['Página', 'Visitas'], ';', '"', '');

            foreach ($filas as $fila) {
                fputcsv($salida, [$fila->ruta, (int) $fila->total], ';', '"', '');
            }

            fclose($salida);
        }, 'visitas_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Visitas por página del período (de visitas_diarias, que mantiene el trigger).
     *
     * @return Collection<int, stdClass>
     */
    private function paginasPeriodo(CarbonImmutable $desde, CarbonImmutable $hasta): Collection
    {
        return VisitaDiaria::query()
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('ruta, sum(total) as total')
            ->groupBy('ruta')
            ->orderByDesc('total')
            ->orderBy('ruta')
            ->toBase()
            ->get();
    }

    /**
     * Período del reporte (por defecto, los últimos 30 días; máximo 367 días).
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function rango(Request $request): array
    {
        $datos = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $hasta = isset($datos['hasta']) ? CarbonImmutable::parse($datos['hasta'])->startOfDay() : CarbonImmutable::today();
        $desde = isset($datos['desde']) ? CarbonImmutable::parse($datos['desde'])->startOfDay() : $hasta->subDays(29);

        if ($desde->greaterThan($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        if ($desde->diffInDays($hasta) > 366) {
            $desde = $hasta->subDays(366);
        }

        return [$desde, $hasta];
    }
}

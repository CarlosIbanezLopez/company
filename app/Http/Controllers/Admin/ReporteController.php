<?php

namespace App\Http\Controllers\Admin;

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
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CU8 - Reportes y estadísticas del portal.
 */
class ReporteController extends Controller
{
    public function index(Request $request): Response
    {
        [$desde, $hasta] = $this->rango($request);

        $visitasPorDia = VisitaDiaria::query()
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('fecha, sum(total) as total')
            ->groupBy('fecha')
            ->pluck('total', 'fecha')
            ->mapWithKeys(fn ($total, $fecha) => [Carbon::parse($fecha)->toDateString() => (int) $total]);

        $serie = collect(CarbonPeriod::create($desde, $hasta))
            ->map(fn ($dia) => ['fecha' => $dia->toDateString(), 'total' => $visitasPorDia[$dia->toDateString()] ?? 0])
            ->values();

        $paginasPeriodo = VisitaDiaria::query()
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('ruta, sum(total) as total')
            ->groupBy('ruta')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn ($v) => ['ruta' => $v->ruta, 'total' => (int) $v->total]);

        $consultas = Consulta::query()->whereBetween('created_at', [$desde, $hasta->copy()->endOfDay()]);

        return Inertia::render('admin/Reportes', [
            'filtros' => ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()],
            'resumen' => [
                'visitas_periodo' => (int) $serie->sum('total'),
                'visitas_historicas' => (int) ContadorVisita::query()->sum('total'),
                'paginas_registradas' => ContadorVisita::query()->count(),
                'consultas_periodo' => (clone $consultas)->count(),
            ],
            'visitasPorDia' => $serie,
            'paginasPeriodo' => $paginasPeriodo,
            'paginasHistorico' => ContadorVisita::query()->orderByDesc('total')->limit(15)->get(['ruta', 'total', 'ultima_visita']),
            'consultasPorEstado' => $this->conEtiquetas((clone $consultas)->selectRaw('estado as clave, count(*) as total')->groupBy('estado')->pluck('total', 'clave'), Catalogos::ESTADOS_CONSULTA),
            'consultasPorCategoria' => CategoriaConsulta::query()
                ->withCount(['consultas' => fn ($q) => $q->whereBetween('created_at', [$desde, $hasta->copy()->endOfDay()])])
                ->get()->map(fn ($c) => ['etiqueta' => $c->nombre, 'total' => $c->consultas_count]),
            'graduadosPorSituacion' => $this->conEtiquetas(Graduado::query()->selectRaw('situacion_laboral as clave, count(*) as total')->groupBy('situacion_laboral')->pluck('total', 'clave'), Catalogos::SITUACIONES_LABORALES),
            'graduadosPorAnio' => Graduado::query()->selectRaw('anio_graduacion as etiqueta, count(*) as total')->groupBy('anio_graduacion')->orderBy('anio_graduacion')->get(),
            'documentosMasDescargados' => Documento::query()->with('categoria:id,nombre')->orderByDesc('descargas')->limit(10)->get(['id', 'titulo', 'descargas', 'categoria_documento_id']),
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
                'solicitudes' => $this->conEtiquetas(InnovaSolicitud::query()->selectRaw('estado as clave, count(*) as total')->groupBy('estado')->pluck('total', 'clave'), Catalogos::ESTADOS_SOLICITUD),
                'postulaciones' => $this->conEtiquetas(InnovaPostulacion::query()->selectRaw('estado as clave, count(*) as total')->groupBy('estado')->pluck('total', 'clave'), Catalogos::ESTADOS_POSTULACION),
            ],
            'actividadUsuarios' => Bitacora::query()
                ->whereBetween('bitacora.created_at', [$desde, $hasta->copy()->endOfDay()])
                ->join('users', 'users.id', '=', 'bitacora.user_id')
                ->select('users.name as etiqueta', DB::raw('count(*) as total'))
                ->groupBy('users.name')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
        ]);
    }

    /**
     * Exporta a CSV las visitas por página del período seleccionado.
     */
    public function exportarVisitas(Request $request): StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);

        Bitacora::registrar('exportar', 'Reportes', "Exportó visitas por página del {$desde->toDateString()} al {$hasta->toDateString()}");

        $filas = VisitaDiaria::query()
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('ruta, sum(total) as total')
            ->groupBy('ruta')
            ->orderByDesc('total')
            ->get();

        return response()->streamDownload(function () use ($filas, $desde, $hasta) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Reporte de visitas por página', $desde->format('d/m/Y').' - '.$hasta->format('d/m/Y')], ';');
            fputcsv($salida, ['Página', 'Visitas'], ';');

            foreach ($filas as $fila) {
                fputcsv($salida, [$fila->ruta, $fila->total], ';');
            }

            fclose($salida);
        }, 'visitas_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function rango(Request $request): array
    {
        $datos = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        $hasta = isset($datos['hasta']) ? Carbon::parse($datos['hasta'])->startOfDay() : today();
        $desde = isset($datos['desde']) ? Carbon::parse($datos['desde'])->startOfDay() : $hasta->copy()->subDays(29);

        if ($desde->greaterThan($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        if ($desde->diffInDays($hasta) > 366) {
            $desde = $hasta->copy()->subDays(366);
        }

        return [$desde, $hasta];
    }

    private function conEtiquetas($totales, array $etiquetas): array
    {
        return collect($totales)->map(fn ($total, $clave) => ['etiqueta' => $etiquetas[$clave] ?? $clave, 'total' => (int) $total])->values()->all();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ConsultasAnaliticas;
use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Consulta;
use App\Models\ContadorRecurso;
use App\Models\ContadorVisita;
use App\Models\Evento;
use App\Models\Graduado;
use App\Models\Noticia;
use App\Models\User;
use App\Models\VisitaDiaria;
use App\Support\Catalogos;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tablero del panel de administración: resumen del portal, métricas del negocio
 * y, para quien puede ver la bitácora, el flujo de accesos (sesiones, recursos e IPs).
 */
class DashboardController extends Controller
{
    use ConsultasAnaliticas;

    /** Días que abarcan las series del tablero. */
    public const DIAS = 14;

    public function index(Request $request): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();
        $verBitacora = $usuario->tienePermiso('bitacora.ver');

        $hasta = today()->toImmutable();
        $desde = $hasta->subDays(self::DIAS - 1);

        return response()->json([
            'tarjetas' => [
                ['titulo' => 'Visitas hoy', 'valor' => (int) VisitaDiaria::query()->where('fecha', $hasta->toDateString())->sum('total'), 'icono' => 'eye'],
                ['titulo' => 'Visitas totales', 'valor' => (int) ContadorVisita::query()->sum('total'), 'icono' => 'bar-chart-3'],
                ['titulo' => 'Consultas nuevas', 'valor' => Consulta::query()->where('estado', 'nueva')->count(), 'icono' => 'inbox'],
                ['titulo' => 'Graduados registrados', 'valor' => Graduado::query()->count(), 'icono' => 'graduation-cap'],
                ['titulo' => 'Noticias publicadas', 'valor' => Noticia::query()->where('estado', 'publicado')->count(), 'icono' => 'newspaper'],
                ['titulo' => 'Próximos eventos', 'valor' => Evento::query()->whereDate('fecha', '>=', $hasta->toDateString())->count(), 'icono' => 'calendar-days'],
            ],
            'consultas' => $usuario->tienePermiso('consultas.ver')
                ? Consulta::query()->with('categoria')->latest()->limit(5)->get()
                : [],
            'bitacora' => $verBitacora
                ? Bitacora::query()->with('usuario:id,name')->latest('created_at')->latest('id')->limit(8)->get()
                : [],
            'paginasMasVistas' => ContadorVisita::query()->orderByDesc('total')->orderBy('ruta')->limit(5)->get(['ruta', 'total']),
            'visitasPorDia' => $this->visitasPorDia($desde, $hasta),
            'sesiones' => $verBitacora ? $this->sesiones($desde, $hasta) : null,
            'recursosMasAccedidos' => $verBitacora
                ? ContadorRecurso::query()->orderByDesc('total')->orderBy('recurso')->limit(8)->get(['recurso', 'metodo', 'ruta', 'total'])
                : null,
            'ipsFrecuentes' => $verBitacora
                ? DB::table('accesos')->whereNotNull('ip')
                    ->select('ip')->selectRaw('count(*) as total')
                    ->groupBy('ip')->orderByDesc('total')->orderBy('ip')->limit(8)->get()
                    ->map(fn ($fila) => ['ip' => $fila->ip, 'total' => (int) $fila->total])
                : null,
            'negocio' => [
                'noticiasPorCategoria' => DB::table('noticias')
                    ->leftJoin('categorias_noticia', 'categorias_noticia.id', '=', 'noticias.categoria_noticia_id')
                    ->select('categorias_noticia.nombre')->selectRaw('count(*) as total')
                    ->groupBy('categorias_noticia.id', 'categorias_noticia.nombre')
                    ->orderByDesc('total')->orderBy('categorias_noticia.nombre')->get()
                    ->map(fn ($fila) => ['etiqueta' => $fila->nombre ?? 'Sin categoría', 'total' => (int) $fila->total])
                    ->all(),
                'consultasPorEstado' => $this->conEtiquetas(
                    Consulta::query()->selectRaw('estado as clave, count(*) as total')->groupBy('estado')->pluck('total', 'clave'),
                    Catalogos::ESTADOS_CONSULTA,
                    completar: true,
                ),
                'graduadosPorSituacion' => $this->conEtiquetas(
                    Graduado::query()->selectRaw('situacion_laboral as clave, count(*) as total')->groupBy('situacion_laboral')->pluck('total', 'clave'),
                    Catalogos::SITUACIONES_LABORALES,
                    completar: true,
                ),
            ],
        ]);
    }

    /** @return array<int, array{fecha: string, total: int}> */
    private function visitasPorDia(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $totales = VisitaDiaria::query()
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('fecha, sum(total) as total')
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        return $this->serieDiaria($desde, $hasta, $totales);
    }

    /**
     * Inicios de sesión exitosos y fallidos del período, en total y por día.
     *
     * @return array{exitosas: int, fallidas: int, porDia: array<int, array{fecha: string, exitosas: int, fallidas: int}>}
     */
    private function sesiones(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $fecha = $this->sqlFecha('created_at');

        $filas = DB::table('inicios_sesion')
            ->where('created_at', '>=', $desde->startOfDay())
            ->where('created_at', '<', $hasta->addDay()->startOfDay())
            ->selectRaw("{$fecha} as fecha")
            ->selectRaw('sum(case when exitoso then 1 else 0 end) as exitosas')
            ->selectRaw('sum(case when exitoso then 0 else 1 end) as fallidas')
            ->groupByRaw($fecha)
            ->get()
            ->keyBy(fn ($fila) => $this->claveFecha($fila->fecha));

        $porDia = array_map(fn (array $dia) => [
            'fecha' => $dia['fecha'],
            'exitosas' => (int) ($filas[$dia['fecha']]->exitosas ?? 0),
            'fallidas' => (int) ($filas[$dia['fecha']]->fallidas ?? 0),
        ], $this->serieDiaria($desde, $hasta, []));

        return [
            'exitosas' => array_sum(array_column($porDia, 'exitosas')),
            'fallidas' => array_sum(array_column($porDia, 'fallidas')),
            'porDia' => $porDia,
        ];
    }
}

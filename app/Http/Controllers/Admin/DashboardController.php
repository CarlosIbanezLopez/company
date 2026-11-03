<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Consulta;
use App\Models\ContadorVisita;
use App\Models\Evento;
use App\Models\Graduado;
use App\Models\Noticia;
use App\Models\VisitaDiaria;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Página inicial del panel de administración con un resumen general.
 */
class DashboardController extends Controller
{
    public function index(): Response
    {
        $usuario = request()->user();

        return Inertia::render('admin/Dashboard', [
            'tarjetas' => [
                ['titulo' => 'Visitas hoy', 'valor' => (int) VisitaDiaria::query()->whereDate('fecha', today())->sum('total'), 'icono' => 'eye'],
                ['titulo' => 'Visitas totales', 'valor' => (int) ContadorVisita::query()->sum('total'), 'icono' => 'bar-chart-3'],
                ['titulo' => 'Consultas nuevas', 'valor' => Consulta::query()->where('estado', 'nueva')->count(), 'icono' => 'inbox'],
                ['titulo' => 'Graduados registrados', 'valor' => Graduado::query()->count(), 'icono' => 'graduation-cap'],
                ['titulo' => 'Noticias publicadas', 'valor' => Noticia::query()->where('estado', 'publicado')->count(), 'icono' => 'newspaper'],
                ['titulo' => 'Próximos eventos', 'valor' => Evento::query()->whereDate('fecha', '>=', today())->count(), 'icono' => 'calendar-days'],
            ],
            'consultas' => $usuario->tienePermiso('consultas.gestionar')
                ? Consulta::query()->with('categoria')->latest()->limit(5)->get()
                : [],
            'bitacora' => $usuario->tienePermiso('bitacora.ver')
                ? Bitacora::query()->with('usuario:id,name')->latest('created_at')->limit(8)->get()
                : [],
            'paginasMasVistas' => ContadorVisita::query()->orderByDesc('total')->limit(5)->get(['ruta', 'total']),
        ]);
    }
}

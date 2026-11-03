<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Configuracion;
use App\Models\Destacado;
use App\Models\EnlacePlataforma;
use App\Models\Evento;
use App\Models\Noticia;
use App\Support\Markdown;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Página de inicio: carrusel, mensaje de presentación, destacados, noticias,
 * próximos eventos y enlaces a plataformas (RF-01).
 */
class InicioController extends Controller
{
    public function index(): Response
    {
        $noticias = Noticia::query()->with('categoria')->where('estado', 'publicado')
            ->orderByDesc('destacado')->orderByDesc('fecha_publicacion')->limit(5)->get();

        return Inertia::render('sitio/Inicio', [
            'banners' => Banner::query()->where('activo', true)->orderBy('orden')->get(),
            'presentacion' => [
                'titulo' => Configuracion::valor('presentacion_titulo'),
                'html' => Markdown::html(Configuracion::valor('presentacion_texto')),
                'autor' => Configuracion::valor('presentacion_autor'),
                'video' => Configuracion::valor('video_presentacion'),
            ],
            'destacados' => Destacado::query()->where('activo', true)->orderBy('orden')->get(),
            'noticias' => $noticias,
            'eventos' => Evento::query()->where('publicado', true)->whereDate('fecha', '>=', today())->orderBy('fecha')->limit(3)->get(),
            'enlaces' => EnlacePlataforma::query()->where('activo', true)->orderBy('orden')->get(),
        ]);
    }
}

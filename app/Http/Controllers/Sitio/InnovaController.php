<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\InnovaIntegrante;
use App\Models\InnovaPostulacion;
use App\Models\InnovaProyecto;
use App\Models\InnovaServicio;
use App\Models\InnovaSolicitud;
use App\Models\Pagina;
use App\Support\Antispam;
use App\Support\Markdown;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Empresa Junior INNOVA: información, servicios, integrantes, proyectos y
 * formularios de solicitud de servicio y postulación (RF-10).
 */
class InnovaController extends Controller
{
    public function index(): Response
    {
        $descripcion = Pagina::query()->where('seccion', 'innova')->where('publicado', true)->orderBy('orden')->first();

        return Inertia::render('sitio/Innova', [
            'descripcion' => $descripcion ? ['titulo' => $descripcion->titulo, 'html' => Markdown::html($descripcion->contenido)] : null,
            'servicios' => InnovaServicio::query()->where('activo', true)->orderBy('orden')->get(),
            'integrantes' => InnovaIntegrante::query()->where('activo', true)->orderBy('orden')->get(),
            'proyectos' => InnovaProyecto::query()->where('publicado', true)->orderByDesc('fecha')->get(),
            'antispam' => Antispam::generar('innova', false),
        ]);
    }

    public function solicitar(Request $request): RedirectResponse
    {
        Antispam::validar($request, 'innova', false);

        $datos = $request->validate([
            'empresa' => ['required', 'string', 'max:150'],
            'contacto' => ['required', 'string', 'max:150'],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'innova_servicio_id' => ['nullable', 'exists:innova_servicios,id'],
            'mensaje' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        InnovaSolicitud::query()->create($datos);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Solicitud enviada. El equipo de INNOVA se comunicará con usted.']);

        return back();
    }

    public function postular(Request $request): RedirectResponse
    {
        Antispam::validar($request, 'innova', false);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'registro' => ['required', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'semestre' => ['nullable', 'string', 'max:30'],
            'motivacion' => ['required', 'string', 'min:20', 'max:3000'],
        ]);

        InnovaPostulacion::query()->create($datos);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Postulación registrada. ¡Gracias por su interés en INNOVA!']);

        return back();
    }
}

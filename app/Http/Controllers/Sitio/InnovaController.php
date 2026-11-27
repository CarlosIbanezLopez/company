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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Empresa Junior INNOVA: información, servicios, integrantes, proyectos y
 * formularios de solicitud de servicio y postulación (RF-10).
 */
class InnovaController extends Controller
{
    /** Ambos formularios de la página comparten el mismo token antispam (sin pregunta). */
    private const FORMULARIO = 'innova';

    public function index(): JsonResponse
    {
        $descripcion = Pagina::query()->where('seccion', 'innova')->where('publicado', true)->orderBy('orden')->orderBy('id')->first();

        return response()->json([
            'descripcion' => $descripcion ? ['titulo' => $descripcion->titulo, 'html' => Markdown::html($descripcion->contenido)] : null,
            'servicios' => InnovaServicio::query()->where('activo', true)->orderBy('orden')->get(),
            'integrantes' => InnovaIntegrante::query()->where('activo', true)->orderBy('orden')->get(),
            'proyectos' => InnovaProyecto::query()->where('publicado', true)
                ->orderByRaw('fecha is null')->orderByDesc('fecha')->orderByDesc('id')->get(),
            'antispam' => Antispam::generar(self::FORMULARIO, false),
        ]);
    }

    public function solicitar(Request $request): JsonResponse
    {
        Antispam::validar($request, self::FORMULARIO, false);

        $datos = $request->validate([
            'empresa' => ['required', 'string', 'max:150'],
            'contacto' => ['required', 'string', 'max:150'],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            // "bail": no consultar la tabla si el valor no es numérico (PostgreSQL fallaría).
            'innova_servicio_id' => ['bail', 'nullable', 'integer', 'exists:innova_servicios,id'],
            'mensaje' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        InnovaSolicitud::query()->create($datos);
        Antispam::consumir($request);

        return response()->json([
            'message' => 'Solicitud enviada. El equipo de INNOVA se comunicará con usted.',
            // Token nuevo para un siguiente envío sin recargar la página.
            'antispam' => Antispam::generar(self::FORMULARIO, false),
        ], 201);
    }

    public function postular(Request $request): JsonResponse
    {
        Antispam::validar($request, self::FORMULARIO, false);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'registro' => ['required', 'string', 'max:20'],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'semestre' => ['nullable', 'string', 'max:30'],
            'motivacion' => ['required', 'string', 'min:20', 'max:3000'],
        ]);

        InnovaPostulacion::query()->create($datos);
        Antispam::consumir($request);

        return response()->json([
            'message' => 'Postulación registrada. ¡Gracias por su interés en INNOVA!',
            'antispam' => Antispam::generar(self::FORMULARIO, false),
        ], 201);
    }
}

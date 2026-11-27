<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Mail\NuevaConsultaMail;
use App\Models\CategoriaConsulta;
use App\Models\Configuracion;
use App\Models\Consulta;
use App\Support\Antispam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Formulario de contacto (RF-15) e información oficial de contacto y mapa (RF-16).
 * Los datos de contacto y redes sociales llegan a la SPA con GET /api/sitio.
 */
class ContactoController extends Controller
{
    private const FORMULARIO = 'contacto';

    public function index(): JsonResponse
    {
        $c = Configuracion::todas();

        return response()->json([
            'categorias' => CategoriaConsulta::query()->orderBy('nombre')->get(['id', 'nombre']),
            'mapa' => ($c['mapa_url'] ?? null) ?: null,
            'antispam' => Antispam::generar(self::FORMULARIO),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 +()-]+$/'],
            // "bail": no consultar la tabla si el valor no es numérico (PostgreSQL fallaría).
            'categoria_consulta_id' => ['bail', 'required', 'integer', 'exists:categorias_consulta,id'],
            'mensaje' => ['required', 'string', 'min:10', 'max:3000'],
        ], [], [
            'categoria_consulta_id' => 'categoría',
        ]);

        Antispam::validar($request, self::FORMULARIO);

        $consulta = Consulta::query()->create([...$datos, 'estado' => 'nueva', 'ip' => $request->ip()]);
        Antispam::consumir($request);

        $consulta->load('categoria');
        $destino = $consulta->categoria?->correo_destino ?: Configuracion::valor('correo_notificaciones') ?: Configuracion::valor('correo');

        if ($destino) {
            try {
                Mail::to($destino)->send(new NuevaConsultaMail($consulta));
            } catch (Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'message' => 'Su consulta fue enviada. Le responderemos a la brevedad.',
            // Token y pregunta nuevos por si el visitante envía otra consulta sin recargar la página.
            'antispam' => Antispam::generar(self::FORMULARIO),
        ], 201);
    }
}

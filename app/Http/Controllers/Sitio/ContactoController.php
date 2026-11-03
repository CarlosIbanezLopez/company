<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Mail\NuevaConsultaMail;
use App\Models\CategoriaConsulta;
use App\Models\Configuracion;
use App\Models\Consulta;
use App\Support\Antispam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Formulario de contacto (RF-15) e información oficial de contacto y mapa (RF-16).
 */
class ContactoController extends Controller
{
    public function index(): Response
    {
        $c = Configuracion::todas();

        return Inertia::render('sitio/Contacto', [
            'categorias' => CategoriaConsulta::query()->orderBy('nombre')->get(['id', 'nombre']),
            'mapa' => $c['mapa_url'] ?? null,
            'antispam' => Antispam::generar('contacto'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'correo' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 +()-]+$/'],
            'categoria_consulta_id' => ['required', 'exists:categorias_consulta,id'],
            'mensaje' => ['required', 'string', 'min:10', 'max:3000'],
        ], [], [
            'categoria_consulta_id' => 'categoría',
        ]);

        Antispam::validar($request, 'contacto');

        $consulta = Consulta::query()->create([...$datos, 'estado' => 'nueva', 'ip' => $request->ip()]);
        $consulta->load('categoria');

        $destino = $consulta->categoria?->correo_destino ?: Configuracion::valor('correo_notificaciones') ?: Configuracion::valor('correo');

        if ($destino) {
            try {
                Mail::to($destino)->send(new NuevaConsultaMail($consulta));
            } catch (Throwable $e) {
                report($e);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Su consulta fue enviada. Le responderemos a la brevedad.']);

        return redirect()->route('contacto');
    }
}

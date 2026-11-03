<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CU2 - Configuración del portal: mensaje de presentación (RF-01)
 * e información oficial de contacto y redes sociales (RF-16).
 */
class ConfiguracionController extends Controller
{
    /** @return array<string, array{titulo: string, descripcion: string, campos: array<int, array<string, mixed>>}> */
    public static function grupos(): array
    {
        return [
            'portal' => [
                'titulo' => 'Página principal',
                'descripcion' => 'Textos de presentación que se muestran en el inicio del sitio.',
                'campos' => [
                    ['nombre' => 'nombre_carrera', 'etiqueta' => 'Nombre de la carrera', 'tipo' => 'text', 'requerido' => true],
                    ['nombre' => 'nombre_facultad', 'etiqueta' => 'Facultad', 'tipo' => 'text'],
                    ['nombre' => 'nombre_universidad', 'etiqueta' => 'Universidad', 'tipo' => 'text'],
                    ['nombre' => 'presentacion_titulo', 'etiqueta' => 'Título del mensaje de presentación', 'tipo' => 'text', 'ancho' => 'full'],
                    ['nombre' => 'presentacion_texto', 'etiqueta' => 'Mensaje de presentación', 'tipo' => 'markdown', 'ancho' => 'full'],
                    ['nombre' => 'presentacion_autor', 'etiqueta' => 'Firma del mensaje (ej. Director de Carrera)', 'tipo' => 'text'],
                    ['nombre' => 'video_presentacion', 'etiqueta' => 'Video de presentación (URL de YouTube)', 'tipo' => 'url'],
                ],
            ],
            'contacto' => [
                'titulo' => 'Contacto y redes sociales',
                'descripcion' => 'Datos oficiales que aparecen en la sección Contacto y en el pie de página.',
                'campos' => [
                    ['nombre' => 'direccion', 'etiqueta' => 'Dirección', 'tipo' => 'text', 'ancho' => 'full'],
                    ['nombre' => 'mapa_url', 'etiqueta' => 'URL del mapa (Google Maps "insertar mapa")', 'tipo' => 'url', 'ancho' => 'full'],
                    ['nombre' => 'telefonos', 'etiqueta' => 'Teléfonos', 'tipo' => 'text'],
                    ['nombre' => 'whatsapp', 'etiqueta' => 'WhatsApp (solo números con código de país)', 'tipo' => 'text'],
                    ['nombre' => 'correo', 'etiqueta' => 'Correo institucional', 'tipo' => 'email'],
                    ['nombre' => 'correo_notificaciones', 'etiqueta' => 'Correo que recibe las consultas del formulario', 'tipo' => 'email'],
                    ['nombre' => 'horario', 'etiqueta' => 'Horario de atención', 'tipo' => 'text', 'ancho' => 'full'],
                    ['nombre' => 'facebook', 'etiqueta' => 'Facebook', 'tipo' => 'url'],
                    ['nombre' => 'instagram', 'etiqueta' => 'Instagram', 'tipo' => 'url'],
                    ['nombre' => 'youtube', 'etiqueta' => 'YouTube', 'tipo' => 'url'],
                    ['nombre' => 'tiktok', 'etiqueta' => 'TikTok', 'tipo' => 'url'],
                    ['nombre' => 'linkedin', 'etiqueta' => 'LinkedIn', 'tipo' => 'url'],
                ],
            ],
        ];
    }

    public function edit(string $grupo): Response
    {
        $definicion = self::grupos()[$grupo] ?? abort(404);
        $todas = Configuracion::todas();

        $valores = [];
        foreach ($definicion['campos'] as $campo) {
            $valores[$campo['nombre']] = $todas[$campo['nombre']] ?? '';
        }

        return Inertia::render('admin/crud/Form', [
            'config' => [
                'titulo' => $definicion['titulo'],
                'singular' => 'configuración',
                'descripcion' => $definicion['descripcion'],
                'urls' => ['index' => null, 'actualizar' => route('admin.configuracion.update', $grupo)],
                'acciones' => [],
                'accionesFila' => [],
            ],
            'campos' => $definicion['campos'],
            'valores' => $valores,
            'registroId' => $grupo,
        ]);
    }

    public function update(Request $request, string $grupo): RedirectResponse
    {
        $definicion = self::grupos()[$grupo] ?? abort(404);

        $reglas = [];
        foreach ($definicion['campos'] as $campo) {
            $reglas[$campo['nombre']] = match ($campo['tipo']) {
                'email' => ['nullable', 'email', 'max:150'],
                'url' => ['nullable', 'url', 'max:500'],
                'markdown' => ['nullable', 'string', 'max:5000'],
                default => [($campo['requerido'] ?? false) ? 'required' : 'nullable', 'string', 'max:255'],
            };
        }

        foreach ($request->validate($reglas) as $clave => $valor) {
            Configuracion::guardar($clave, $valor, $grupo);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuración guardada.']);

        return back();
    }
}

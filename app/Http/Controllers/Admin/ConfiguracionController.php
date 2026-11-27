<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use App\Support\ReglasCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CU2 - Configuración del portal: mensaje de presentación (RF-01)
 * e información oficial de contacto y redes sociales (RF-16).
 *
 *   GET /api/admin/configuracion/{portal|contacto}  (portal.ver)    -> mismo formato que el formulario CRUD
 *   PUT /api/admin/configuracion/{portal|contacto}  (portal.editar)
 *
 * En el formato del formulario, config.uri = "configuracion" y registroId = grupo,
 * de modo que el formulario genérico guarda con PUT /api/admin/configuracion/{grupo}.
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

    public function show(Request $request, string $grupo): JsonResponse
    {
        $definicion = self::definicion($grupo);
        $usuario = $request->user();

        return response()->json([
            'config' => [
                'uri' => 'configuracion',
                'recurso' => 'portal',
                'titulo' => $definicion['titulo'],
                'singular' => 'configuración',
                'femenino' => true,
                'descripcion' => $definicion['descripcion'],
                'permisos' => [
                    'crear' => false,
                    'editar' => (bool) $usuario?->tienePermiso('portal.editar'),
                    'eliminar' => false,
                ],
                'acciones' => [],
                'accionesFila' => [],
            ],
            'campos' => ReglasCliente::campos($definicion['campos'], self::reglas($definicion)),
            'valores' => self::valores($definicion),
            'registroId' => $grupo,
        ]);
    }

    public function update(Request $request, string $grupo): JsonResponse
    {
        $definicion = self::definicion($grupo);

        $datos = $request->validate(self::reglas($definicion), [], ReglasCliente::atributos($definicion['campos']));

        DB::transaction(function () use ($datos, $grupo) {
            foreach ($datos as $clave => $valor) {
                Configuracion::guardar($clave, $valor, $grupo);
            }
        });

        return response()->json([
            'message' => 'Configuración guardada.',
            'valores' => self::valores($definicion),
        ]);
    }

    /** @return array{titulo: string, descripcion: string, campos: array<int, array<string, mixed>>} */
    private static function definicion(string $grupo): array
    {
        return self::grupos()[$grupo] ?? abort(404);
    }

    /**
     * Reglas de validación según el tipo de cada campo.
     *
     * @param  array{campos: array<int, array<string, mixed>>}  $definicion
     * @return array<string, array<int, string>>
     */
    private static function reglas(array $definicion): array
    {
        $reglas = [];

        foreach ($definicion['campos'] as $campo) {
            $reglas[$campo['nombre']] = match ($campo['tipo']) {
                'email' => ['nullable', 'email', 'max:150'],
                'url' => ['nullable', 'url', 'max:500'],
                'markdown' => ['nullable', 'string', 'max:5000'],
                default => [! empty($campo['requerido']) ? 'required' : 'nullable', 'string', 'max:255'],
            };
        }

        return $reglas;
    }

    /**
     * @param  array{campos: array<int, array<string, mixed>>}  $definicion
     * @return array<string, string|null>
     */
    private static function valores(array $definicion): array
    {
        $todas = Configuracion::todas();
        $valores = [];

        foreach ($definicion['campos'] as $campo) {
            $valores[$campo['nombre']] = $todas[$campo['nombre']] ?? '';
        }

        return $valores;
    }
}

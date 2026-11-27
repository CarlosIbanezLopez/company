<?php

namespace Tests\Feature\Api;

use App\Mail\NuevaConsultaMail;
use App\Models\CategoriaConsulta;
use App\Models\Configuracion;
use App\Models\Consulta;
use App\Models\InnovaServicio;
use App\Support\Antispam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Formularios públicos (contacto e INNOVA) con antispam sin sesión: campo trampa,
 * tiempo mínimo, token cifrado por formulario y pregunta de verificación.
 */
class SitioFormulariosTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE_ANTISPAM = 'No pudimos verificar el envío. Recargue la página e intente de nuevo.';

    private CategoriaConsulta $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        // Estas pruebas envían varios formularios seguidos (el límite real es de 6 por minuto).
        $this->withoutMiddleware(ThrottleRequests::class);

        Configuracion::guardar('correo_notificaciones', 'consultas@ejemplo.test', 'contacto');
        $this->categoria = CategoriaConsulta::query()->create(['nombre' => 'Admisión', 'correo_destino' => 'admision@ejemplo.test']);
    }

    public function test_contacto_valido_guarda_la_consulta_y_notifica(): void
    {
        Mail::fake();
        [$token, $respuesta] = $this->antispamContacto();
        $this->travel(5)->seconds();

        $this->enviarContacto(['antispam_token' => $token, 'antispam_respuesta' => (string) $respuesta])
            ->assertCreated()
            ->assertJsonPath('message', 'Su consulta fue enviada. Le responderemos a la brevedad.')
            ->assertJsonStructure(['message', 'antispam' => ['token', 'pregunta']]);

        $consulta = Consulta::query()->where('correo', 'visitante@ejemplo.test')->firstOrFail();
        $this->assertSame('nueva', $consulta->estado);
        $this->assertSame($this->categoria->id, $consulta->categoria_consulta_id);
        $this->assertSame('127.0.0.1', $consulta->ip);

        Mail::assertSent(NuevaConsultaMail::class, function (NuevaConsultaMail $correo) use ($consulta) {
            // El enlace del correo lleva a la pantalla de edición del panel en la SPA.
            $correo->assertSeeInHtml(url('/admin/consultas/'.$consulta->id.'/editar'), false);

            return $correo->hasTo('admision@ejemplo.test') && $correo->hasReplyTo('visitante@ejemplo.test');
        });
    }

    public function test_el_mismo_token_no_puede_reutilizarse_despues_de_un_envio_exitoso(): void
    {
        Mail::fake();
        [$token, $respuesta] = $this->antispamContacto();
        $this->travel(5)->seconds();

        $nuevo = $this->enviarContacto(['antispam_token' => $token, 'antispam_respuesta' => $respuesta])->assertCreated()->json('antispam');

        $this->enviarContacto(['antispam_token' => $token, 'antispam_respuesta' => $respuesta])
            ->assertUnprocessable()
            ->assertJsonPath('errors.antispam.0', 'El formulario expiró. Recargue la página e intente de nuevo.');

        // El token nuevo de la respuesta permite enviar otra consulta.
        $this->travel(5)->seconds();
        $this->enviarContacto(['antispam_token' => $nuevo['token'], 'antispam_respuesta' => $this->resolver($nuevo['pregunta'])])->assertCreated();

        $this->assertSame(2, Consulta::query()->count());
    }

    public function test_contacto_rechaza_envios_automatizados_con_422_en_espanol(): void
    {
        [$token, $respuesta] = $this->antispamContacto();
        $this->travel(5)->seconds();
        $valido = ['antispam_token' => $token, 'antispam_respuesta' => $respuesta];

        // Campo trampa lleno.
        $this->enviarContacto([...$valido, 'sitio_web' => 'http://spam.test'])
            ->assertUnprocessable()
            ->assertJsonPath('message', self::MENSAJE_ANTISPAM)
            ->assertJsonPath('errors.antispam.0', self::MENSAJE_ANTISPAM);

        // Respuesta incorrecta o ausente.
        $this->enviarContacto([...$valido, 'antispam_respuesta' => $respuesta + 1])
            ->assertUnprocessable()
            ->assertJsonPath('errors.antispam_respuesta.0', 'La respuesta de verificación no es correcta.');
        $this->enviarContacto([...$valido, 'antispam_respuesta' => ''])->assertJsonValidationErrors(['antispam_respuesta']);

        // Token ausente, alterado o creado para otro formulario.
        $this->enviarContacto(['antispam_respuesta' => $respuesta])->assertJsonPath('errors.antispam.0', self::MENSAJE_ANTISPAM);
        $this->enviarContacto([...$valido, 'antispam_token' => substr($token, 0, -4).'abcd'])->assertJsonPath('errors.antispam.0', self::MENSAJE_ANTISPAM);
        $this->enviarContacto([...$valido, 'antispam_token' => Crypt::encryptString((string) (time() - 60))])->assertJsonPath('errors.antispam.0', self::MENSAJE_ANTISPAM);
        $this->enviarContacto([...$valido, 'antispam_token' => Antispam::generar('innova', false)['token']])->assertJsonPath('errors.antispam.0', self::MENSAJE_ANTISPAM);

        $this->assertSame(0, Consulta::query()->count());
    }

    public function test_contacto_rechaza_el_envio_demasiado_rapido_o_vencido(): void
    {
        [$token, $respuesta] = $this->antispamContacto();

        $this->enviarContacto(['antispam_token' => $token, 'antispam_respuesta' => $respuesta])
            ->assertUnprocessable()
            ->assertJsonPath('errors.antispam.0', 'El formulario se envió demasiado rápido. Intente nuevamente.');

        $this->travel(Antispam::SEGUNDOS_MAXIMOS + 60)->seconds();

        $this->enviarContacto(['antispam_token' => $token, 'antispam_respuesta' => $respuesta])
            ->assertUnprocessable()
            ->assertJsonPath('errors.antispam.0', 'El formulario expiró. Recargue la página e intente de nuevo.');

        $this->assertSame(0, Consulta::query()->count());
    }

    public function test_contacto_valida_los_datos_con_mensajes_en_espanol(): void
    {
        [$token, $respuesta] = $this->antispamContacto();
        $this->travel(5)->seconds();

        $this->postJson('/api/contacto', ['nombre' => '', 'correo' => 'no-es-correo', 'mensaje' => 'corto', 'categoria_consulta_id' => 'abc', 'antispam_token' => $token, 'antispam_respuesta' => $respuesta])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre', 'correo', 'mensaje', 'categoria_consulta_id'])
            ->assertJsonPath('errors.nombre.0', 'El campo nombre es obligatorio.')
            ->assertJsonPath('errors.correo.0', 'El campo correo electrónico debe ser un correo electrónico válido.')
            ->assertJsonPath('errors.categoria_consulta_id.0', 'El campo categoría debe ser un número entero.')
            ->assertJsonPath('message', fn (string $mensaje) => str_starts_with($mensaje, 'El campo nombre es obligatorio. (y 3 errores más)'));

        $this->postJson('/api/contacto', ['nombre' => 'Visitante', 'correo' => 'a@b.test', 'mensaje' => 'Mensaje suficientemente largo', 'categoria_consulta_id' => 999, 'antispam_token' => $token, 'antispam_respuesta' => $respuesta])
            ->assertUnprocessable()
            ->assertJsonPath('errors.categoria_consulta_id.0', 'El valor seleccionado en categoría no es válido.');

        $this->assertSame(0, Consulta::query()->count());
    }

    public function test_solicitud_de_servicio_innova(): void
    {
        $servicio = InnovaServicio::query()->create(['nombre' => 'Plan de negocios']);
        $token = $this->getJson('/api/innova')->assertOk()->json('antispam.token');
        $this->travel(5)->seconds();

        $this->postJson('/api/innova/solicitudes', [
            'empresa' => 'Empresa Test', 'contacto' => 'Ana', 'correo' => 'ana@ejemplo.test', 'innova_servicio_id' => $servicio->id,
            'mensaje' => 'Necesitamos un plan de negocios.', 'antispam_token' => $token, 'sitio_web' => '',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Solicitud enviada. El equipo de INNOVA se comunicará con usted.')
            ->assertJsonStructure(['message', 'antispam' => ['token']]);

        $this->assertDatabaseHas('innova_solicitudes', ['empresa' => 'Empresa Test', 'innova_servicio_id' => $servicio->id, 'estado' => 'pendiente']);

        // Servicio con un valor no numérico: error de validación (no de base de datos).
        $nuevo = Antispam::generar('innova', false)['token'];
        $this->travel(5)->seconds();
        $this->postJson('/api/innova/solicitudes', ['empresa' => 'Otra', 'contacto' => 'Luis', 'correo' => 'luis@ejemplo.test', 'innova_servicio_id' => 'abc', 'mensaje' => 'Mensaje de prueba largo.', 'antispam_token' => $nuevo])
            ->assertUnprocessable()
            ->assertJsonPath('errors.innova_servicio_id.0', 'El campo servicio debe ser un número entero.');
    }

    public function test_postulacion_a_innova(): void
    {
        $token = $this->getJson('/api/innova')->json('antispam.token');
        $this->travel(5)->seconds();

        $datos = [
            'nombre' => 'Estudiante Postulante', 'registro' => '220012345', 'correo' => 'postulante@ejemplo.test', 'semestre' => '6to',
            'motivacion' => 'Quiero aplicar lo aprendido en proyectos reales.', 'antispam_token' => $token, 'sitio_web' => '',
        ];

        $this->postJson('/api/innova/postulaciones', $datos)
            ->assertCreated()
            ->assertJsonPath('message', 'Postulación registrada. ¡Gracias por su interés en INNOVA!');

        $this->assertDatabaseHas('innova_postulaciones', ['registro' => '220012345', 'estado' => 'pendiente']);

        // Un token del formulario de contacto no sirve para INNOVA.
        [$tokenContacto] = $this->antispamContacto();
        $this->travel(5)->seconds();
        $this->postJson('/api/innova/postulaciones', [...$datos, 'antispam_token' => $tokenContacto])
            ->assertUnprocessable()
            ->assertJsonPath('errors.antispam.0', self::MENSAJE_ANTISPAM);
    }

    /** @return array{0: string, 1: int} token y respuesta correcta de la pregunta */
    private function antispamContacto(): array
    {
        $antispam = $this->getJson('/api/contacto')->assertOk()->json('antispam');

        return [$antispam['token'], $this->resolver($antispam['pregunta'])];
    }

    private function resolver(string $pregunta): int
    {
        $this->assertSame(1, preg_match('/(\d+) \+ (\d+)/', $pregunta, $m));

        return (int) $m[1] + (int) $m[2];
    }

    /** @param  array<string, mixed>  $antispam */
    private function enviarContacto(array $antispam): TestResponse
    {
        return $this->postJson('/api/contacto', [
            'nombre' => 'Visitante de prueba',
            'correo' => 'visitante@ejemplo.test',
            'telefono' => '+591 70000000',
            'categoria_consulta_id' => $this->categoria->id,
            'mensaje' => 'Quisiera información sobre la admisión.',
            'sitio_web' => '',
            ...$antispam,
        ]);
    }
}

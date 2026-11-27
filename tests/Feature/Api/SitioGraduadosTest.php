<?php

namespace Tests\Feature\Api;

use App\Models\Graduado;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Programa Alumni: registro del graduado (crea la cuenta y entrega un token Bearer)
 * y actualización de su ficha con la sesión iniciada.
 */
class SitioGraduadosTest extends TestCase
{
    use RefreshDatabase;

    private const DATOS = [
        'nombre' => 'Nuevo Graduado',
        'ci' => '9999999',
        'registro' => '210000001',
        'anio_graduacion' => 2024,
        'modalidad_titulacion' => 'tesis',
        'situacion_laboral' => 'dependiente',
        'telefono' => '+591 70000000',
        'email' => 'nuevo@graduado.test',
        'password' => 'secreto123',
        'password_confirmation' => 'secreto123',
        'sitio_web' => '',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Rol::query()->create(['nombre' => 'Graduado', 'descripcion' => 'Titulado que actualiza sus datos desde el sitio', 'acceso_panel' => false]);
    }

    public function test_registro_crea_la_cuenta_y_entrega_token_y_usuario(): void
    {
        $respuesta = $this->registrar()
            ->assertCreated()
            ->assertJsonPath('message', 'Registro completado. Puede actualizar sus datos cuando lo necesite.')
            ->assertJsonStructure([
                'message',
                'token',
                'usuario' => ['id', 'name', 'email', 'telefono', 'rol', 'panel', 'es_graduado', 'permisos', 'preferencias', 'ultimo_acceso', 'created_at'],
            ])
            ->assertJsonPath('usuario.name', 'Nuevo Graduado')
            ->assertJsonPath('usuario.email', 'nuevo@graduado.test')
            ->assertJsonPath('usuario.rol', 'Graduado')
            ->assertJsonPath('usuario.panel', false)
            ->assertJsonPath('usuario.es_graduado', true)
            ->assertJsonPath('usuario.permisos', []);

        $usuario = User::query()->where('email', 'nuevo@graduado.test')->firstOrFail();
        $this->assertSame('Graduado', $usuario->rol->nombre);
        $this->assertSame('9999999', $usuario->graduado->ci);
        $this->assertSame('nuevo@graduado.test', $usuario->graduado->correo);
        $this->assertCount(1, $usuario->tokens);

        // Queda registrado como inicio de sesión exitoso (bitácora de autenticación).
        $this->assertDatabaseHas('inicios_sesion', ['user_id' => $usuario->id, 'email' => 'nuevo@graduado.test', 'exitoso' => true]);

        // Con el token puede consultar y actualizar su ficha.
        $token = $respuesta->json('token');

        $this->withToken($token)->getJson('/api/graduados/mi-ficha')
            ->assertOk()
            ->assertJsonStructure(['graduado' => ['id', 'nombre', 'ci', 'registro', 'anio_graduacion', 'modalidad_titulacion', 'situacion_laboral', 'empresa', 'cargo', 'ciudad', 'correo', 'telefono', 'linkedin', 'updated_at'], 'situaciones', 'modalidades'])
            ->assertJsonPath('graduado.ci', '9999999')
            ->assertJsonPath('graduado.situacion_laboral', 'dependiente')
            ->assertJsonPath('situaciones.dependiente', 'Trabaja en relación de dependencia');

        $this->withToken($token)->putJson('/api/graduados/mi-ficha', [
            'nombre' => 'Nuevo Graduado', 'ci' => '9999999', 'anio_graduacion' => 2024, 'situacion_laboral' => 'independiente',
            'empresa' => 'Mi emprendimiento', 'correo' => 'nuevo@graduado.test', 'linkedin' => 'https://www.linkedin.com/in/nuevo',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Sus datos fueron actualizados.')
            ->assertJsonPath('graduado.situacion_laboral', 'independiente')
            ->assertJsonPath('graduado.empresa', 'Mi emprendimiento');

        $this->assertSame('independiente', Graduado::query()->where('user_id', $usuario->id)->value('situacion_laboral'));

        $this->withToken($token)->getJson('/api/graduados')->assertOk()->assertJsonPath('tieneFicha', true)->assertJsonPath('total', 1);
    }

    public function test_la_ficha_requiere_iniciar_sesion(): void
    {
        $this->getJson('/api/graduados/mi-ficha')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Su sesión no es válida o expiró. Inicie sesión nuevamente.']);

        $this->putJson('/api/graduados/mi-ficha', [])->assertUnauthorized();
        $this->withToken('token-invalido')->getJson('/api/graduados/mi-ficha')->assertUnauthorized();
    }

    public function test_usuario_sin_ficha_la_crea_al_guardar(): void
    {
        $usuario = User::factory()->create(['email' => 'sinficha@ejemplo.test']);
        $token = $usuario->createToken('spa')->plainTextToken;

        $this->withToken($token)->getJson('/api/graduados/mi-ficha')->assertOk()->assertJsonPath('graduado', null);

        $this->withToken($token)->putJson('/api/graduados/mi-ficha', ['nombre' => 'Sin Ficha', 'ci' => '123', 'anio_graduacion' => 2020, 'situacion_laboral' => 'otro', 'correo' => 'sinficha@ejemplo.test'])
            ->assertOk()
            ->assertJsonPath('graduado.user_id', $usuario->id);

        $this->withToken($token)->putJson('/api/graduados/mi-ficha', ['nombre' => '', 'ci' => '123', 'anio_graduacion' => 1800, 'situacion_laboral' => 'astronauta', 'correo' => 'x'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nombre.0', 'El campo nombre es obligatorio.')
            ->assertJsonPath('errors.situacion_laboral.0', 'El valor seleccionado en situación laboral no es válido.')
            ->assertJsonValidationErrors(['anio_graduacion', 'correo']);
    }

    public function test_registro_valida_datos_y_antispam_en_espanol(): void
    {
        User::factory()->create(['email' => 'repetido@graduado.test']);

        $this->registrar(['email' => 'repetido@graduado.test', 'password_confirmation' => 'otra-clave', 'anio_graduacion' => 1900])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'El valor de correo electrónico ya está registrado.')
            ->assertJsonPath('errors.password.0', 'La confirmación de contraseña no coincide.')
            ->assertJsonValidationErrors(['anio_graduacion']);

        $this->registrar(['sitio_web' => 'http://spam.test'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.antispam.0', 'No pudimos verificar el envío. Recargue la página e intente de nuevo.');

        $this->postJson('/api/graduados', self::DATOS)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['antispam']);

        $this->assertDatabaseMissing('users', ['email' => 'nuevo@graduado.test']);
    }

    public function test_con_sesion_iniciada_no_puede_registrar_otra_cuenta(): void
    {
        $usuario = User::factory()->create();

        $this->withToken($usuario->createToken('spa')->plainTextToken);

        $this->registrar()
            ->assertForbidden()
            ->assertExactJson(['message' => 'Ya tiene una sesión iniciada. Cierre la sesión para registrar una cuenta nueva.']);

        $this->assertDatabaseMissing('users', ['email' => 'nuevo@graduado.test']);
    }

    /** @param  array<string, mixed>  $cambios */
    private function registrar(array $cambios = []): TestResponse
    {
        $token = $this->getJson('/api/graduados')->assertOk()->json('antispam.token');
        $this->travel(5)->seconds();

        return $this->postJson('/api/graduados', [...self::DATOS, 'antispam_token' => $token, ...$cambios]);
    }
}

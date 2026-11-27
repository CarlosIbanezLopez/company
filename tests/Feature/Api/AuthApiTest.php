<?php

namespace Tests\Feature\Api;

use App\Models\Bitacora;
use App\Models\InicioSesion;
use App\Models\User;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Autenticación por tokens Sanctum, bitácora de inicios de sesión y cuenta del usuario.
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private const ESTRUCTURA_USUARIO = ['id', 'name', 'email', 'telefono', 'rol', 'panel', 'es_graduado', 'permisos', 'preferencias', 'ultimo_acceso', 'created_at'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeguridadSeeder::class);
    }

    private function usuario(string $correo): User
    {
        return User::query()->where('email', $correo)->firstOrFail();
    }

    /** Olvida el usuario resuelto por los guards (en una misma prueba se hacen varias solicitudes). */
    private function nuevaSolicitud(): void
    {
        $this->app['auth']->forgetGuards();
    }

    public function test_inicio_de_sesion_correcto_emite_token_y_registra_el_intento(): void
    {
        $respuesta = $this->postJson('/api/auth/login', ['email' => 'admin@carrera.test', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['message', 'token', 'usuario' => self::ESTRUCTURA_USUARIO])
            ->assertJsonPath('message', 'Sesión iniciada correctamente.')
            ->assertJsonPath('usuario.email', 'admin@carrera.test')
            ->assertJsonPath('usuario.rol', 'Administrador')
            ->assertJsonPath('usuario.panel', true)
            ->assertJsonPath('usuario.es_graduado', false);

        $admin = $this->usuario('admin@carrera.test');

        $this->assertContains('usuarios.eliminar', $respuesta->json('usuario.permisos'));
        $this->assertNotNull($respuesta->json('usuario.ultimo_acceso'));
        $this->assertSame(1, $admin->tokens()->count());

        $this->assertDatabaseHas('inicios_sesion', ['email' => 'admin@carrera.test', 'user_id' => $admin->id, 'exitoso' => true, 'motivo' => null]);
        $this->assertDatabaseHas('bitacora', ['accion' => 'inicio_sesion', 'modulo' => 'Autenticación', 'user_id' => $admin->id]);

        // El trigger trg_inicios_sesion_usuario actualizó el último acceso.
        $this->assertNotNull($admin->ultimo_acceso);
        $this->assertSame(0, $admin->intentos_fallidos);

        // El token sirve para las rutas protegidas.
        $this->nuevaSolicitud();
        $this->withToken($respuesta->json('token'))->getJson('/api/auth/usuario')
            ->assertOk()
            ->assertJsonPath('usuario.id', $admin->id);
    }

    public function test_el_correo_no_distingue_mayusculas(): void
    {
        $this->postJson('/api/auth/login', ['email' => '  Admin@Carrera.TEST ', 'password' => 'password'])->assertOk();
    }

    public function test_credenciales_incorrectas_devuelven_422_y_quedan_registradas(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'noticias@carrera.test', 'password' => 'incorrecta'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Las credenciales no coinciden con nuestros registros.')
            ->assertJsonPath('errors.email.0', 'Las credenciales no coinciden con nuestros registros.');

        $usuario = $this->usuario('noticias@carrera.test');

        $this->assertDatabaseHas('inicios_sesion', ['email' => 'noticias@carrera.test', 'user_id' => $usuario->id, 'exitoso' => false, 'motivo' => 'Credenciales incorrectas']);
        $this->assertSame(1, $usuario->intentos_fallidos, 'El trigger debe sumar el intento fallido.');
        $this->assertSame(0, PersonalAccessToken::query()->count());

        // Un correo que no existe también se registra (sin usuario) y responde lo mismo.
        $this->postJson('/api/auth/login', ['email' => 'nadie@carrera.test', 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Las credenciales no coinciden con nuestros registros.');

        $this->assertDatabaseHas('inicios_sesion', ['email' => 'nadie@carrera.test', 'user_id' => null, 'exitoso' => false, 'motivo' => 'Correo no registrado']);

        // El siguiente inicio exitoso reinicia el contador de intentos fallidos.
        $this->postJson('/api/auth/login', ['email' => 'noticias@carrera.test', 'password' => 'password'])->assertOk();
        $this->assertSame(0, $usuario->fresh()->intentos_fallidos);
    }

    public function test_validacion_del_formulario_de_inicio_de_sesion_en_espanol(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'no-es-correo'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'El campo correo electrónico debe ser un correo electrónico válido.')
            ->assertJsonPath('errors.password.0', 'El campo contraseña es obligatorio.')
            ->assertJsonPath('message', 'El campo correo electrónico debe ser un correo electrónico válido. (y 1 error más)');

        $this->assertSame(0, InicioSesion::query()->count());
    }

    public function test_cuenta_deshabilitada_no_puede_iniciar_sesion(): void
    {
        $this->usuario('noticias@carrera.test')->update(['activo' => false]);

        $this->postJson('/api/auth/login', ['email' => 'noticias@carrera.test', 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Su cuenta está deshabilitada. Contacte al administrador.');

        $this->assertDatabaseHas('inicios_sesion', ['email' => 'noticias@carrera.test', 'exitoso' => false, 'motivo' => 'Cuenta deshabilitada']);
        $this->assertSame(0, PersonalAccessToken::query()->count());
    }

    public function test_bloqueo_temporal_despues_de_cinco_intentos_fallidos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'revista@carrera.test', 'password' => 'mala'.$i])->assertStatus(422);
        }

        // El sexto intento queda bloqueado aunque la contraseña sea correcta.
        $respuesta = $this->postJson('/api/auth/login', ['email' => 'revista@carrera.test', 'password' => 'password'])
            ->assertStatus(429)
            ->assertHeader('Retry-After');

        $this->assertMatchesRegularExpression('/^Demasiados intentos de inicio de sesión\. Intente de nuevo en \d+ segundos\.$/', $respuesta->json('errors.email.0'));
        $this->assertSame($respuesta->json('errors.email.0'), $respuesta->json('message'));

        $this->assertSame(6, InicioSesion::query()->where('email', 'revista@carrera.test')->count());
        $this->assertDatabaseHas('inicios_sesion', ['email' => 'revista@carrera.test', 'exitoso' => false, 'motivo' => 'Bloqueo temporal por demasiados intentos']);
        $this->assertSame(0, PersonalAccessToken::query()->count());

        // El límite es por correo + IP: otra cuenta desde la misma IP puede entrar.
        $this->postJson('/api/auth/login', ['email' => 'secretaria@carrera.test', 'password' => 'password'])->assertOk();
    }

    public function test_cierre_de_sesion_revoca_el_token(): void
    {
        $token = $this->postJson('/api/auth/login', ['email' => 'graduados@carrera.test', 'password' => 'password'])->json('token');
        $usuario = $this->usuario('graduados@carrera.test');

        $this->nuevaSolicitud();
        $this->withToken($token)->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Sesión cerrada correctamente.');

        $this->assertSame(0, $usuario->tokens()->count());
        $this->assertDatabaseHas('bitacora', ['accion' => 'cierre_sesion', 'user_id' => $usuario->id]);

        $this->nuevaSolicitud();
        $this->withToken($token)->getJson('/api/auth/usuario')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Su sesión no es válida o expiró. Inicie sesión nuevamente.');
    }

    public function test_cierre_de_sesion_en_todos_los_dispositivos(): void
    {
        $usuario = $this->usuario('admin@carrera.test');
        $usuario->createToken('otro dispositivo');
        $token = $usuario->createToken('este dispositivo')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout', ['todos' => true])->assertOk();

        $this->assertSame(0, $usuario->tokens()->count());
    }

    public function test_usuario_autenticado_y_sin_sesion(): void
    {
        $this->getJson('/api/auth/usuario')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Su sesión no es válida o expiró. Inicie sesión nuevamente.');

        Sanctum::actingAs($this->usuario('noticias@carrera.test'));

        $respuesta = $this->getJson('/api/auth/usuario')
            ->assertOk()
            ->assertJsonStructure(['usuario' => self::ESTRUCTURA_USUARIO])
            ->assertJsonPath('usuario.rol', 'Responsable de Noticias')
            ->assertJsonPath('usuario.preferencias', null);

        $permisos = $respuesta->json('usuario.permisos');
        $this->assertContains('noticias.ver', $permisos);
        $this->assertContains('reportes.ver', $permisos);
        $this->assertNotContains('usuarios.ver', $permisos);
    }

    public function test_graduado_se_identifica_y_no_tiene_panel(): void
    {
        Sanctum::actingAs($this->usuario('graduado@carrera.test'));

        $this->getJson('/api/auth/usuario')
            ->assertOk()
            ->assertJsonPath('usuario.rol', 'Graduado')
            ->assertJsonPath('usuario.panel', false)
            ->assertJsonPath('usuario.es_graduado', true)
            ->assertJsonPath('usuario.permisos', []);
    }

    public function test_cuenta_deshabilitada_con_sesion_abierta_pierde_el_token(): void
    {
        $usuario = $this->usuario('secretaria@carrera.test');
        $token = $usuario->createToken('spa')->plainTextToken;
        $usuario->update(['activo' => false]);

        $this->withToken($token)->getJson('/api/auth/usuario')
            ->assertForbidden()
            ->assertJsonPath('message', 'Su cuenta está deshabilitada. Contacte al administrador.');

        $this->assertSame(0, $usuario->tokens()->count());
    }

    public function test_olvide_contrasena_envia_enlace_a_la_spa_sin_revelar_cuentas(): void
    {
        Notification::fake();
        $mensaje = 'Si el correo está registrado, le enviamos un enlace para restablecer la contraseña.';

        $this->postJson('/api/auth/olvide-contrasena', ['email' => 'noticias@carrera.test'])
            ->assertOk()
            ->assertJsonPath('message', $mensaje);

        $this->postJson('/api/auth/olvide-contrasena', ['email' => 'desconocido@carrera.test'])
            ->assertOk()
            ->assertJsonPath('message', $mensaje);

        $usuario = $this->usuario('noticias@carrera.test');

        Notification::assertSentTo($usuario, ResetPassword::class, function (ResetPassword $notificacion) use ($usuario) {
            $url = $notificacion->toMail($usuario)->actionUrl;

            return $url === url('/restablecer-contrasena/'.$notificacion->token).'?email='.urlencode('noticias@carrera.test');
        });
        Notification::assertCount(1);
    }

    public function test_restablecer_contrasena_con_el_token_del_correo(): void
    {
        $usuario = $this->usuario('revista@carrera.test');
        $usuario->createToken('sesion anterior');
        $token = Password::broker()->createToken($usuario);

        $this->postJson('/api/auth/restablecer-contrasena', [
            'token' => 'token-invalido', 'email' => 'revista@carrera.test', 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
        ])->assertStatus(422)->assertJsonPath('errors.email.0', 'El enlace para restablecer la contraseña no es válido o ya expiró. Solicite uno nuevo.');

        $this->postJson('/api/auth/restablecer-contrasena', [
            'token' => $token, 'email' => 'revista@carrera.test', 'password' => 'NuevaClave2026', 'password_confirmation' => 'otra',
        ])->assertStatus(422)->assertJsonPath('errors.password.0', 'La confirmación de contraseña no coincide.');

        $this->postJson('/api/auth/restablecer-contrasena', [
            'token' => $token, 'email' => 'revista@carrera.test', 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
        ])->assertOk()->assertJsonPath('message', 'Su contraseña fue restablecida. Ya puede iniciar sesión con la nueva contraseña.');

        $this->assertTrue(Hash::check('NuevaClave2026', $usuario->fresh()->password));
        $this->assertSame(0, $usuario->tokens()->count(), 'Se cierran las sesiones abiertas.');

        $this->postJson('/api/auth/login', ['email' => 'revista@carrera.test', 'password' => 'NuevaClave2026'])->assertOk();
    }

    public function test_actualizar_perfil(): void
    {
        $usuario = Sanctum::actingAs($this->usuario('noticias@carrera.test'));

        $this->putJson('/api/perfil', ['name' => '', 'email' => 'admin@carrera.test'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'El campo nombre es obligatorio.')
            ->assertJsonPath('errors.email.0', 'El valor de correo electrónico ya está registrado.');

        $this->putJson('/api/perfil', ['name' => 'Responsable de Prensa', 'email' => 'Prensa@Carrera.test', 'telefono' => '70011122'])
            ->assertOk()
            ->assertJsonPath('message', 'Perfil actualizado correctamente.')
            ->assertJsonPath('usuario.name', 'Responsable de Prensa')
            ->assertJsonPath('usuario.email', 'prensa@carrera.test')
            ->assertJsonPath('usuario.telefono', '70011122');

        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'email' => 'prensa@carrera.test']);
    }

    public function test_cambiar_contrasena_exige_la_actual(): void
    {
        $usuario = $this->usuario('secretaria@carrera.test');
        $otraSesion = $usuario->createToken('otro dispositivo');
        $token = $usuario->createToken('spa')->plainTextToken;

        $this->withToken($token)->putJson('/api/perfil/contrasena', [
            'current_password' => 'incorrecta', 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
        ])->assertStatus(422)->assertJsonPath('errors.current_password.0', 'La contraseña actual es incorrecta.');

        $this->withToken($token)->putJson('/api/perfil/contrasena', [
            'current_password' => 'password', 'password' => 'corta', 'password_confirmation' => 'corta',
        ])->assertStatus(422)->assertJsonPath('errors.password.0', 'El campo contraseña debe tener al menos 8 caracteres.');

        $this->withToken($token)->putJson('/api/perfil/contrasena', [
            'current_password' => 'password', 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
        ])->assertOk()->assertJsonPath('message', 'Contraseña actualizada correctamente.');

        $this->assertTrue(Hash::check('NuevaClave2026', $usuario->fresh()->password));
        // Se cierran las otras sesiones y se conserva la actual.
        $this->assertNull(PersonalAccessToken::query()->find($otraSesion->accessToken->id));
        $this->assertSame(1, $usuario->tokens()->count());
        $this->assertTrue(Bitacora::query()->where('user_id', $usuario->id)->where('descripcion', 'like', 'Cambió su contraseña%')->exists());
    }

    public function test_preferencias_de_visualizacion(): void
    {
        $usuario = Sanctum::actingAs($this->usuario('graduado@carrera.test'));

        $this->putJson('/api/perfil/preferencias', ['tema' => 'bebes', 'modo' => 'tarde', 'fuente' => 200, 'contraste' => 'quizas'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tema', 'modo', 'fuente', 'contraste'])
            ->assertJsonPath('errors.fuente.0', 'El tamaño de fuente debe estar entre 85 % y 150 %.');

        $this->putJson('/api/perfil/preferencias', ['tema' => 'jovenes', 'modo' => 'noche', 'fuente' => 120, 'contraste' => true])
            ->assertOk()
            ->assertJsonPath('message', 'Preferencias guardadas correctamente.')
            ->assertJsonPath('preferencias', ['tema' => 'jovenes', 'modo' => 'noche', 'fuente' => 120, 'contraste' => true]);

        // Cambio parcial: se conservan los demás valores.
        $this->putJson('/api/perfil/preferencias', ['modo' => 'dia'])
            ->assertOk()
            ->assertJsonPath('preferencias', ['tema' => 'jovenes', 'modo' => 'dia', 'fuente' => 120, 'contraste' => true]);

        $this->assertSame(['tema' => 'jovenes', 'modo' => 'dia', 'fuente' => 120, 'contraste' => true], $usuario->fresh()->preferencias);

        $this->getJson('/api/auth/usuario')->assertJsonPath('usuario.preferencias.modo', 'dia');
    }
}

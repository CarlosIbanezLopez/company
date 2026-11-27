<?php

namespace Tests\Feature\Api;

use App\Models\Acceso;
use App\Models\Bitacora;
use App\Models\ContadorRecurso;
use App\Models\InicioSesion;
use App\Models\User;
use App\Models\Visita;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Bitácora de auditoría: acciones del panel, inicios de sesión y tráfico de la API (accesos).
 */
class BitacoraApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeguridadSeeder::class);
    }

    private function usuario(string $correo): User
    {
        return User::query()->where('email', $correo)->firstOrFail();
    }

    private function comoUsuario(string $correo): User
    {
        $this->app['auth']->forgetGuards();

        return Sanctum::actingAs($this->usuario($correo));
    }

    public function test_cada_solicitud_a_la_api_queda_registrada_en_accesos(): void
    {
        $noticias = $this->comoUsuario('noticias@carrera.test');

        $this->getJson('/api/admin/noticias')->assertOk();
        $this->getJson('/api/admin/noticias?page=2')->assertOk();
        $this->getJson('/api/admin/usuarios')->assertForbidden();

        $this->assertDatabaseHas('accesos', [
            'user_id' => $noticias->id,
            'ip' => '127.0.0.1',
            'metodo' => 'GET',
            'ruta' => 'api/admin/noticias',
            'recurso' => 'api.admin.noticias.index',
            'estado' => 200,
        ]);
        $this->assertDatabaseHas('accesos', ['recurso' => 'api.admin.usuarios.index', 'estado' => 403, 'user_id' => $noticias->id]);

        // El trigger trg_accesos_contador acumula el total por recurso y método.
        $contador = ContadorRecurso::query()->where('recurso', 'api.admin.noticias.index')->where('metodo', 'GET')->firstOrFail();
        $this->assertSame(2, $contador->total);
        $this->assertSame('api/admin/noticias', $contador->ruta);
        $this->assertNotNull($contador->ultimo_acceso);
    }

    public function test_registra_solicitudes_anonimas_rechazadas_y_el_usuario_que_inicia_sesion(): void
    {
        $this->getJson('/api/auth/usuario')->assertUnauthorized();
        $this->assertDatabaseHas('accesos', ['recurso' => 'api.auth.usuario', 'estado' => 401, 'user_id' => null]);

        $this->postJson('/api/auth/login', ['email' => 'admin@carrera.test', 'password' => 'password'])->assertOk();
        $this->assertDatabaseHas('accesos', ['recurso' => 'api.auth.login', 'metodo' => 'POST', 'estado' => 200, 'user_id' => $this->usuario('admin@carrera.test')->id]);

        $this->assertTrue(Acceso::query()->where('duracion_ms', '>=', 0)->exists());
    }

    public function test_no_registra_el_contador_de_visitas_ni_las_solicitudes_options(): void
    {
        $this->postJson('/api/visitas', ['ruta' => '/noticias']);
        $this->call('OPTIONS', '/api/sitio');

        $this->assertSame(0, Acceso::query()->where('recurso', 'api.sitio.visitas')->count());
        $this->assertSame(0, Acceso::query()->where('metodo', 'OPTIONS')->count());
    }

    public function test_indice_de_acciones_con_formato_crud_y_filtros(): void
    {
        $admin = $this->comoUsuario('admin@carrera.test');
        Bitacora::registrar('crear', 'Noticias', 'Creó: Noticia de prueba', 1, $admin->id);
        Bitacora::registrar('eliminar', 'Noticias', 'Eliminó: Otra noticia', 2, $admin->id);

        $respuesta = $this->getJson('/api/admin/bitacora?accion=crear')
            ->assertOk()
            ->assertJsonPath('config.uri', 'bitacora')
            ->assertJsonPath('config.recurso', 'bitacora')
            ->assertJsonPath('config.permisos', ['crear' => false, 'editar' => false, 'eliminar' => false])
            ->assertJsonPath('consulta.accion', 'crear')
            ->assertJsonStructure(['config', 'columnas', 'filtros', 'buscar', 'consulta', 'registros' => ['data', 'current_page', 'last_page', 'total']]);

        $filas = collect($respuesta->json('registros.data'));
        $this->assertNotEmpty($filas);
        $this->assertTrue($filas->every(fn ($fila) => $fila['accion'] === 'crear'));
        $this->assertSame(['id', 'name'], array_keys($filas->first()['usuario']));
        $this->assertSame(['user_id', 'accion', 'modulo', 'desde', 'hasta'], collect($respuesta->json('filtros'))->pluck('clave')->all());
    }

    public function test_inicios_de_sesion_con_resumen_e_ips_con_fallos(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'noticias@carrera.test', 'password' => 'mala'])->assertStatus(422);
        $this->postJson('/api/auth/login', ['email' => 'noticias@carrera.test', 'password' => 'mala'])->assertStatus(422);
        $this->postJson('/api/auth/login', ['email' => 'noticias@carrera.test', 'password' => 'password'])->assertOk();
        InicioSesion::query()->create(['email' => 'otro@carrera.test', 'exitoso' => false, 'motivo' => 'Correo no registrado', 'ip' => '200.87.1.10']);

        $this->comoUsuario('admin@carrera.test');

        $respuesta = $this->getJson('/api/admin/bitacora/sesiones')
            ->assertOk()
            ->assertJsonPath('resumen', ['exitosas' => 1, 'fallidas' => 3, 'hoy' => 4, 'ipsDistintas' => 2])
            ->assertJsonPath('ipsConFallos.0', ['ip' => '127.0.0.1', 'total' => 2])
            ->assertJsonStructure(['registros' => ['data' => [['id', 'email', 'exitoso', 'motivo', 'ip', 'agente', 'created_at', 'usuario']], 'current_page', 'total', 'links']]);

        $primero = $respuesta->json('registros.data.0');
        $this->assertSame('otro@carrera.test', $primero['email']);
        $this->assertNull($primero['usuario']);

        $exitoso = collect($respuesta->json('registros.data'))->firstWhere('exitoso', true);
        $this->assertSame(['id' => $this->usuario('noticias@carrera.test')->id, 'name' => 'Responsable de Noticias'], $exitoso['usuario']);

        // Filtros: resultado, correo e IP.
        $this->getJson('/api/admin/bitacora/sesiones?exitoso=0')->assertJsonPath('registros.total', 3);
        $this->getJson('/api/admin/bitacora/sesiones?exitoso=1&email=NOTICIAS')->assertJsonPath('registros.total', 1);
        $this->getJson('/api/admin/bitacora/sesiones?ip=200.87.1.10')
            ->assertJsonPath('registros.total', 1)
            ->assertJsonPath('resumen.fallidas', 1);
        $this->getJson('/api/admin/bitacora/sesiones?desde=2000-01-01&hasta=2000-01-31')->assertJsonPath('registros.total', 0);
        $this->getJson('/api/admin/bitacora/sesiones?desde=no-es-fecha')
            ->assertStatus(422)
            ->assertJsonPath('errors.desde.0', 'El campo fecha desde debe ser una fecha válida.');
    }

    public function test_trafico_con_recursos_ips_paginas_y_distribucion_por_hora(): void
    {
        $noticias = $this->comoUsuario('noticias@carrera.test');
        $this->getJson('/api/admin/noticias')->assertOk();
        $this->getJson('/api/admin/eventos')->assertOk();
        Acceso::query()->create(['ip' => '190.129.5.5', 'metodo' => 'POST', 'ruta' => 'api/contacto', 'recurso' => 'api.sitio.contacto.store', 'estado' => 201, 'duracion_ms' => 12]);
        Visita::query()->create(['ruta' => '/noticias', 'ip' => '190.129.5.5']);
        Visita::query()->create(['ruta' => '/noticias', 'ip' => '190.129.5.5']);
        Visita::query()->create(['ruta' => '/', 'ip' => '127.0.0.1']);

        $this->comoUsuario('admin@carrera.test');

        $respuesta = $this->getJson('/api/admin/bitacora/trafico')
            ->assertOk()
            ->assertJsonStructure([
                'resumen' => ['solicitudes', 'hoy', 'ipsDistintas', 'usuariosDistintos'],
                'recursos' => [['recurso', 'metodo', 'ruta', 'total', 'ultimo_acceso']],
                'ips' => [['ip', 'total', 'ultimo']],
                'paginas' => [['ruta', 'total']],
                'porHora' => [['hora', 'total']],
                'registros' => ['data' => [['id', 'metodo', 'ruta', 'recurso', 'estado', 'duracion_ms', 'ip', 'created_at', 'usuario']], 'current_page', 'total'],
            ]);

        // Solo cuenta las solicitudes anteriores a esta consulta (esta se registra al terminar).
        $this->assertSame(3, $respuesta->json('resumen.solicitudes'));
        $this->assertSame(2, $respuesta->json('resumen.ipsDistintas'));
        $this->assertSame(1, $respuesta->json('resumen.usuariosDistintos'));
        $this->assertCount(24, $respuesta->json('porHora'));
        $this->assertSame(3, array_sum(array_column($respuesta->json('porHora'), 'total')));
        $this->assertSame(['ruta' => '/noticias', 'total' => 2], $respuesta->json('paginas.0'));
        $this->assertSame(['id' => $noticias->id, 'name' => 'Responsable de Noticias'], collect($respuesta->json('registros.data'))->firstWhere('recurso', 'api.admin.noticias.index')['usuario']);
        $this->assertContains('api.admin.noticias.index', array_column($respuesta->json('recursos'), 'recurso'));

        // Filtro por IP y método: recursos y páginas se calculan sobre los accesos y visitas de esa IP.
        $filtrado = $this->getJson('/api/admin/bitacora/trafico?ip=190.129.5.5&metodo=post')
            ->assertOk()
            ->assertJsonPath('resumen.solicitudes', 1)
            ->assertJsonPath('recursos.0.recurso', 'api.sitio.contacto.store')
            ->assertJsonPath('recursos.0.total', 1)
            ->assertJsonPath('ips.0.ip', '190.129.5.5')
            ->assertJsonPath('paginas', [['ruta' => '/noticias', 'total' => 2]])
            ->assertJsonPath('registros.total', 1);

        $this->assertSame(1, array_sum(array_column($filtrado->json('porHora'), 'total')));

        $this->getJson('/api/admin/bitacora/trafico?metodo=TRACE')->assertStatus(422)->assertJsonValidationErrors('metodo');
        $this->getJson('/api/admin/bitacora/trafico?desde=2000-01-01&hasta=2000-01-02')
            ->assertOk()
            ->assertJsonPath('resumen.solicitudes', 0)
            ->assertJsonPath('recursos', [])
            ->assertJsonPath('paginas', []);
    }

    public function test_la_bitacora_requiere_el_permiso_bitacora_ver(): void
    {
        $this->comoUsuario('noticias@carrera.test');

        foreach (['/api/admin/bitacora', '/api/admin/bitacora/sesiones', '/api/admin/bitacora/trafico'] as $uri) {
            $this->getJson($uri)->assertForbidden()->assertJsonPath('message', 'No tiene permiso para realizar esta acción.');
        }
    }
}

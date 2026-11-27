<?php

namespace Tests\Feature\Api;

use App\Models\Bitacora;
use App\Models\CategoriaConsulta;
use App\Models\Consulta;
use App\Models\User;
use App\Models\Visita;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tablero del panel (métricas del negocio y del flujo de accesos) y reportes.
 */
class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SeguridadSeeder::class);
    }

    private function comoUsuario(string $correo): User
    {
        $this->app['auth']->forgetGuards();

        return Sanctum::actingAs(User::query()->where('email', $correo)->firstOrFail());
    }

    public function test_tablero_del_administrador_con_metricas_de_acceso(): void
    {
        $this->postJson('/api/auth/login', ['email' => 'noticias@carrera.test', 'password' => 'mala'])->assertStatus(422);
        $this->postJson('/api/auth/login', ['email' => 'noticias@carrera.test', 'password' => 'password'])->assertOk();
        Visita::query()->create(['ruta' => '/noticias', 'ip' => '10.0.0.1']);
        Visita::query()->create(['ruta' => '/noticias', 'ip' => '10.0.0.2']);
        Visita::query()->create(['ruta' => '/', 'ip' => '10.0.0.1']);
        Consulta::query()->create(['categoria_consulta_id' => CategoriaConsulta::query()->create(['nombre' => 'Admisión'])->id, 'nombre' => 'Ana', 'correo' => 'ana@correo.test', 'mensaje' => 'Hola', 'estado' => 'nueva']);

        $this->comoUsuario('admin@carrera.test');

        $respuesta = $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'tarjetas' => [['titulo', 'valor', 'icono']],
                'consultas',
                'bitacora',
                'paginasMasVistas' => [['ruta', 'total']],
                'visitasPorDia' => [['fecha', 'total']],
                'sesiones' => ['exitosas', 'fallidas', 'porDia' => [['fecha', 'exitosas', 'fallidas']]],
                'recursosMasAccedidos' => [['recurso', 'metodo', 'ruta', 'total']],
                'ipsFrecuentes' => [['ip', 'total']],
                'negocio' => ['noticiasPorCategoria', 'consultasPorEstado' => [['etiqueta', 'total']], 'graduadosPorSituacion' => [['etiqueta', 'total']]],
            ]);

        $hoy = today()->toDateString();

        $this->assertCount(6, $respuesta->json('tarjetas'));
        $this->assertSame(3, collect($respuesta->json('tarjetas'))->firstWhere('titulo', 'Visitas hoy')['valor']);
        $this->assertSame(['ruta' => '/noticias', 'total' => 2], $respuesta->json('paginasMasVistas.0'));

        $this->assertCount(14, $respuesta->json('visitasPorDia'));
        $this->assertSame(['fecha' => $hoy, 'total' => 3], $respuesta->json('visitasPorDia.13'));

        $this->assertSame(1, $respuesta->json('sesiones.exitosas'));
        $this->assertSame(1, $respuesta->json('sesiones.fallidas'));
        $this->assertCount(14, $respuesta->json('sesiones.porDia'));
        $this->assertSame(['fecha' => $hoy, 'exitosas' => 1, 'fallidas' => 1], $respuesta->json('sesiones.porDia.13'));

        $this->assertContains('api.auth.login', array_column($respuesta->json('recursosMasAccedidos'), 'recurso'));
        $this->assertSame(['ip' => '127.0.0.1', 'total' => 2], $respuesta->json('ipsFrecuentes.0'));

        $this->assertCount(1, $respuesta->json('consultas'));
        $this->assertNotEmpty($respuesta->json('bitacora'));
        $this->assertSame(['etiqueta' => 'Nueva', 'total' => 1], $respuesta->json('negocio.consultasPorEstado.0'));
        $this->assertContains(['etiqueta' => 'Trabaja en relación de dependencia', 'total' => 1], $respuesta->json('negocio.graduadosPorSituacion'));
    }

    public function test_sin_permiso_de_bitacora_las_metricas_de_acceso_son_nulas(): void
    {
        $this->comoUsuario('noticias@carrera.test');

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('sesiones', null)
            ->assertJsonPath('recursosMasAccedidos', null)
            ->assertJsonPath('ipsFrecuentes', null)
            ->assertJsonPath('bitacora', [])
            ->assertJsonPath('consultas', [])
            ->assertJsonCount(14, 'visitasPorDia');

        // La Secretaría Académica ve las consultas (consultas.ver), pero tampoco la bitácora.
        $this->comoUsuario('secretaria@carrera.test');

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('sesiones', null)
            ->assertJsonIsArray('consultas');
    }

    public function test_reportes_del_periodo_y_exportacion_csv(): void
    {
        Visita::query()->create(['ruta' => '/biblioteca', 'ip' => '10.0.0.1']);

        $this->comoUsuario('noticias@carrera.test');

        $this->getJson('/api/admin/reportes?desde=2026-10-01&hasta=2026-11-02')
            ->assertOk()
            ->assertJsonPath('filtros', ['desde' => '2026-10-01', 'hasta' => '2026-11-02'])
            ->assertJsonCount(33, 'visitasPorDia')
            ->assertJsonStructure([
                'filtros', 'resumen' => ['visitas_periodo', 'visitas_historicas', 'paginas_registradas', 'consultas_periodo'],
                'visitasPorDia', 'paginasPeriodo', 'paginasHistorico', 'consultasPorEstado', 'consultasPorCategoria',
                'graduadosPorSituacion', 'graduadosPorAnio', 'documentosMasDescargados', 'contenido',
                'innova' => ['solicitudes', 'postulaciones'], 'actividadUsuarios',
            ]);

        // Período por defecto: últimos 30 días, con la visita de hoy.
        $this->getJson('/api/admin/reportes')
            ->assertOk()
            ->assertJsonCount(30, 'visitasPorDia')
            ->assertJsonPath('resumen.visitas_periodo', 1)
            ->assertJsonPath('resumen.visitas_historicas', 1)
            ->assertJsonPath('paginasPeriodo.0', ['ruta' => '/biblioteca', 'total' => 1]);

        $this->getJson('/api/admin/reportes?desde=no-es-fecha')->assertStatus(422)->assertJsonValidationErrors('desde');

        $csv = $this->get('/api/admin/reportes/visitas.csv');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('Content-Type'));
        $contenido = $csv->streamedContent();
        $this->assertStringContainsString('Página;Visitas', $contenido);
        $this->assertStringContainsString('/biblioteca;1', $contenido);
        $this->assertTrue(Bitacora::query()->where('accion', 'exportar')->where('modulo', 'Reportes')->exists());
    }

    public function test_reportes_requieren_el_permiso_reportes_ver(): void
    {
        $this->comoUsuario('secretaria@carrera.test');

        $this->getJson('/api/admin/reportes')->assertForbidden();
        $this->get('/api/admin/reportes/visitas.csv')->assertForbidden();
    }
}

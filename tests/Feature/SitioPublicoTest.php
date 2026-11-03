<?php

namespace Tests\Feature;

use App\Mail\NuevaConsultaMail;
use App\Models\CategoriaConsulta;
use App\Models\Consulta;
use App\Models\ContadorVisita;
use App\Models\Documento;
use App\Models\Graduado;
use App\Models\InnovaSolicitud;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SitioPublicoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_paginas_publicas_responden(): void
    {
        foreach (['/', '/institucional/historia', '/plantel-docente', '/malla-curricular', '/calendario-academico', '/preguntas-frecuentes',
            '/biblioteca', '/investigacion', '/revista-innovar', '/revista-innovar/1', '/vinculacion', '/innova', '/graduados',
            '/noticias', '/eventos', '/galeria', '/contacto', '/buscar?q=gestion'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_contador_de_visitas_por_pagina(): void
    {
        $this->get('/noticias')->assertOk();
        $antes = ContadorVisita::query()->where('ruta', '/noticias')->value('total');

        $this->get('/noticias')->assertInertia(fn (Assert $page) => $page->where('visitas', $antes + 1));

        // Las precargas de Inertia no cuentan como visitas.
        $this->get('/noticias', ['Purpose' => 'prefetch']);
        $this->assertSame($antes + 1, ContadorVisita::query()->where('ruta', '/noticias')->value('total'));
    }

    public function test_malla_incluye_prerrequisitos_y_materias_posteriores(): void
    {
        $this->get('/malla-curricular')->assertInertia(fn (Assert $page) => $page
            ->component('sitio/Malla')
            ->has('materias', 54)
            ->where('materias', fn ($materias) => count(collect($materias)->firstWhere('sigla', 'ADM-200')['prerequisitos']) === 1));
    }

    public function test_docente_sin_autorizacion_no_publica_su_correo(): void
    {
        $this->get('/plantel-docente')->assertInertia(fn (Assert $page) => $page
            ->where('docentes', fn ($docentes) => collect($docentes)->contains(fn ($d) => $d['correo'] === null)));
    }

    public function test_formulario_de_contacto_valida_y_guarda_la_consulta(): void
    {
        Mail::fake();
        $this->get('/contacto');
        $respuesta = session('antispam.contacto');

        $datos = [
            'nombre' => 'Visitante de prueba',
            'correo' => 'visitante@ejemplo.test',
            'categoria_consulta_id' => CategoriaConsulta::query()->value('id'),
            'mensaje' => 'Quisiera información sobre la admisión.',
            'antispam_token' => Crypt::encryptString((string) (time() - 10)),
            'antispam_respuesta' => $respuesta,
            'sitio_web' => '',
        ];

        $this->post('/contacto', $datos)->assertRedirect('/contacto');
        $this->assertDatabaseHas('consultas', ['correo' => 'visitante@ejemplo.test', 'estado' => 'nueva']);
        Mail::assertSent(NuevaConsultaMail::class);
    }

    public function test_formulario_de_contacto_rechaza_bots(): void
    {
        $total = Consulta::query()->count();
        $categoria = CategoriaConsulta::query()->value('id');

        // Campo trampa lleno.
        $this->post('/contacto', ['nombre' => 'Bot', 'correo' => 'bot@x.test', 'categoria_consulta_id' => $categoria, 'mensaje' => 'Mensaje automatizado', 'sitio_web' => 'http://spam', 'antispam_token' => Crypt::encryptString((string) (time() - 10))])
            ->assertSessionHasErrors('antispam');

        // Enviado demasiado rápido.
        $this->get('/contacto');
        $this->post('/contacto', ['nombre' => 'Bot', 'correo' => 'bot@x.test', 'categoria_consulta_id' => $categoria, 'mensaje' => 'Mensaje automatizado', 'antispam_token' => Crypt::encryptString((string) time()), 'antispam_respuesta' => session('antispam.contacto')])
            ->assertSessionHasErrors('antispam');

        // Datos inválidos.
        $this->post('/contacto', ['nombre' => '', 'correo' => 'no-es-correo', 'mensaje' => 'x'])->assertSessionHasErrors(['nombre', 'correo', 'mensaje', 'categoria_consulta_id']);

        $this->assertSame($total, Consulta::query()->count());
    }

    public function test_solicitud_de_servicio_innova(): void
    {
        $this->post('/innova/solicitudes', [
            'empresa' => 'Empresa Test', 'contacto' => 'Ana', 'correo' => 'ana@test.test', 'mensaje' => 'Necesitamos un plan de negocios.',
            'antispam_token' => Crypt::encryptString((string) (time() - 10)),
        ])->assertRedirect();

        $this->assertTrue(InnovaSolicitud::query()->where('empresa', 'Empresa Test')->exists());
    }

    public function test_graduado_se_registra_y_actualiza_sus_datos(): void
    {
        $this->post('/graduados', [
            'nombre' => 'Nuevo Graduado', 'ci' => '9999999', 'anio_graduacion' => 2024, 'situacion_laboral' => 'dependiente',
            'email' => 'nuevo@graduado.test', 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            'antispam_token' => Crypt::encryptString((string) (time() - 10)),
        ])->assertRedirect('/graduados/mi-ficha');

        $this->assertAuthenticated();
        $usuario = User::query()->where('email', 'nuevo@graduado.test')->first();
        $this->assertSame('Graduado', $usuario->rol->nombre);

        $this->put('/graduados/mi-ficha', [
            'nombre' => 'Nuevo Graduado', 'ci' => '9999999', 'anio_graduacion' => 2024, 'situacion_laboral' => 'independiente',
            'empresa' => 'Mi emprendimiento', 'correo' => 'nuevo@graduado.test',
        ])->assertRedirect();

        $this->assertSame('independiente', Graduado::query()->where('user_id', $usuario->id)->value('situacion_laboral'));

        // Un graduado no puede entrar al panel.
        $this->get('/admin')->assertRedirect('/graduados/mi-ficha');
    }

    public function test_descarga_de_documento_suma_contador(): void
    {
        $documento = Documento::query()->where('publicado', true)->firstOrFail();

        $this->get("/biblioteca/{$documento->id}/descargar")->assertOk();
        $this->assertSame($documento->descargas + 1, $documento->fresh()->descargas);
    }
}

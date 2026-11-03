<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Materia;
use App\Models\Noticia;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PanelAdministracionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
    }

    private function usuario(string $correo): User
    {
        return User::query()->where('email', $correo)->firstOrFail();
    }

    public function test_inicio_de_sesion_queda_en_bitacora(): void
    {
        $this->post('/login', ['email' => 'admin@carrera.test', 'password' => 'password'])->assertRedirect('/admin');
        $this->assertDatabaseHas('bitacora', ['accion' => 'inicio_sesion', 'user_id' => $this->usuario('admin@carrera.test')->id]);
    }

    public function test_cuenta_deshabilitada_no_puede_ingresar(): void
    {
        $this->usuario('noticias@carrera.test')->update(['activo' => false]);
        $this->post('/login', ['email' => 'noticias@carrera.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_menu_dinamico_segun_permisos(): void
    {
        $this->actingAs($this->usuario('noticias@carrera.test'))
            ->get('/admin')
            ->assertInertia(fn (Assert $page) => $page
                ->where('menuAdmin', fn ($menu) => collect($menu)->pluck('titulo')->contains('Difusión')
                    && ! collect($menu)->pluck('titulo')->contains('Usuarios y seguridad')));
    }

    public function test_permisos_restringen_modulos(): void
    {
        $this->actingAs($this->usuario('noticias@carrera.test'));
        $this->get('/admin/noticias')->assertOk();
        $this->get('/admin/usuarios')->assertForbidden();
        $this->get('/admin/graduados')->assertForbidden();
    }

    public function test_crud_de_noticias_con_imagen_y_bitacora(): void
    {
        $this->actingAs($admin = $this->usuario('noticias@carrera.test'));

        $this->post('/admin/noticias', [
            'titulo' => 'Noticia de prueba', 'fecha_publicacion' => '2026-11-01', 'estado' => 'borrador', 'destacado' => '1',
            'imagen' => UploadedFile::fake()->image('foto.jpg', 800, 450),
        ])->assertRedirect('/admin/noticias');

        $noticia = Noticia::query()->where('titulo', 'Noticia de prueba')->firstOrFail();
        $this->assertSame('noticia-de-prueba', $noticia->slug);
        $this->assertSame($admin->id, $noticia->user_id);
        Storage::disk('public')->assertExists($noticia->imagen);

        // Publicar (cambio de estado) queda como acción "publicar".
        $this->put("/admin/noticias/{$noticia->id}", ['titulo' => 'Noticia de prueba', 'fecha_publicacion' => '2026-11-01', 'estado' => 'publicado', 'destacado' => '0'])->assertRedirect();
        $this->assertDatabaseHas('bitacora', ['accion' => 'publicar', 'modulo' => 'Noticias', 'registro_id' => $noticia->id]);
        Storage::disk('public')->assertExists($noticia->fresh()->imagen);

        $this->delete("/admin/noticias/{$noticia->id}")->assertRedirect();
        $this->assertModelMissing($noticia);
        $this->assertDatabaseHas('bitacora', ['accion' => 'eliminar', 'registro_id' => $noticia->id]);
        Storage::disk('public')->assertMissing($noticia->imagen);
    }

    public function test_validacion_del_formulario_generico(): void
    {
        $this->actingAs($this->usuario('admin@carrera.test'))
            ->post('/admin/eventos', ['titulo' => '', 'tipo' => 'inexistente'])
            ->assertSessionHasErrors(['titulo', 'tipo', 'fecha']);
    }

    public function test_materia_con_prerrequisitos(): void
    {
        $prerequisitos = Materia::query()->orderBy('id')->limit(2)->pluck('id')->all();

        $this->actingAs($this->usuario('secretaria@carrera.test'))
            ->post('/admin/materias', ['sigla' => 'TST-999', 'nombre' => 'Materia de prueba', 'semestre' => 3, 'tipo' => 'electiva', 'creditos' => 3, 'horas_teoricas' => 2, 'horas_practicas' => 1, 'prerequisitos' => $prerequisitos])
            ->assertRedirect('/admin/materias');

        $this->assertCount(2, Materia::query()->where('sigla', 'TST-999')->first()->prerequisitos);
    }

    public function test_roles_y_usuarios(): void
    {
        $this->actingAs($this->usuario('admin@carrera.test'));

        $rolNoticias = Rol::query()->where('nombre', 'Responsable de Noticias')->value('id');

        $this->post('/admin/usuarios', ['name' => 'Editor', 'email' => 'editor@carrera.test', 'rol_id' => $rolNoticias, 'password' => 'clave12345', 'activo' => '1'])->assertRedirect('/admin/usuarios');
        $editor = $this->usuario('editor@carrera.test');
        $this->assertTrue($editor->tienePermiso('noticias.gestionar'));

        $this->put("/admin/usuarios/{$editor->id}", ['name' => 'Editor', 'email' => 'editor@carrera.test', 'rol_id' => $rolNoticias, 'password' => '', 'activo' => '0'])->assertRedirect();
        $this->assertFalse($editor->fresh()->activo);

        $administrador = Rol::query()->where('nombre', 'Administrador')->value('id');
        $this->delete("/admin/roles/{$administrador}")->assertRedirect();
        $this->assertDatabaseHas('roles', ['id' => $administrador, 'nombre' => 'Administrador']);
    }

    public function test_exportaciones_y_reportes(): void
    {
        $this->actingAs($this->usuario('admin@carrera.test'));

        $csv = $this->get('/admin/graduados/exportar?situacion_laboral=dependiente');
        $csv->assertOk();
        $this->assertStringContainsString('Situación laboral', $csv->streamedContent());

        $this->get('/admin/reportes?desde=2026-10-01&hasta=2026-11-02')->assertInertia(fn (Assert $page) => $page
            ->component('admin/Reportes')
            ->has('visitasPorDia', 33)
            ->has('graduadosPorSituacion'));

        $this->assertTrue(Bitacora::query()->where('accion', 'exportar')->exists());
    }

    public function test_bitacora_filtra_por_accion(): void
    {
        $this->actingAs($this->usuario('admin@carrera.test'));
        $this->post('/admin/categorias-noticia', ['nombre' => 'Prueba'])->assertRedirect();

        $this->get('/admin/bitacora?accion=crear')->assertInertia(fn (Assert $page) => $page
            ->where('registros.data', fn ($filas) => collect($filas)->every(fn ($f) => $f['accion'] === 'crear') && count($filas) > 0));
    }
}

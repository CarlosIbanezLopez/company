<?php

namespace Tests\Feature\Api;

use App\Models\MenuItem;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\SeguridadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Matriz de control de acceso (RBAC): menú dinámico, permisos por recurso y acción,
 * acceso al panel, roles, usuarios y opciones del menú.
 */
class RbacApiTest extends TestCase
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

    public function test_menu_del_panel_filtrado_por_los_permisos_del_rol(): void
    {
        $this->comoUsuario('noticias@carrera.test');

        $menu = collect($this->getJson('/api/menu')->assertOk()->assertJsonStructure(['menu' => [['titulo', 'icono', 'url', 'items']]])->json('menu'));
        $titulos = $menu->pluck('titulo');

        $this->assertTrue($titulos->contains('Inicio'));
        $this->assertTrue($titulos->contains('Difusión'));
        $this->assertTrue($titulos->contains('Reportes y estadísticas'));
        $this->assertFalse($titulos->contains('Usuarios y seguridad'));
        $this->assertFalse($titulos->contains('Vinculación'));

        $difusion = $menu->firstWhere('titulo', 'Difusión');
        $this->assertNull($difusion['url']);
        $this->assertContains(['titulo' => 'Noticias', 'icono' => 'newspaper', 'url' => '/admin/noticias'], $difusion['items']);
        $this->assertSame('/admin', $menu->firstWhere('titulo', 'Inicio')['url']);

        // Solo rutas de la SPA (no rutas de Laravel).
        $menu->pluck('items')->flatten(1)->each(fn ($item) => $this->assertStringStartsWith('/admin', $item['url']));

        // El Administrador ve todo el menú; el graduado (sin panel) no ve ninguno.
        $this->comoUsuario('admin@carrera.test');
        $titulosAdmin = collect($this->getJson('/api/menu')->json('menu'))->pluck('titulo');
        $this->assertTrue($titulosAdmin->contains('Usuarios y seguridad'));
        $this->assertCount(MenuItem::query()->whereNull('padre_id')->count(), $titulosAdmin);

        $this->comoUsuario('graduado@carrera.test');
        $this->getJson('/api/menu')->assertOk()->assertExactJson(['menu' => []]);
    }

    public function test_un_grupo_sin_opciones_permitidas_no_se_muestra(): void
    {
        $this->comoUsuario('revista@carrera.test');

        $menu = collect($this->getJson('/api/menu')->json('menu'));
        $investigacion = $menu->firstWhere('titulo', 'Investigación');

        $this->assertNotNull($investigacion);
        $this->assertFalse($menu->pluck('titulo')->contains('Consultas'));
        $this->assertFalse($menu->pluck('titulo')->contains('Reportes y estadísticas'));
    }

    public function test_permisos_restringen_los_recursos_del_panel(): void
    {
        $this->comoUsuario('noticias@carrera.test');

        $this->getJson('/api/admin/noticias')->assertOk()->assertJsonPath('config.recurso', 'noticias');
        $this->getJson('/api/admin/eventos')->assertOk();

        foreach (['/api/admin/usuarios', '/api/admin/graduados', '/api/admin/roles', '/api/admin/bitacora', '/api/admin/roles/matriz'] as $uri) {
            $this->getJson($uri)
                ->assertForbidden()
                ->assertExactJson(['message' => 'No tiene permiso para realizar esta acción.']);
        }

        $this->postJson('/api/admin/usuarios', [])->assertForbidden();
        $this->deleteJson('/api/admin/roles/1')->assertForbidden();
    }

    public function test_cada_accion_exige_su_propio_permiso(): void
    {
        // Encargado de la Revista: documentos ver/crear/editar, pero no eliminar.
        $this->comoUsuario('revista@carrera.test');

        $this->getJson('/api/admin/documentos')->assertOk();
        $this->getJson('/api/admin/documentos/formulario')->assertOk();
        $this->deleteJson('/api/admin/documentos/999')->assertForbidden();

        // Secretaría Académica: consultas ver/editar, pero no eliminar.
        $this->comoUsuario('secretaria@carrera.test');

        $this->getJson('/api/admin/consultas')->assertOk();
        $this->deleteJson('/api/admin/consultas/999')->assertForbidden();
        $this->getJson('/api/admin/noticias')->assertForbidden();
    }

    public function test_los_permisos_del_listado_reflejan_la_matriz(): void
    {
        $this->comoUsuario('revista@carrera.test');

        $this->getJson('/api/admin/documentos')
            ->assertOk()
            ->assertJsonPath('config.permisos', ['crear' => true, 'editar' => true, 'eliminar' => false]);
    }

    public function test_rol_sin_acceso_al_panel_recibe_403(): void
    {
        $this->comoUsuario('graduado@carrera.test');

        $this->getJson('/api/admin/dashboard')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Su rol no tiene acceso al panel de administración.']);

        $this->getJson('/api/admin/noticias')->assertForbidden();
    }

    public function test_sin_token_el_panel_responde_401(): void
    {
        $this->getJson('/api/admin/dashboard')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Su sesión no es válida o expiró. Inicie sesión nuevamente.');
    }

    public function test_cuenta_deshabilitada_en_el_panel_revoca_el_token(): void
    {
        $usuario = $this->usuario('noticias@carrera.test');
        $token = $usuario->createToken('spa')->plainTextToken;

        $this->withToken($token)->getJson('/api/admin/dashboard')->assertOk();

        $usuario->update(['activo' => false]);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/admin/dashboard')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Su cuenta está deshabilitada.']);

        $this->assertSame(0, $usuario->tokens()->count());

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/admin/dashboard')->assertUnauthorized();
    }

    public function test_matriz_de_acceso_de_solo_lectura(): void
    {
        $this->comoUsuario('admin@carrera.test');

        $respuesta = $this->getJson('/api/admin/roles/matriz')
            ->assertOk()
            ->assertJsonPath('acciones', ['ver', 'crear', 'editar', 'eliminar'])
            ->assertJsonStructure([
                'acciones',
                'roles' => [['id', 'nombre', 'acceso_panel', 'es_admin']],
                'filas' => [['recurso', 'etiqueta', 'modulo', 'permisos' => ['ver', 'crear', 'editar', 'eliminar']]],
            ]);

        $roles = collect($respuesta->json('roles'))->keyBy('nombre');
        $filas = collect($respuesta->json('filas'))->keyBy('recurso');

        $this->assertTrue($roles['Administrador']['es_admin']);
        $this->assertFalse($roles['Graduado']['acceso_panel']);
        $this->assertCount(count(Permiso::RECURSOS), $filas);
        $this->assertSame('Noticias y categorías', $filas['noticias']['etiqueta']);
        $this->assertSame('Difusión', $filas['noticias']['modulo']);

        $noticias = $roles['Responsable de Noticias']['id'];
        $revista = $roles['Encargado Revista INNOVAR']['id'];
        $admin = $roles['Administrador']['id'];

        $this->assertContains($noticias, $filas['noticias']['permisos']['eliminar']['roles']);
        $this->assertContains($admin, $filas['usuarios']['permisos']['ver']['roles']);
        $this->assertNotContains($noticias, $filas['usuarios']['permisos']['ver']['roles']);
        $this->assertContains($revista, $filas['documentos']['permisos']['editar']['roles']);
        $this->assertNotContains($revista, $filas['documentos']['permisos']['eliminar']['roles']);

        // Recursos de solo lectura: sin celdas para crear/editar/eliminar.
        $this->assertNull($filas['bitacora']['permisos']['crear']);
        $this->assertNull($filas['reportes']['permisos']['eliminar']);
        $this->assertSame(Permiso::query()->where('nombre', 'reportes.ver')->value('id'), $filas['reportes']['permisos']['ver']['id']);
    }

    public function test_formulario_de_rol_con_campo_matriz_y_asignacion_de_permisos(): void
    {
        $this->comoUsuario('admin@carrera.test');

        $campos = collect($this->getJson('/api/admin/roles/formulario')->assertOk()->json('campos'))->keyBy('nombre');
        $matriz = $campos['permisos'];

        $this->assertSame('matriz', $matriz['tipo']);
        $this->assertSame('Matriz de permisos', $matriz['etiqueta']);
        $this->assertSame(['ver', 'crear', 'editar', 'eliminar'], $matriz['acciones']);
        $fila = collect($matriz['filas'])->firstWhere('recurso', 'eventos');
        $this->assertSame(['recurso', 'etiqueta', 'modulo', 'permisos'], array_keys($fila));
        $this->assertSame(Permiso::query()->where('nombre', 'eventos.crear')->value('id'), $fila['permisos']['crear']);

        $ids = Permiso::query()->whereIn('nombre', ['eventos.ver', 'eventos.crear'])->pluck('id')->all();

        $id = $this->postJson('/api/admin/roles', ['nombre' => 'Editor de eventos', 'descripcion' => 'Prueba', 'acceso_panel' => true, 'permisos' => $ids])
            ->assertCreated()
            ->assertJsonPath('message', 'Rol registrado correctamente.')
            ->json('registro.id');

        $rol = Rol::query()->findOrFail($id);
        $this->assertEqualsCanonicalizing($ids, $rol->permisos()->pluck('permisos.id')->all());
        $this->assertDatabaseHas('bitacora', ['accion' => 'editar', 'modulo' => 'Usuarios', 'registro_id' => $id]);

        // La edición muestra los ids asignados; una lista ausente se trata como vacía.
        $this->assertEqualsCanonicalizing($ids, $this->getJson("/api/admin/roles/{$id}")->assertOk()->json('valores.permisos'));
        $this->putJson("/api/admin/roles/{$id}", ['nombre' => 'Editor de eventos', 'acceso_panel' => true])->assertOk();
        $this->assertSame(0, $rol->permisos()->count());

        $this->deleteJson("/api/admin/roles/{$id}")->assertOk();
        $this->assertModelMissing($rol);
    }

    public function test_roles_del_sistema_y_roles_con_usuarios_no_se_eliminan(): void
    {
        $this->comoUsuario('admin@carrera.test');

        $administrador = Rol::query()->where('nombre', 'Administrador')->firstOrFail();
        $noticias = Rol::query()->where('nombre', 'Responsable de Noticias')->firstOrFail();

        $this->deleteJson("/api/admin/roles/{$administrador->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Este rol es parte del sistema y no se puede eliminar.');

        $this->deleteJson("/api/admin/roles/{$noticias->id}")->assertStatus(409);

        // El nombre de un rol del sistema no cambia (el sistema lo reconoce por su nombre).
        $this->putJson("/api/admin/roles/{$administrador->id}", ['nombre' => 'Jefe', 'acceso_panel' => false, 'permisos' => []])->assertOk();
        $this->assertDatabaseHas('roles', ['id' => $administrador->id, 'nombre' => 'Administrador', 'acceso_panel' => true]);
    }

    public function test_gestion_de_usuarios_con_ultimo_acceso_e_intentos(): void
    {
        $this->comoUsuario('admin@carrera.test');

        $columnas = collect($this->getJson('/api/admin/usuarios')->assertOk()->json('columnas'))->pluck('clave');
        $this->assertTrue($columnas->contains('ultimo_acceso'));
        $this->assertTrue($columnas->contains('intentos_fallidos'));

        $rol = Rol::query()->where('nombre', 'Responsable de Noticias')->value('id');

        $id = $this->postJson('/api/admin/usuarios', ['name' => 'Editor', 'email' => 'Editor@Carrera.test', 'rol_id' => $rol, 'password' => 'clave12345', 'activo' => true])
            ->assertCreated()
            ->assertJsonPath('message', 'Usuario registrado correctamente.')
            ->json('registro.id');

        $editor = User::query()->findOrFail($id);
        $this->assertSame('editor@carrera.test', $editor->email);
        $this->assertTrue($editor->tienePermiso('noticias.crear'));

        // Correo repetido (sin importar mayúsculas).
        $this->postJson('/api/admin/usuarios', ['name' => 'Otro', 'email' => 'EDITOR@carrera.test', 'rol_id' => $rol, 'password' => 'clave12345'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        // Al deshabilitar la cuenta se cierran sus sesiones.
        $editor->createToken('spa');
        $this->putJson("/api/admin/usuarios/{$id}", ['name' => 'Editor', 'email' => 'editor@carrera.test', 'rol_id' => $rol, 'password' => '', 'activo' => false])->assertOk();
        $this->assertFalse($editor->fresh()->activo);
        $this->assertSame(0, $editor->tokens()->count());

        $admin = $this->usuario('admin@carrera.test');
        $this->deleteJson("/api/admin/usuarios/{$admin->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'No puede eliminar su propia cuenta.');

        $this->deleteJson("/api/admin/usuarios/{$id}")->assertOk()->assertJsonPath('message', 'Usuario eliminado.');
        $this->assertModelMissing($editor);
    }

    public function test_quien_no_es_administrador_no_puede_escalar_privilegios(): void
    {
        $jefe = Rol::query()->create(['nombre' => 'Jefe de seguridad', 'acceso_panel' => true]);
        $jefe->permisos()->sync(Permiso::query()->where('recurso', 'usuarios')->orWhere('recurso', 'roles')->pluck('id'));
        $usuario = User::query()->create(['name' => 'Jefe', 'email' => 'jefe@carrera.test', 'password' => 'password', 'rol_id' => $jefe->id, 'activo' => true]);

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($usuario);

        $administrador = Rol::query()->where('nombre', 'Administrador')->value('id');
        $admin = $this->usuario('admin@carrera.test');
        $noticias = Rol::query()->where('nombre', 'Responsable de Noticias')->firstOrFail();

        $opciones = collect($this->getJson('/api/admin/usuarios/formulario')->assertOk()->json('campos'))->firstWhere('nombre', 'rol_id')['opciones'];
        $this->assertNotContains('Administrador', array_column($opciones, 'label'));

        $this->postJson('/api/admin/usuarios', ['name' => 'Intruso', 'email' => 'intruso@carrera.test', 'rol_id' => $administrador, 'password' => 'clave12345'])
            ->assertStatus(422)
            ->assertJsonPath('errors.rol_id.0', 'Solo un Administrador puede asignar el rol Administrador.');

        $this->putJson("/api/admin/usuarios/{$admin->id}", ['name' => 'Admin', 'email' => 'admin@carrera.test', 'rol_id' => $noticias->id])
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo un Administrador puede modificar o eliminar la cuenta de un Administrador.');
        $this->deleteJson("/api/admin/usuarios/{$admin->id}")->assertForbidden();

        $this->putJson("/api/admin/roles/{$jefe->id}", ['nombre' => 'Jefe de seguridad', 'permisos' => Permiso::query()->pluck('id')->all()])
            ->assertForbidden()
            ->assertJsonPath('message', 'No puede modificar los permisos de su propio rol.');
        $this->putJson("/api/admin/roles/{$administrador}", ['nombre' => 'Administrador'])->assertForbidden();

        $this->putJson("/api/admin/roles/{$noticias->id}", ['nombre' => 'Responsable de Noticias', 'acceso_panel' => true, 'permisos' => $noticias->permisos()->pluck('permisos.id')->all()])
            ->assertOk();
        $this->assertTrue($admin->fresh()->esAdministrador());
    }

    public function test_opciones_del_menu_con_ruta_de_la_spa_y_permiso_existente(): void
    {
        $this->comoUsuario('admin@carrera.test');

        $campos = collect($this->getJson('/api/admin/menu/formulario')->assertOk()->json('campos'))->keyBy('nombre');
        $this->assertSame('select', $campos['permiso']['tipo']);
        $this->assertContains('noticias.ver', array_column($campos['permiso']['opciones'], 'value'));

        $grupo = MenuItem::query()->where('etiqueta', 'Difusión')->value('id');

        $this->postJson('/api/admin/menu', ['etiqueta' => 'Prueba', 'ruta' => 'admin.noticias.index', 'permiso' => 'noticias.gestionar', 'padre_id' => $grupo])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ruta', 'permiso']);

        $this->postJson('/api/admin/menu', ['etiqueta' => 'Comunicados', 'ruta' => 'javascript:alert(1)', 'permiso' => 'noticias.ver', 'padre_id' => $grupo])
            ->assertStatus(422)
            ->assertJsonValidationErrors('ruta');

        // El grupo padre debe ser un grupo (no una opción).
        $opcion = MenuItem::query()->where('etiqueta', 'Noticias')->value('id');
        $this->postJson('/api/admin/menu', ['etiqueta' => 'Comunicados', 'ruta' => '/admin/comunicados', 'padre_id' => $opcion])
            ->assertStatus(422)
            ->assertJsonValidationErrors('padre_id');

        $this->postJson('/api/admin/menu', ['etiqueta' => 'Comunicados', 'ruta' => '/admin/comunicados', 'permiso' => 'noticias.ver', 'padre_id' => $grupo, 'orden' => 9, 'activo' => true])
            ->assertCreated();

        $this->comoUsuario('noticias@carrera.test');
        $difusion = collect($this->getJson('/api/menu')->json('menu'))->firstWhere('titulo', 'Difusión');
        $this->assertContains(['titulo' => 'Comunicados', 'icono' => null, 'url' => '/admin/comunicados'], $difusion['items']);

        $this->getJson('/api/admin/menu')->assertForbidden();
    }
}

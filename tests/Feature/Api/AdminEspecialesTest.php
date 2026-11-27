<?php

namespace Tests\Feature\Api;

use App\Models\Album;
use App\Models\Configuracion;
use App\Models\Graduado;
use App\Models\Permiso;
use App\Models\RevistaEdicion;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API del panel: endpoints especiales (exportar graduados, fotos de álbumes, configuración del portal)
 * y acciones extra de los listados.
 */
class AdminEspecialesTest extends TestCase
{
    use RefreshDatabase;

    private const MULTIPART = ['Accept' => 'application/json'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** Usuario con un rol de panel y los permisos "{recurso}.{accion}" indicados. */
    private function usuario(string $rol, array $permisos = []): User
    {
        $registro = Rol::query()->create(['nombre' => $rol, 'descripcion' => $rol, 'acceso_panel' => true]);
        $registro->permisos()->sync(array_map(function (string $nombre) {
            [$recurso, $accion] = explode('.', $nombre);

            return Permiso::query()->firstOrCreate(['nombre' => $nombre], ['recurso' => $recurso, 'accion' => $accion, 'modulo' => ucfirst($recurso), 'descripcion' => $nombre])->id;
        }, $permisos));

        return User::query()->create([
            'name' => $rol,
            'email' => Str::slug($rol).'@carrera.test',
            'password' => 'password',
            'rol_id' => $registro->id,
            'activo' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function graduado(string $nombre, string $situacion, int $anio): Graduado
    {
        return Graduado::query()->create([
            'nombre' => $nombre, 'ci' => (string) random_int(1000000, 9999999), 'anio_graduacion' => $anio,
            'situacion_laboral' => $situacion, 'modalidad_titulacion' => 'tesis', 'correo' => Str::slug($nombre).'@correo.test',
        ]);
    }

    public function test_exportar_graduados_a_csv_con_los_filtros_del_listado(): void
    {
        Sanctum::actingAs($this->usuario('Administrador'));

        $this->graduado('María López', 'dependiente', 2020);
        $this->graduado('Pedro Gómez', 'busca_empleo', 2021);
        $this->graduado('Lucía Vargas', 'dependiente', 2022);

        $this->getJson('/api/admin/graduados')
            ->assertOk()
            ->assertJsonPath('config.acciones', [['etiqueta' => 'Exportar CSV', 'url' => 'admin/graduados/exportar', 'icono' => 'download', 'tipo' => 'descarga']])
            ->assertJsonPath('registros.total', 3);

        $respuesta = $this->get('/api/admin/graduados/exportar?situacion_laboral=dependiente&q=lucía');
        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', (string) $respuesta->headers->get('Content-Type'));
        $this->assertMatchesRegularExpression('/attachment; filename=graduados_\d{8}_\d{6}\.csv/', (string) $respuesta->headers->get('Content-Disposition'));

        $csv = $respuesta->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $lineas = array_values(array_filter(explode("\n", substr($csv, 3))));
        $this->assertCount(2, $lineas);
        $this->assertStringStartsWith('Nombre;CI;Registro;"Año de graduación";Modalidad;"Situación laboral"', $lineas[0]);
        $this->assertStringContainsString('"Lucía Vargas"', $lineas[1]);
        $this->assertStringContainsString('"Trabaja en relación de dependencia"', $lineas[1]);
        $this->assertStringContainsString('"Tesis de grado"', $lineas[1]);

        $this->assertDatabaseHas('bitacora', ['accion' => 'exportar', 'modulo' => 'Graduados']);

        // Sin filtros exporta todo; sin el permiso graduados.ver no se puede exportar.
        $this->assertCount(4, array_filter(explode("\n", $this->get('/api/admin/graduados/exportar')->streamedContent())));

        Sanctum::actingAs($this->usuario('Responsable de Noticias', ['noticias.ver']));
        $this->getJson('/api/admin/graduados/exportar')->assertForbidden();
    }

    public function test_fotos_del_album_subir_listar_y_eliminar(): void
    {
        Sanctum::actingAs($this->usuario('Administrador'));

        $album = Album::query()->create(['titulo' => 'Feria empresarial 2026', 'slug' => 'feria-empresarial-2026']);

        $this->getJson('/api/admin/albumes')
            ->assertOk()
            ->assertJsonPath('config.accionesFila', [['etiqueta' => 'Fotos', 'url' => '/admin/albumes/__ID__/fotos', 'icono' => 'images', 'tipo' => 'enlace']])
            ->assertJsonPath('registros.data.0.fotos_count', 0);

        $respuesta = $this->getJson("/api/admin/albumes/{$album->id}/fotos")
            ->assertOk()
            ->assertJsonPath('album.id', $album->id)
            ->assertJsonPath('album.titulo', 'Feria empresarial 2026')
            ->assertJsonPath('album.fotos', [])
            ->assertJsonPath('urls', ['subir' => "admin/albumes/{$album->id}/fotos", 'eliminar' => "admin/albumes/{$album->id}/fotos/__FOTO__", 'volver' => '/admin/albumes'])
            ->assertJsonPath('permisos', ['subir' => true, 'eliminar' => true])
            ->assertJsonPath('reglas.fotos', ['requerido' => true, 'max' => 20]);
        $this->assertSame(['requerido' => false, 'imagen' => true, 'mimes' => ['jpg', 'jpeg', 'png', 'webp'], 'max' => 5120], $respuesta->json('reglas')['fotos.*']);

        // Validación en español.
        $this->withHeaders(self::MULTIPART)->post("/api/admin/albumes/{$album->id}/fotos", [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.fotos', ['El campo Fotografías es obligatorio.']);
        $errores = $this->withHeaders(self::MULTIPART)->post("/api/admin/albumes/{$album->id}/fotos", ['fotos' => [UploadedFile::fake()->create('nota.pdf', 5, 'application/pdf')]])
            ->assertUnprocessable()
            ->json('errors');
        $this->assertContains('El campo Fotografía debe ser una imagen.', $errores['fotos.0']);

        // Subir dos fotografías: la primera pasa a ser la portada.
        $this->withHeaders(self::MULTIPART)->post("/api/admin/albumes/{$album->id}/fotos", [
            'fotos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
            'descripcion' => 'Inauguración',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Fotografías agregadas al álbum.')
            ->assertJsonCount(2, 'album.fotos')
            ->assertJsonPath('album.fotos.0.descripcion', 'Inauguración')
            ->assertJsonPath('urls.volver', '/admin/albumes');

        $fotos = $album->fotos()->orderBy('orden')->get();
        $this->assertCount(2, $fotos);
        foreach ($fotos as $foto) {
            $this->assertStringStartsWith("galeria/{$album->id}/", $foto->imagen);
            Storage::disk('public')->assertExists($foto->imagen);
        }
        $this->assertSame($fotos[0]->imagen, $album->fresh()->portada);
        $this->assertNotNull($this->getJson("/api/admin/albumes/{$album->id}/fotos")->json('album.fotos.1.imagen_url'));

        // Una fotografía de otro álbum no se encuentra.
        $otro = Album::query()->create(['titulo' => 'Otro', 'slug' => 'otro']);
        $this->deleteJson("/api/admin/albumes/{$otro->id}/fotos/{$fotos[0]->id}")->assertNotFound();

        // Eliminar la fotografía de portada: se borra el archivo y la portada pasa a la siguiente.
        $this->deleteJson("/api/admin/albumes/{$album->id}/fotos/{$fotos[0]->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Fotografía eliminada.');
        Storage::disk('public')->assertMissing($fotos[0]->imagen);
        $this->assertSame($fotos[1]->imagen, $album->fresh()->portada);

        // Reemplazar la portada desde el formulario no borra la fotografía que era portada.
        $this->withHeaders(self::MULTIPART)->post("/api/admin/albumes/{$album->id}", [
            '_method' => 'PUT', 'titulo' => 'Feria empresarial 2026', 'publicado' => '1', 'portada' => UploadedFile::fake()->image('portada.jpg'),
        ])->assertOk()->assertJsonPath('message', 'Álbum actualizado correctamente.');
        Storage::disk('public')->assertExists($fotos[1]->imagen);

        // Eliminar el álbum borra también los archivos de sus fotografías.
        $this->deleteJson("/api/admin/albumes/{$album->id}")->assertOk()->assertJsonPath('message', 'Álbum eliminado.');
        Storage::disk('public')->assertMissing($fotos[1]->imagen);
        $this->assertDatabaseMissing('fotos', ['album_id' => $album->id]);
    }

    public function test_permisos_de_las_fotos_segun_el_rol(): void
    {
        $album = Album::query()->create(['titulo' => 'Aniversario', 'slug' => 'aniversario']);

        Sanctum::actingAs($this->usuario('Lector de galería', ['galeria.ver']));

        $this->getJson("/api/admin/albumes/{$album->id}/fotos")->assertOk()->assertJsonPath('permisos', ['subir' => false, 'eliminar' => false]);
        $this->withHeaders(self::MULTIPART)->post("/api/admin/albumes/{$album->id}/fotos", ['fotos' => [UploadedFile::fake()->image('a.jpg')]])->assertForbidden();
    }

    public function test_configuracion_del_portal_get_y_put_con_formato_de_formulario(): void
    {
        Sanctum::actingAs($this->usuario('Administrador'));
        Configuracion::guardar('nombre_carrera', 'Administración de Empresas', 'portal');

        $respuesta = $this->getJson('/api/admin/configuracion/portal')
            ->assertOk()
            ->assertJsonPath('config.uri', 'configuracion')
            ->assertJsonPath('config.recurso', 'portal')
            ->assertJsonPath('config.titulo', 'Página principal')
            ->assertJsonPath('config.permisos', ['crear' => false, 'editar' => true, 'eliminar' => false])
            ->assertJsonPath('registroId', 'portal')
            ->assertJsonPath('valores.nombre_carrera', 'Administración de Empresas')
            ->assertJsonPath('valores.nombre_facultad', '');

        $campos = collect($respuesta->json('campos'))->keyBy('nombre');
        $this->assertSame(['requerido' => true, 'max' => 255], $campos['nombre_carrera']['reglas']);
        $this->assertTrue($campos['nombre_carrera']['requerido']);
        $this->assertSame(['requerido' => false, 'url' => true, 'max' => 500], $campos['video_presentacion']['reglas']);
        $this->assertSame('markdown', $campos['presentacion_texto']['tipo']);

        $this->putJson('/api/admin/configuracion/portal', ['nombre_carrera' => '', 'video_presentacion' => 'no-es-una-url'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nombre_carrera', ['El campo Nombre de la carrera es obligatorio.'])
            ->assertJsonPath('errors.video_presentacion', ['El campo Video de presentación debe ser una URL válida.']);

        $this->putJson('/api/admin/configuracion/portal', ['nombre_carrera' => 'Carrera de Administración de Empresas', 'presentacion_titulo' => 'Bienvenidos'])
            ->assertOk()
            ->assertJsonPath('message', 'Configuración guardada.')
            ->assertJsonPath('valores.nombre_carrera', 'Carrera de Administración de Empresas')
            ->assertJsonPath('valores.presentacion_titulo', 'Bienvenidos');

        $this->assertSame('Bienvenidos', Configuracion::valor('presentacion_titulo'));
        $this->assertDatabaseHas('configuraciones', ['clave' => 'presentacion_titulo', 'grupo' => 'portal']);
        $this->getJson('/api/admin/configuracion/portal')->assertJsonPath('valores.presentacion_titulo', 'Bienvenidos');

        $this->putJson('/api/admin/configuracion/contacto', ['correo' => 'carrera@uagrm.edu.bo', 'facebook' => 'https://facebook.com/carrera'])->assertOk();
        $this->assertDatabaseHas('configuraciones', ['clave' => 'correo', 'valor' => 'carrera@uagrm.edu.bo', 'grupo' => 'contacto']);
        $this->getJson('/api/admin/configuracion/contacto')->assertOk()->assertJsonPath('registroId', 'contacto')->assertJsonPath('valores.correo', 'carrera@uagrm.edu.bo');

        $this->getJson('/api/admin/configuracion/otro')->assertNotFound();
    }

    public function test_configuracion_requiere_los_permisos_del_portal(): void
    {
        Sanctum::actingAs($this->usuario('Secretaría Académica', ['academico.ver']));
        $this->getJson('/api/admin/configuracion/portal')->assertForbidden();

        Sanctum::actingAs($this->usuario('Lector del portal', ['portal.ver']));
        $this->getJson('/api/admin/configuracion/portal')->assertOk()->assertJsonPath('config.permisos.editar', false);
        $this->putJson('/api/admin/configuracion/portal', ['nombre_carrera' => 'Otra'])->assertForbidden();
    }

    public function test_acciones_de_fila_de_las_ediciones_de_la_revista(): void
    {
        Sanctum::actingAs($this->usuario('Encargado Revista INNOVAR', ['revista.ver', 'revista.crear', 'revista.editar']));
        $edicion = RevistaEdicion::query()->create(['numero' => 7, 'titulo' => 'Innovación y empresa']);

        $this->getJson('/api/admin/ediciones')
            ->assertOk()
            ->assertJsonPath('config.permisos', ['crear' => true, 'editar' => true, 'eliminar' => false])
            ->assertJsonPath('config.accionesFila', [['etiqueta' => 'Artículos', 'url' => '/admin/articulos?revista_edicion_id=__ID__', 'icono' => 'file-text', 'tipo' => 'enlace']]);

        // El formulario de artículos se puede precargar con la edición (?revista_edicion_id=).
        $this->getJson("/api/admin/articulos/formulario?revista_edicion_id={$edicion->id}")
            ->assertOk()
            ->assertJsonPath('valores.revista_edicion_id', (string) $edicion->id)
            ->assertJsonPath('campos.0.opciones.0', ['value' => $edicion->id, 'label' => 'N° 7 - Innovación y empresa']);
    }
}

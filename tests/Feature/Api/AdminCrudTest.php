<?php

namespace Tests\Feature\Api;

use App\Models\CategoriaConsulta;
use App\Models\CategoriaDocumento;
use App\Models\CategoriaNoticia;
use App\Models\Consulta;
use App\Models\Documento;
use App\Models\Materia;
use App\Models\Noticia;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API del panel: CRUD genérico (listado, formulario, registrar, ver, actualizar, eliminar),
 * validación en español, reglas de cliente y permisos de la matriz de acceso.
 */
class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    /** Permisos del rol Secretaría Académica según la matriz de acceso. */
    private const PERMISOS_SECRETARIA = [
        'academico.ver', 'academico.crear', 'academico.editar', 'academico.eliminar',
        'docentes.ver', 'docentes.crear', 'docentes.editar', 'docentes.eliminar',
        'documentos.ver', 'documentos.crear', 'documentos.editar', 'documentos.eliminar',
        'consultas.ver', 'consultas.editar',
    ];

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

    private function admin(): User
    {
        return $this->usuario('Administrador');
    }

    public function test_listado_devuelve_config_columnas_filtros_consulta_y_registros_paginados(): void
    {
        Sanctum::actingAs($this->admin());

        $categoria = CategoriaNoticia::query()->create(['nombre' => 'Comunicados', 'slug' => 'comunicados']);
        foreach (range(1, 17) as $i) {
            Noticia::query()->create([
                'titulo' => "Noticia $i", 'slug' => "noticia-$i", 'fecha_publicacion' => sprintf('2026-11-%02d', $i),
                'estado' => 'publicado', 'categoria_noticia_id' => $categoria->id, 'destacado' => $i === 1,
            ]);
        }
        Noticia::query()->create(['titulo' => 'Borrador interno', 'slug' => 'borrador-interno', 'fecha_publicacion' => '2026-10-01', 'estado' => 'borrador']);

        $respuesta = $this->getJson('/api/admin/noticias?estado=publicado')
            ->assertOk()
            ->assertJsonPath('config.uri', 'noticias')
            ->assertJsonPath('config.recurso', 'noticias')
            ->assertJsonPath('config.titulo', 'Noticias y comunicados')
            ->assertJsonPath('config.singular', 'noticia')
            ->assertJsonPath('config.femenino', true)
            ->assertJsonPath('config.descripcion', null)
            ->assertJsonPath('config.permisos', ['crear' => true, 'editar' => true, 'eliminar' => true])
            ->assertJsonPath('config.acciones', [])
            ->assertJsonPath('config.accionesFila', [])
            ->assertJsonPath('buscar', true)
            ->assertJsonPath('consulta', ['q' => '', 'estado' => 'publicado', 'categoria_noticia_id' => '', 'destacado' => ''])
            ->assertJsonPath('registros.total', 17)
            ->assertJsonPath('registros.per_page', 15)
            ->assertJsonPath('registros.current_page', 1)
            ->assertJsonPath('registros.last_page', 2)
            ->assertJsonCount(15, 'registros.data')
            ->assertJsonPath('registros.data.0.titulo', 'Noticia 17')
            ->assertJsonPath('registros.data.0.categoria.nombre', 'Comunicados')
            ->assertJsonStructure([
                'columnas' => [['clave', 'etiqueta', 'tipo']],
                'filtros' => [['clave', 'etiqueta', 'tipo', 'opciones']],
                'registros' => ['data', 'current_page', 'last_page', 'per_page', 'total', 'from', 'to', 'links'],
            ]);

        $columnas = collect($respuesta->json('columnas'))->keyBy('clave');
        $this->assertSame('text', $columnas['titulo']['tipo']);
        $this->assertSame('image', $columnas['imagen_url']['tipo']);

        $filtros = collect($respuesta->json('filtros'))->keyBy('clave');
        $this->assertSame('select', $filtros['estado']['tipo']);
        $this->assertSame([['value' => (string) $categoria->id, 'label' => 'Comunicados']], $filtros['categoria_noticia_id']['opciones']);

        // La fila incluye solo los datos públicos del autor y la URL absoluta de la imagen.
        $this->assertArrayHasKey('imagen_url', $respuesta->json('registros.data.0'));

        // Segunda página, búsqueda, filtro booleano "1"/"0" y valores no ofrecidos por el filtro (se ignoran).
        $this->getJson('/api/admin/noticias?estado=publicado&page=2')->assertJsonCount(2, 'registros.data')->assertJsonPath('registros.current_page', 2);
        $this->getJson('/api/admin/noticias?q=BORRADOR')->assertJsonPath('registros.total', 1)->assertJsonPath('consulta.q', 'BORRADOR');
        $this->getJson('/api/admin/noticias?destacado=1')->assertJsonPath('registros.total', 1)->assertJsonPath('registros.data.0.titulo', 'Noticia 1');
        $this->getJson('/api/admin/noticias?destacado=0')->assertJsonPath('registros.total', 17);
        $this->getJson('/api/admin/noticias?categoria_noticia_id=abc')->assertJsonPath('registros.total', 18);

        // Recurso y URI distintos: categorías de noticias usan los permisos de "noticias".
        $this->getJson('/api/admin/categorias-noticia')->assertOk()
            ->assertJsonPath('config.uri', 'categorias-noticia')
            ->assertJsonPath('config.recurso', 'noticias')
            ->assertJsonPath('registros.data.0.noticias_count', 17);
    }

    public function test_formulario_de_registro_trae_campos_con_reglas_y_valores_por_defecto(): void
    {
        Sanctum::actingAs($this->admin());
        CategoriaNoticia::query()->create(['nombre' => 'Eventos', 'slug' => 'eventos']);

        $respuesta = $this->getJson('/api/admin/noticias/formulario')
            ->assertOk()
            ->assertJsonPath('registroId', null)
            ->assertJsonPath('config.uri', 'noticias')
            ->assertJsonPath('valores.estado', 'publicado')
            ->assertJsonPath('valores.destacado', false)
            ->assertJsonPath('valores.fecha_publicacion', now()->toDateString())
            ->assertJsonPath('valores.titulo', null);

        $campos = collect($respuesta->json('campos'))->keyBy('nombre');

        $this->assertTrue($campos->every(fn (array $campo) => isset($campo['reglas']['requerido']) && ! array_key_exists('defecto', $campo)));
        $this->assertSame(['requerido' => true, 'max' => 200], $campos['titulo']['reglas']);
        $this->assertTrue($campos['titulo']['requerido']);
        $this->assertSame(['requerido' => true, 'fecha' => true], $campos['fecha_publicacion']['reglas']);
        $this->assertSame(['requerido' => true, 'enLista' => ['borrador', 'publicado', 'archivado']], $campos['estado']['reglas']);
        $this->assertSame(['requerido' => false, 'imagen' => true, 'max' => 4096], $campos['imagen']['reglas']);
        $this->assertSame(['requerido' => false, 'max' => 300], $campos['resumen']['reglas']);
        $this->assertSame('select', $campos['categoria_noticia_id']['tipo']);
        $this->assertSame('Eventos', $campos['categoria_noticia_id']['opciones'][0]['label']);
        $this->assertSame('markdown', $campos['contenido']['tipo']);

        // Requerido derivado de la regla del servidor: el PDF de un documento es obligatorio al registrar.
        $documento = collect($this->getJson('/api/admin/documentos/formulario')->json('campos'))->keyBy('nombre');
        $this->assertTrue($documento['archivo']['requerido']);
        $this->assertSame(['requerido' => true, 'mimes' => ['pdf'], 'max' => 20480], $documento['archivo']['reglas']);
        $this->assertSame('application/pdf', $documento['archivo']['accept']);
    }

    public function test_crud_completo_de_noticia_con_imagen(): void
    {
        Sanctum::actingAs($admin = $this->admin());

        // Registrar (multipart con archivo).
        $creada = $this->withHeaders(self::MULTIPART)->post('/api/admin/noticias', [
            'titulo' => 'Noticia de prueba', 'fecha_publicacion' => '2026-11-01', 'estado' => 'borrador', 'destacado' => '1',
            'imagen' => UploadedFile::fake()->image('foto.jpg', 800, 450),
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Noticia registrada correctamente.')
            ->assertJsonPath('registro.titulo', 'Noticia de prueba')
            ->assertJsonPath('registro.slug', 'noticia-de-prueba')
            ->assertJsonPath('registro.destacado', true)
            ->assertJsonPath('registro.autor', ['id' => $admin->id, 'name' => 'Administrador']);

        $noticia = Noticia::query()->findOrFail($creada->json('registro.id'));
        $this->assertSame($admin->id, $noticia->user_id);
        $this->assertStringStartsWith('noticias/', $noticia->imagen);
        Storage::disk('public')->assertExists($noticia->imagen);
        $this->assertStringEndsWith('/storage/'.$noticia->imagen, $creada->json('registro.imagen_url'));

        // Ver (valores de edición con la URL de la imagen).
        $this->getJson("/api/admin/noticias/{$noticia->id}")
            ->assertOk()
            ->assertJsonPath('registroId', $noticia->id)
            ->assertJsonPath('valores.titulo', 'Noticia de prueba')
            ->assertJsonPath('valores.fecha_publicacion', '2026-11-01')
            ->assertJsonPath('valores.destacado', true)
            ->assertJsonPath('valores.imagen_url', $creada->json('registro.imagen_url'))
            ->assertJsonPath('campos.0.nombre', 'titulo');

        // Actualizar con multipart (POST + _method=PUT): reemplaza la imagen y publica.
        $anterior = $noticia->imagen;
        $this->withHeaders(self::MULTIPART)->post("/api/admin/noticias/{$noticia->id}", [
            '_method' => 'PUT', 'titulo' => 'Noticia de prueba', 'fecha_publicacion' => '2026-11-01', 'estado' => 'publicado', 'destacado' => '0',
            'imagen' => UploadedFile::fake()->image('nueva.png', 400, 300),
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Noticia actualizada correctamente.')
            ->assertJsonPath('registro.estado', 'publicado')
            ->assertJsonPath('registro.destacado', false);

        $noticia->refresh();
        Storage::disk('public')->assertMissing($anterior);
        Storage::disk('public')->assertExists($noticia->imagen);
        $this->assertDatabaseHas('bitacora', ['accion' => 'publicar', 'modulo' => 'Noticias', 'registro_id' => $noticia->id]);

        // Actualizar con JSON reenviando los valores recibidos (la ruta de la imagen se ignora: se conserva).
        $valores = $this->getJson("/api/admin/noticias/{$noticia->id}")->json('valores');
        $this->putJson("/api/admin/noticias/{$noticia->id}", [...$valores, 'titulo' => 'Noticia editada'])
            ->assertOk()
            ->assertJsonPath('registro.slug', 'noticia-editada');
        $this->assertSame($noticia->imagen, $noticia->fresh()->imagen);
        Storage::disk('public')->assertExists($noticia->imagen);

        // Quitar la imagen.
        $this->putJson("/api/admin/noticias/{$noticia->id}", ['titulo' => 'Noticia editada', 'fecha_publicacion' => '2026-11-01', 'estado' => 'publicado', 'imagen_quitar' => true])
            ->assertOk()
            ->assertJsonPath('registro.imagen', null)
            ->assertJsonPath('registro.imagen_url', null);
        Storage::disk('public')->assertMissing($noticia->imagen);

        // Eliminar.
        $this->deleteJson("/api/admin/noticias/{$noticia->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Noticia eliminada.');
        $this->assertModelMissing($noticia);
        $this->getJson("/api/admin/noticias/{$noticia->id}")->assertNotFound()->assertJsonPath('message', 'El recurso solicitado no existe.');
    }

    public function test_validacion_responde_422_con_mensajes_en_espanol_y_la_etiqueta_del_campo(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/admin/noticias', ['estado' => 'otro'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El campo Título es obligatorio. (y 2 errores más)')
            ->assertJsonPath('errors.titulo', ['El campo Título es obligatorio.'])
            ->assertJsonPath('errors.fecha_publicacion', ['El campo Fecha es obligatorio.'])
            ->assertJsonPath('errors.estado', ['El valor seleccionado en Estado no es válido.']);

        // Comparación entre campos usa también la etiqueta del otro campo.
        $this->postJson('/api/admin/calendario', ['titulo' => 'Inscripciones', 'tipo' => 'inscripciones', 'fecha_inicio' => '2026-12-10', 'fecha_fin' => '2026-12-01', 'gestion' => '1-2027'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.fecha_fin', ['El campo Fecha de fin debe ser una fecha posterior o igual a Fecha de inicio.']);

        // La aclaración entre paréntesis de la etiqueta no aparece en el mensaje.
        $this->withHeaders(self::MULTIPART)->post('/api/admin/banners', ['titulo' => 'Banner', 'imagen' => UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonPath('errors.imagen', ['El campo Imagen debe ser una imagen.']);

        $this->postJson('/api/admin/eventos', ['titulo' => str_repeat('a', 201), 'tipo' => 'taller', 'fecha' => '2026-12-01', 'hora_inicio' => '25:99'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.titulo', ['El campo Título no debe tener más de 200 caracteres.'])
            ->assertJsonPath('errors.hora_inicio', ['El campo Hora de inicio debe tener el formato H:i.']);
    }

    public function test_materia_con_select_y_multiselect_de_prerrequisitos(): void
    {
        Sanctum::actingAs($this->usuario('Secretaría Académica', self::PERMISOS_SECRETARIA));

        $a = Materia::query()->create(['sigla' => 'ADM-100', 'nombre' => 'Administración I', 'semestre' => 1]);
        $b = Materia::query()->create(['sigla' => 'MAT-101', 'nombre' => 'Matemática I', 'semestre' => 1]);

        $campos = collect($this->getJson('/api/admin/materias/formulario')->assertOk()->json('campos'))->keyBy('nombre');
        $this->assertSame('multiselect', $campos['prerequisitos']['tipo']);
        $this->assertSame(['value' => $a->id, 'label' => 'ADM-100 - Administración I', 'grupo' => '1° semestre'], $campos['prerequisitos']['opciones'][0]);
        $this->assertSame(['requerido' => false], $campos['prerequisitos']['reglas']);
        $this->assertSame('select', $campos['semestre']['tipo']);
        $this->assertSame(['requerido' => true, 'numerico' => true, 'entero' => true, 'entre' => [1, 10]], $campos['semestre']['reglas']);
        $this->assertSame(['requerido' => true, 'max' => 20], $campos['sigla']['reglas']);
        $this->assertSame(['requerido' => false, 'numerico' => true, 'entero' => true, 'min' => 0, 'max' => 30], $campos['creditos']['reglas']);

        $datos = ['sigla' => 'ADM-200', 'nombre' => 'Administración II', 'semestre' => 2, 'tipo' => 'obligatoria', 'creditos' => 4, 'horas_teoricas' => 3, 'horas_practicas' => 1];

        $id = $this->postJson('/api/admin/materias', [...$datos, 'prerequisitos' => [$a->id, $b->id]])
            ->assertCreated()
            ->assertJsonPath('message', 'Materia registrada correctamente.')
            ->json('registro.id');

        $materia = Materia::query()->findOrFail($id);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $materia->prerequisitos->pluck('id')->all());

        $valores = $this->getJson("/api/admin/materias/$id")->assertOk()->json('valores');
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $valores['prerequisitos']);

        // La lista vacía no viaja en multipart: si no llega, se trata como [] (quita los prerrequisitos).
        $this->withHeaders(self::MULTIPART)->post("/api/admin/materias/$id", ['_method' => 'PUT', ...array_map('strval', $datos)])
            ->assertOk()
            ->assertJsonPath('message', 'Materia actualizada correctamente.')
            ->assertJsonPath('registro.prerequisitos_texto', '—');
        $this->assertCount(0, $materia->fresh()->prerequisitos);

        // null en JSON también es una lista vacía.
        $this->putJson("/api/admin/materias/$id", [...$datos, 'prerequisitos' => [$a->id]])->assertOk()->assertJsonPath('registro.prerequisitos_texto', 'ADM-100');
        $this->putJson("/api/admin/materias/$id", [...$datos, 'prerequisitos' => null])->assertOk()->assertJsonPath('registro.prerequisitos_texto', '—');

        // Validaciones: sigla única y una materia no puede ser su propio prerrequisito.
        $this->postJson('/api/admin/materias', [...$datos, 'sigla' => 'ADM-100'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.sigla', ['El valor de Sigla ya está registrado.']);
        $errores = $this->putJson("/api/admin/materias/$id", [...$datos, 'prerequisitos' => [$id]])->assertUnprocessable()->json('errors');
        $this->assertSame(['El valor seleccionado en Prerrequisitos no es válido.'], $errores['prerequisitos.0']);
    }

    public function test_casillas_booleanas_en_multipart_y_filtro_booleano(): void
    {
        Sanctum::actingAs($this->admin());

        $this->withHeaders(self::MULTIPART)->post('/api/admin/docentes', [
            'nombre' => 'Ana Pérez', 'tipo' => 'docente', 'orden' => '1', 'visible' => '0', 'mostrar_correo' => '1', 'mostrar_formacion' => 'false',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Docente registrado correctamente.')
            ->assertJsonPath('registro.visible', false)
            ->assertJsonPath('registro.mostrar_correo', true)
            ->assertJsonPath('registro.mostrar_formacion', false);

        $this->postJson('/api/admin/docentes', ['nombre' => 'Luis Rojas', 'tipo' => 'autoridad', 'visible' => true])->assertCreated();

        $this->getJson('/api/admin/docentes?visible=0')->assertJsonPath('registros.total', 1)->assertJsonPath('registros.data.0.nombre', 'Ana Pérez');
        $this->getJson('/api/admin/docentes?visible=1')->assertJsonPath('registros.total', 1)->assertJsonPath('registros.data.0.nombre', 'Luis Rojas');

        $campos = collect($this->getJson('/api/admin/docentes/formulario')->json('campos'))->keyBy('nombre');
        $this->assertSame('checkbox', $campos['visible']['tipo']);
        $this->assertSame(['requerido' => false], $campos['visible']['reglas']);

        $this->postJson('/api/admin/docentes', ['nombre' => 'Sin dato', 'tipo' => 'docente', 'visible' => 'quizás'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.visible', ['El campo Mostrar en el sitio debe ser verdadero o falso.']);
    }

    public function test_permisos_del_listado_reflejan_el_rol_del_usuario(): void
    {
        $categoria = CategoriaConsulta::query()->create(['nombre' => 'Admisión']);
        $consulta = Consulta::query()->create([
            'categoria_consulta_id' => $categoria->id, 'nombre' => 'Juan Pérez', 'correo' => 'juan@correo.test',
            'mensaje' => '¿Cuándo son las inscripciones?', 'estado' => 'nueva',
        ]);

        Sanctum::actingAs($this->usuario('Secretaría Académica', self::PERMISOS_SECRETARIA));

        // consultas.ver / consultas.editar, sin crear (el módulo no registra consultas) ni eliminar.
        $this->getJson('/api/admin/consultas')
            ->assertOk()
            ->assertJsonPath('config.permisos', ['crear' => false, 'editar' => true, 'eliminar' => false])
            ->assertJsonPath('registros.data.0.categoria.nombre', 'Admisión');
        $this->getJson('/api/admin/materias')->assertOk()->assertJsonPath('config.permisos', ['crear' => true, 'editar' => true, 'eliminar' => true]);

        // Al abrirla pasa a "en revisión"; los datos del visitante son de solo lectura.
        $respuesta = $this->getJson("/api/admin/consultas/{$consulta->id}")
            ->assertOk()
            ->assertJsonPath('valores.estado', 'en_revision')
            ->assertJsonPath('valores.categoria_nombre', 'Admisión');
        $campos = collect($respuesta->json('campos'))->keyBy('nombre');
        $this->assertSame('readonly', $campos['mensaje']['tipo']);
        $this->assertSame(['requerido' => true, 'enLista' => ['nueva', 'en_revision', 'respondida', 'cerrada']], $campos['estado']['reglas']);

        $this->putJson("/api/admin/consultas/{$consulta->id}", ['estado' => 'respondida', 'respuesta' => 'Se respondió por correo.', 'mensaje' => 'no se modifica'])
            ->assertOk()
            ->assertJsonPath('message', 'Consulta actualizada correctamente.')
            ->assertJsonPath('registro.estado', 'respondida');
        $this->assertDatabaseHas('consultas', ['id' => $consulta->id, 'estado' => 'respondida', 'mensaje' => '¿Cuándo son las inscripciones?']);

        $this->deleteJson("/api/admin/consultas/{$consulta->id}")->assertForbidden()->assertJsonPath('message', 'No tiene permiso para realizar esta acción.');
        $this->getJson('/api/admin/noticias')->assertForbidden();
        $this->getJson('/api/admin/noticias/formulario')->assertForbidden();
        $this->assertModelExists($consulta);
    }

    public function test_no_se_puede_eliminar_un_registro_con_informacion_relacionada(): void
    {
        Sanctum::actingAs($this->admin());

        $categoria = CategoriaDocumento::query()->create(['nombre' => 'Reglamentos', 'slug' => 'reglamentos']);
        Documento::query()->create(['categoria_documento_id' => $categoria->id, 'titulo' => 'Reglamento general', 'archivo' => 'documentos/reglamento.pdf']);

        $this->deleteJson("/api/admin/categorias-documento/{$categoria->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'No se puede eliminar: tiene información relacionada.');
        $this->assertModelExists($categoria);

        // Slug generado y único.
        $this->postJson('/api/admin/categorias-documento', ['nombre' => 'Reglamentos'])
            ->assertCreated()
            ->assertJsonPath('message', 'Categoría registrada correctamente.')
            ->assertJsonPath('registro.slug', 'reglamentos-2');

        $vacia = CategoriaDocumento::query()->where('slug', 'reglamentos-2')->firstOrFail();
        $this->deleteJson("/api/admin/categorias-documento/{$vacia->id}")->assertOk()->assertJsonPath('message', 'Categoría eliminada.');
        $this->assertModelMissing($vacia);
    }
}

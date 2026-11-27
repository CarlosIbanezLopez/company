<?php

namespace Tests\Feature\Api;

use App\Models\ActividadCalendario;
use App\Models\Album;
use App\Models\Banner;
use App\Models\CategoriaConsulta;
use App\Models\CategoriaDocumento;
use App\Models\CategoriaNoticia;
use App\Models\Configuracion;
use App\Models\Convenio;
use App\Models\Destacado;
use App\Models\Docente;
use App\Models\Documento;
use App\Models\EnlacePlataforma;
use App\Models\Evento;
use App\Models\InnovaIntegrante;
use App\Models\InnovaProyecto;
use App\Models\InnovaServicio;
use App\Models\LineaInvestigacion;
use App\Models\Materia;
use App\Models\Noticia;
use App\Models\Pagina;
use App\Models\PreguntaFrecuente;
use App\Models\ProyectoInvestigacion;
use App\Models\RevistaArticulo;
use App\Models\RevistaEdicion;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * API pública del portal: cada GET devuelve las mismas props que recibía la página Vue original.
 */
class SitioContenidoTest extends TestCase
{
    use RefreshDatabase;

    private const PAGINADOR = ['data', 'current_page', 'last_page', 'per_page', 'total', 'from', 'to', 'links'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->crearContenido();
    }

    public function test_datos_compartidos_del_sitio(): void
    {
        $this->getJson('/api/sitio')
            ->assertOk()
            ->assertJsonStructure([
                'carrera', 'facultad', 'universidad',
                'contacto' => ['direccion', 'telefonos', 'whatsapp', 'correo', 'horario'],
                'redes',
                'menu' => ['institucional', 'academico'],
            ])
            ->assertJsonPath('carrera', 'Administración de Empresas')
            ->assertJsonPath('contacto.correo', 'carrera@ejemplo.test')
            ->assertJsonPath('redes', ['facebook' => 'https://facebook.com/carrera'])
            ->assertJsonPath('menu.institucional', [['slug' => 'historia', 'titulo' => 'Historia'], ['slug' => 'mision', 'titulo' => 'Misión']])
            ->assertJsonPath('menu.academico', [['slug' => 'admision', 'titulo' => 'Admisión']]);
    }

    public function test_redes_es_un_objeto_aunque_no_haya_ninguna(): void
    {
        Configuracion::guardar('facebook', '', 'contacto');

        $this->assertStringContainsString('"redes":{}', $this->getJson('/api/sitio')->assertOk()->getContent());
    }

    public function test_pagina_de_inicio(): void
    {
        $this->getJson('/api/inicio')
            ->assertOk()
            ->assertJsonStructure(['banners', 'presentacion' => ['titulo', 'html', 'autor', 'video'], 'destacados', 'noticias', 'eventos', 'enlaces'])
            ->assertJsonPath('presentacion.html', fn (string $html) => str_contains($html, '<strong>profesionales</strong>'))
            ->assertJsonCount(1, 'banners')
            ->assertJsonCount(2, 'noticias')
            ->assertJsonPath('noticias.0.slug', 'noticia-destacada')
            ->assertJsonCount(1, 'eventos')
            ->assertJsonPath('eventos.0.slug', 'congreso-proximo');
    }

    public function test_paginas_institucionales_y_academicas(): void
    {
        $this->getJson('/api/institucional/historia')
            ->assertOk()
            ->assertJsonStructure(['pagina' => ['titulo', 'slug', 'resumen', 'html', 'imagen_url', 'updated_at'], 'seccion', 'hermanas', 'prefijo'])
            ->assertJsonPath('seccion', 'Institucional')
            ->assertJsonPath('prefijo', 'institucional')
            ->assertJsonPath('pagina.html', fn (string $html) => str_contains($html, '<h1>Nuestra historia</h1>'))
            ->assertJsonPath('hermanas', [['slug' => 'historia', 'titulo' => 'Historia'], ['slug' => 'mision', 'titulo' => 'Misión']]);

        $this->getJson('/api/academico/admision')->assertOk()->assertJsonPath('seccion', 'Académico')->assertJsonPath('prefijo', 'academico');
    }

    public function test_slug_inexistente_o_no_publicado_responde_404_en_espanol(): void
    {
        foreach ([
            '/api/institucional/no-existe',
            '/api/institucional/oculta',
            '/api/institucional/admision',
            '/api/academico/historia',
            '/api/noticias/no-existe',
            '/api/noticias/borrador',
            '/api/eventos/no-existe',
            '/api/galeria/no-existe',
            '/api/investigacion/no-existe',
            '/api/revista-innovar/99',
        ] as $uri) {
            $this->getJson($uri)->assertNotFound()->assertExactJson(['message' => 'El recurso solicitado no existe.']);
        }
    }

    public function test_plantel_docente_oculta_lo_no_autorizado_y_filtra(): void
    {
        $respuesta = $this->getJson('/api/plantel-docente')
            ->assertOk()
            ->assertJsonStructure(['autoridades' => [['id', 'nombre', 'tipo', 'cargo', 'especialidad', 'materias', 'formacion', 'correo', 'foto_url']], 'docentes', 'q'])
            ->assertJsonCount(1, 'autoridades')
            ->assertJsonCount(2, 'docentes')
            ->assertJsonPath('q', null);

        $reservada = collect($respuesta->json('docentes'))->firstWhere('nombre', 'Lic. Docente Reservada');
        $this->assertNull($reservada['correo']);
        $this->assertNull($reservada['formacion']);
        $this->assertSame('director@ejemplo.test', $respuesta->json('autoridades.0.correo'));

        $this->getJson('/api/plantel-docente?q=finanzas')->assertOk()->assertJsonCount(0, 'autoridades')->assertJsonCount(1, 'docentes')->assertJsonPath('q', 'finanzas');

        // Un filtro malformado no produce error.
        $this->getJson('/api/plantel-docente?q[]=x')->assertOk()->assertJsonPath('q', null);
    }

    public function test_malla_curricular_con_prerrequisitos_y_posteriores(): void
    {
        $respuesta = $this->getJson('/api/malla-curricular')
            ->assertOk()
            ->assertJsonStructure(['materias' => [['id', 'sigla', 'nombre', 'semestre', 'creditos', 'horas_teoricas', 'horas_practicas', 'tipo', 'descripcion', 'prerequisitos', 'posteriores']], 'semestres'])
            ->assertJsonPath('semestres', [1, 2]);

        $materias = collect($respuesta->json('materias'));
        $base = $materias->firstWhere('sigla', 'ADM-100');
        $avanzada = $materias->firstWhere('sigla', 'ADM-200');

        $this->assertSame([$base['id']], $avanzada['prerequisitos']);
        $this->assertSame([$avanzada['id']], $base['posteriores']);
    }

    public function test_calendario_academico_por_gestion(): void
    {
        $this->getJson('/api/calendario-academico')
            ->assertOk()
            ->assertJsonStructure(['actividades' => [['id', 'titulo', 'descripcion', 'fecha_inicio', 'fecha_fin', 'tipo']], 'gestiones', 'gestion', 'tipos'])
            ->assertJsonPath('gestiones', ['2026', '2025'])
            ->assertJsonPath('gestion', '2026')
            ->assertJsonCount(2, 'actividades');

        $this->getJson('/api/calendario-academico?gestion=2025')->assertOk()->assertJsonPath('gestion', '2025')->assertJsonCount(1, 'actividades');
    }

    public function test_preguntas_frecuentes_agrupadas(): void
    {
        $this->getJson('/api/preguntas-frecuentes')
            ->assertOk()
            ->assertJsonStructure(['grupos' => ['Admisión' => [['id', 'pregunta', 'respuesta']], 'General']])
            ->assertJsonCount(2, 'grupos.Admisión')
            ->assertJsonCount(1, 'grupos.General');
    }

    public function test_biblioteca_digital_con_filtros_y_enlace_de_descarga(): void
    {
        $documento = Documento::query()->where('titulo', 'Reglamento de modalidades de graduación')->firstOrFail();

        $this->getJson('/api/biblioteca')
            ->assertOk()
            ->assertJsonStructure([
                'documentos' => [...self::PAGINADOR, 'data' => [['id', 'titulo', 'descripcion', 'fecha_publicacion', 'descargas', 'archivo_url', 'descargar_url', 'categoria' => ['nombre', 'slug']]]],
                'categorias' => [['id', 'nombre', 'slug', 'descripcion', 'documentos_count']],
                'filtros',
            ])
            ->assertJsonPath('documentos.total', 2)
            ->assertJsonPath('documentos.data.0.descargar_url', url('/api/biblioteca/'.$documento->id.'/descargar'))
            ->assertJsonPath('categorias.0.documentos_count', 2)
            ->assertJsonPath('filtros', []);

        $this->getJson('/api/biblioteca?q=modalidades')->assertOk()->assertJsonPath('documentos.total', 1)->assertJsonPath('filtros', ['q' => 'modalidades']);
        $this->getJson('/api/biblioteca?categoria=reglamentos')->assertOk()->assertJsonPath('documentos.total', 2);
        $this->getJson('/api/biblioteca?categoria=otra')->assertOk()->assertJsonPath('documentos.total', 0);
    }

    public function test_descarga_de_documento_suma_el_contador(): void
    {
        $documento = Documento::query()->where('titulo', 'Reglamento de modalidades de graduación')->firstOrFail();

        $this->get('/api/biblioteca/'.$documento->id.'/descargar')
            ->assertOk()
            ->assertDownload('reglamento-de-modalidades-de-graduacion.pdf');

        $this->assertSame(1, $documento->fresh()->descargas);

        $oculto = Documento::query()->where('publicado', false)->firstOrFail();
        $this->getJson('/api/biblioteca/'.$oculto->id.'/descargar')->assertNotFound();
        $this->getJson('/api/biblioteca/9999/descargar')->assertNotFound();
    }

    public function test_investigacion_con_filtros_y_detalle(): void
    {
        $linea = LineaInvestigacion::query()->firstOrFail();

        $this->getJson('/api/investigacion')
            ->assertOk()
            ->assertJsonStructure([
                'lineas' => [['id', 'nombre', 'descripcion', 'proyectos_count']],
                'proyectos' => [['id', 'tipo', 'titulo', 'slug', 'responsables', 'estado', 'fecha_inicio', 'imagen_url', 'linea']],
                'estados', 'tipos', 'filtros',
            ])
            ->assertJsonCount(2, 'proyectos')
            ->assertJsonPath('lineas.0.proyectos_count', 1);

        $this->getJson('/api/investigacion?linea='.$linea->id)->assertOk()->assertJsonCount(1, 'proyectos')->assertJsonPath('filtros', ['linea' => (string) $linea->id]);
        $this->getJson('/api/investigacion?tipo=pia')->assertOk()->assertJsonCount(1, 'proyectos')->assertJsonPath('proyectos.0.slug', 'pia-mercados');
        // Un identificador no numérico se ignora (en PostgreSQL provocaría un error).
        $this->getJson('/api/investigacion?linea=abc')->assertOk()->assertJsonCount(2, 'proyectos');

        $this->getJson('/api/investigacion/estudio-mypes')
            ->assertOk()
            ->assertJsonStructure(['proyecto' => ['titulo', 'tipo', 'html', 'responsables', 'estado', 'fecha_inicio', 'fecha_fin', 'resultados', 'enlace_publicacion', 'enlace_repositorio', 'imagen_url', 'linea'], 'estados', 'tipos'])
            ->assertJsonPath('proyecto.linea.nombre', 'Emprendimiento');
    }

    public function test_revista_innovar(): void
    {
        $this->getJson('/api/revista-innovar')
            ->assertOk()
            ->assertJsonStructure(['ediciones' => [['id', 'numero', 'titulo', 'fecha_publicacion', 'descripcion', 'portada_url', 'archivo_pdf_url', 'articulos_count']], 'presentacion'])
            ->assertJsonCount(2, 'ediciones')
            ->assertJsonPath('ediciones.0.numero', 2)
            ->assertJsonPath('ediciones.1.articulos_count', 2)
            ->assertJsonPath('presentacion', fn (string $html) => str_contains($html, 'Revista académica'));

        $this->getJson('/api/revista-innovar/1')
            ->assertOk()
            ->assertJsonStructure([
                'edicion' => ['numero', 'titulo', 'fecha_publicacion', 'descripcion', 'portada_url', 'archivo_pdf_url', 'articulos' => [['id', 'titulo', 'autores', 'resumen', 'palabras_clave', 'archivo_pdf_url']]],
                'otras' => [['id', 'numero', 'titulo', 'portada_url']],
            ])
            ->assertJsonCount(2, 'edicion.articulos')
            ->assertJsonPath('otras.0.numero', 2);

        $this->getJson('/api/revista-innovar/normas-para-autores')
            ->assertOk()
            ->assertJsonPath('seccion', 'Revista INNOVAR')
            ->assertJsonPath('hermanas', [])
            ->assertJsonPath('pagina.slug', 'normas-para-autores');
    }

    public function test_vinculacion_innova_y_graduados(): void
    {
        $this->getJson('/api/vinculacion')
            ->assertOk()
            ->assertJsonStructure(['convenios' => [['id', 'institucion', 'tipo', 'finalidad', 'fecha_inicio', 'fecha_fin', 'logo_url', 'estado']], 'tipos', 'estados', 'tipo'])
            ->assertJsonPath('convenios.0.estado', 'vigente')
            ->assertJsonPath('tipo', null);

        $this->getJson('/api/vinculacion?tipo=practica')->assertOk()->assertJsonCount(1, 'convenios')->assertJsonPath('tipo', 'practica');

        $this->getJson('/api/innova')
            ->assertOk()
            ->assertJsonStructure([
                'descripcion' => ['titulo', 'html'],
                'servicios' => [['id', 'nombre', 'descripcion']],
                'integrantes' => [['id', 'nombre', 'cargo', 'semestre', 'foto_url']],
                'proyectos' => [['id', 'titulo', 'cliente', 'descripcion', 'fecha', 'imagen_url']],
                'antispam' => ['token'],
            ])
            ->assertJsonMissingPath('antispam.pregunta');

        $this->getJson('/api/graduados')
            ->assertOk()
            ->assertJsonStructure(['paginas' => [['slug', 'titulo', 'resumen', 'html']], 'situaciones', 'modalidades', 'tieneFicha', 'antispam' => ['token'], 'total'])
            ->assertJsonPath('tieneFicha', false)
            ->assertJsonPath('total', 0);
    }

    public function test_noticias_listado_filtro_y_detalle(): void
    {
        $this->getJson('/api/noticias')
            ->assertOk()
            ->assertJsonStructure([
                'noticias' => [...self::PAGINADOR, 'data' => [['id', 'titulo', 'slug', 'resumen', 'imagen_url', 'fecha_publicacion', 'destacado', 'categoria']]],
                'categorias' => [['nombre', 'slug']],
                'categoria',
            ])
            ->assertJsonPath('noticias.total', 2)
            ->assertJsonPath('categoria', null);

        $this->getJson('/api/noticias?categoria=academicas')->assertOk()->assertJsonPath('noticias.total', 1)->assertJsonPath('categoria', 'academicas');

        $this->getJson('/api/noticias/noticia-destacada')
            ->assertOk()
            ->assertJsonStructure(['noticia' => ['titulo', 'resumen', 'html', 'imagen_url', 'fecha_publicacion', 'categoria' => ['nombre', 'slug'], 'autor'], 'relacionadas'])
            ->assertJsonPath('noticia.html', fn (string $html) => str_contains($html, '<em>importante</em>'))
            ->assertJsonCount(0, 'relacionadas');
    }

    public function test_eventos_proximos_historial_y_detalle(): void
    {
        $this->getJson('/api/eventos')
            ->assertOk()
            ->assertJsonStructure(['proximos' => [['id', 'titulo', 'slug', 'tipo', 'fecha', 'hora_inicio', 'hora_fin', 'lugar', 'afiche_url', 'enlace_inscripcion']], 'historial' => self::PAGINADOR, 'tipos'])
            ->assertJsonCount(1, 'proximos')
            ->assertJsonPath('historial.total', 1);

        $this->getJson('/api/eventos/congreso-proximo')->assertOk()->assertJsonPath('evento.finalizado', false)->assertJsonStructure(['evento' => ['html'], 'tipos']);
        $this->getJson('/api/eventos/seminario-pasado')->assertOk()->assertJsonPath('evento.finalizado', true);
    }

    public function test_galeria_y_album(): void
    {
        $this->getJson('/api/galeria')
            ->assertOk()
            ->assertJsonStructure(['albumes' => [['id', 'titulo', 'slug', 'descripcion', 'fecha', 'portada_url', 'fotos_count']], 'videos' => [['id', 'titulo', 'url', 'descripcion']]])
            ->assertJsonPath('albumes.0.fotos_count', 2);

        $this->getJson('/api/galeria/aniversario')
            ->assertOk()
            ->assertJsonStructure(['album' => ['titulo', 'descripcion', 'fecha', 'fotos' => [['id', 'imagen_url', 'descripcion']], 'videos' => [['id', 'titulo', 'url']]]])
            ->assertJsonCount(2, 'album.fotos')
            ->assertJsonCount(1, 'album.videos');
    }

    public function test_pagina_de_contacto_incluye_antispam_con_pregunta(): void
    {
        $this->getJson('/api/contacto')
            ->assertOk()
            ->assertJsonStructure(['categorias' => [['id', 'nombre']], 'mapa', 'antispam' => ['token', 'pregunta']])
            ->assertJsonPath('mapa', 'https://maps.example.test/embed')
            ->assertJsonPath('antispam.pregunta', fn (string $p) => (bool) preg_match('/^¿Cuánto es \d \+ \d\?$/u', $p));
    }

    private function crearContenido(): void
    {
        foreach ([
            'nombre_carrera' => 'Administración de Empresas',
            'nombre_facultad' => 'Facultad de Ciencias Económicas y Empresariales',
            'nombre_universidad' => 'Universidad Autónoma Gabriel René Moreno',
            'presentacion_titulo' => 'Bienvenidos',
            'presentacion_texto' => 'Formamos **profesionales** con visión emprendedora.',
            'presentacion_autor' => 'Dirección de Carrera',
            'correo' => 'carrera@ejemplo.test',
            'mapa_url' => 'https://maps.example.test/embed',
            'facebook' => 'https://facebook.com/carrera',
            'tiktok' => '',
        ] as $clave => $valor) {
            Configuracion::guardar($clave, $valor);
        }

        Pagina::query()->create(['seccion' => 'institucional', 'slug' => 'historia', 'titulo' => 'Historia', 'contenido' => '# Nuestra historia', 'orden' => 1]);
        Pagina::query()->create(['seccion' => 'institucional', 'slug' => 'mision', 'titulo' => 'Misión', 'contenido' => 'Misión de la Carrera', 'orden' => 2]);
        Pagina::query()->create(['seccion' => 'institucional', 'slug' => 'oculta', 'titulo' => 'Oculta', 'publicado' => false]);
        Pagina::query()->create(['seccion' => 'academico', 'slug' => 'admision', 'titulo' => 'Admisión', 'contenido' => 'Requisitos de admisión']);
        Pagina::query()->create(['seccion' => 'revista', 'slug' => 'revista-innovar', 'titulo' => 'Revista INNOVAR', 'contenido' => 'Revista académica de la Carrera']);
        Pagina::query()->create(['seccion' => 'revista', 'slug' => 'normas-para-autores', 'titulo' => 'Normas para autores', 'contenido' => 'Formato de los artículos']);
        Pagina::query()->create(['seccion' => 'innova', 'slug' => 'empresa-junior-innova', 'titulo' => 'Empresa Junior INNOVA', 'contenido' => 'Consultoría estudiantil']);
        Pagina::query()->create(['seccion' => 'graduados', 'slug' => 'programa-alumni', 'titulo' => 'Programa Alumni', 'contenido' => 'Beneficios para graduados']);

        Banner::query()->create(['titulo' => 'Formando líderes', 'enlace' => '/malla-curricular', 'orden' => 1]);
        Banner::query()->create(['titulo' => 'Inactivo', 'activo' => false]);
        Destacado::query()->create(['titulo' => 'Semestres', 'valor' => '10', 'enlace' => '/malla-curricular']);
        EnlacePlataforma::query()->create(['nombre' => 'Portal UAGRM', 'url' => 'https://www.uagrm.edu.bo/']);

        $academicas = CategoriaNoticia::query()->create(['nombre' => 'Académicas', 'slug' => 'academicas']);
        CategoriaNoticia::query()->create(['nombre' => 'Institucionales', 'slug' => 'institucionales']);
        Noticia::query()->create(['categoria_noticia_id' => $academicas->id, 'titulo' => 'Noticia destacada', 'slug' => 'noticia-destacada', 'resumen' => 'Resumen', 'contenido' => 'Un anuncio *importante*', 'fecha_publicacion' => now()->subDays(3), 'estado' => 'publicado', 'destacado' => true]);
        Noticia::query()->create(['titulo' => 'Comunicado general', 'slug' => 'comunicado-general', 'fecha_publicacion' => now()->subDay(), 'estado' => 'publicado']);
        Noticia::query()->create(['titulo' => 'Borrador', 'slug' => 'borrador', 'fecha_publicacion' => now(), 'estado' => 'borrador']);

        Evento::query()->create(['titulo' => 'Congreso próximo', 'slug' => 'congreso-proximo', 'tipo' => 'congreso', 'descripcion' => 'Congreso de **gestión**', 'fecha' => now()->addWeek(), 'hora_inicio' => '09:00']);
        Evento::query()->create(['titulo' => 'Seminario pasado', 'slug' => 'seminario-pasado', 'tipo' => 'seminario', 'fecha' => now()->subWeek()]);
        Evento::query()->create(['titulo' => 'Evento oculto', 'slug' => 'evento-oculto', 'fecha' => now()->addDay(), 'publicado' => false]);

        $album = Album::query()->create(['titulo' => 'Aniversario', 'slug' => 'aniversario', 'fecha' => now()->subMonth()]);
        $album->fotos()->create(['imagen' => 'galeria/foto-1.jpg', 'orden' => 2]);
        $album->fotos()->create(['imagen' => 'galeria/foto-2.jpg', 'orden' => 1]);
        Video::query()->create(['album_id' => $album->id, 'titulo' => 'Resumen del aniversario', 'url' => 'https://www.youtube.com/watch?v=abcdefghijk']);
        Album::query()->create(['titulo' => 'Oculto', 'slug' => 'album-oculto', 'publicado' => false]);

        Docente::query()->create(['nombre' => 'Mg. Director de Carrera', 'tipo' => 'autoridad', 'cargo' => 'Director', 'correo' => 'director@ejemplo.test', 'mostrar_correo' => true]);
        Docente::query()->create(['nombre' => 'Lic. Docente Reservada', 'tipo' => 'docente', 'especialidad' => 'Finanzas', 'formacion' => 'Maestría', 'correo' => 'reservada@ejemplo.test', 'mostrar_correo' => false, 'mostrar_formacion' => false]);
        Docente::query()->create(['nombre' => 'Ing. Docente Público', 'tipo' => 'docente', 'especialidad' => 'Marketing']);
        Docente::query()->create(['nombre' => 'No visible', 'tipo' => 'docente', 'visible' => false]);

        $base = Materia::query()->create(['sigla' => 'ADM-100', 'nombre' => 'Administración I', 'semestre' => 1, 'creditos' => 5]);
        $avanzada = Materia::query()->create(['sigla' => 'ADM-200', 'nombre' => 'Administración II', 'semestre' => 2, 'creditos' => 5]);
        $avanzada->prerequisitos()->attach($base->id);

        ActividadCalendario::query()->create(['titulo' => 'Inscripciones', 'fecha_inicio' => '2026-02-01', 'tipo' => 'inscripciones', 'gestion' => '2026']);
        ActividadCalendario::query()->create(['titulo' => 'Inicio de clases', 'fecha_inicio' => '2026-02-15', 'tipo' => 'clases', 'gestion' => '2026']);
        ActividadCalendario::query()->create(['titulo' => 'Exámenes finales', 'fecha_inicio' => '2025-11-20', 'tipo' => 'examenes', 'gestion' => '2025']);

        PreguntaFrecuente::query()->create(['pregunta' => '¿Cómo me inscribo?', 'respuesta' => 'En el sistema.', 'categoria' => 'Admisión', 'orden' => 1]);
        PreguntaFrecuente::query()->create(['pregunta' => '¿Hay examen de ingreso?', 'respuesta' => 'Sí.', 'categoria' => 'Admisión', 'orden' => 2]);
        PreguntaFrecuente::query()->create(['pregunta' => '¿Dónde queda la Carrera?', 'respuesta' => 'En el campus.']);
        PreguntaFrecuente::query()->create(['pregunta' => 'Inactiva', 'respuesta' => 'No se muestra.', 'activo' => false]);

        $reglamentos = CategoriaDocumento::query()->create(['nombre' => 'Reglamentos', 'slug' => 'reglamentos']);
        Storage::disk('public')->put('documentos/reglamento.pdf', '%PDF-1.4 prueba');
        Documento::query()->create(['categoria_documento_id' => $reglamentos->id, 'titulo' => 'Reglamento de modalidades de graduación', 'descripcion' => 'Normativa vigente', 'archivo' => 'documentos/reglamento.pdf', 'fecha_publicacion' => now()->subDays(2)]);
        Documento::query()->create(['categoria_documento_id' => $reglamentos->id, 'titulo' => 'Reglamento estudiantil', 'archivo' => 'documentos/no-existe.pdf', 'fecha_publicacion' => now()->subDays(5)]);
        Documento::query()->create(['categoria_documento_id' => $reglamentos->id, 'titulo' => 'Documento oculto', 'archivo' => 'documentos/reglamento.pdf', 'publicado' => false]);

        $linea = LineaInvestigacion::query()->create(['nombre' => 'Emprendimiento']);
        ProyectoInvestigacion::query()->create(['linea_investigacion_id' => $linea->id, 'tipo' => 'proyecto', 'titulo' => 'Estudio de las MYPES', 'slug' => 'estudio-mypes', 'descripcion' => 'Diagnóstico **regional**', 'fecha_inicio' => '2025-03-01', 'estado' => 'en_curso']);
        ProyectoInvestigacion::query()->create(['tipo' => 'pia', 'titulo' => 'PIA de mercados', 'slug' => 'pia-mercados', 'estado' => 'finalizado']);
        ProyectoInvestigacion::query()->create(['tipo' => 'proyecto', 'titulo' => 'Proyecto oculto', 'slug' => 'proyecto-oculto', 'publicado' => false]);

        $primera = RevistaEdicion::query()->create(['numero' => 1, 'titulo' => 'Edición inaugural', 'fecha_publicacion' => '2024-06-01']);
        RevistaArticulo::query()->create(['revista_edicion_id' => $primera->id, 'titulo' => 'Gestión del talento humano', 'autores' => 'Ana Pérez', 'orden' => 2]);
        RevistaArticulo::query()->create(['revista_edicion_id' => $primera->id, 'titulo' => 'Finanzas para pymes', 'autores' => 'Luis Rojas', 'orden' => 1]);
        RevistaEdicion::query()->create(['numero' => 2, 'titulo' => 'Segunda edición', 'fecha_publicacion' => '2025-06-01']);
        RevistaEdicion::query()->create(['numero' => 3, 'titulo' => 'Edición no publicada', 'publicado' => false]);

        Convenio::query()->create(['institucion' => 'Cámara de Industria', 'tipo' => 'convenio', 'estado' => 'finalizado', 'fecha_inicio' => '2026-01-01']);
        Convenio::query()->create(['institucion' => 'Empresa de prácticas', 'tipo' => 'practica', 'estado' => 'vigente', 'fecha_inicio' => '2024-01-01']);

        InnovaServicio::query()->create(['nombre' => 'Plan de negocios']);
        InnovaIntegrante::query()->create(['nombre' => 'Estudiante INNOVA', 'cargo' => 'Gerente']);
        InnovaProyecto::query()->create(['titulo' => 'Consultoría para una pyme', 'cliente' => 'Pyme local', 'fecha' => '2025-08-01']);

        CategoriaConsulta::query()->create(['nombre' => 'Admisión']);
    }
}

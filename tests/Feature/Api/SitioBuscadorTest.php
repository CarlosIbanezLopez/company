<?php

namespace Tests\Feature\Api;

use App\Models\CategoriaDocumento;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Materia;
use App\Models\Noticia;
use App\Models\Pagina;
use App\Models\ProyectoInvestigacion;
use App\Models\RevistaArticulo;
use App\Models\RevistaEdicion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Buscador interno (GET /api/buscar) y búsqueda predictiva (GET /api/buscar/sugerencias).
 */
class SitioBuscadorTest extends TestCase
{
    use RefreshDatabase;

    private Materia $materia;

    protected function setUp(): void
    {
        parent::setUp();

        Noticia::query()->create(['titulo' => 'Noticia reciente', 'slug' => 'noticia-reciente', 'contenido' => 'Habla sobre marketing.', 'fecha_publicacion' => now(), 'estado' => 'publicado']);
        Noticia::query()->create(['titulo' => 'Feria de marketing digital', 'slug' => 'feria-marketing', 'resumen' => 'Feria anual', 'fecha_publicacion' => now()->subDays(10), 'estado' => 'publicado']);
        Noticia::query()->create(['titulo' => 'Marketing en borrador', 'slug' => 'marketing-borrador', 'fecha_publicacion' => now(), 'estado' => 'borrador']);

        Evento::query()->create(['titulo' => 'Taller de marketing', 'slug' => 'taller-marketing', 'descripcion' => '<b>Taller</b> práctico', 'fecha' => '2026-05-10']);

        $categoria = CategoriaDocumento::query()->create(['nombre' => 'Guías', 'slug' => 'guias']);
        Documento::query()->create(['categoria_documento_id' => $categoria->id, 'titulo' => 'Guía de marketing', 'fecha_publicacion' => '2026-01-15']);

        $this->materia = Materia::query()->create(['sigla' => 'MKT-300', 'nombre' => 'Marketing estratégico', 'semestre' => 5]);

        ProyectoInvestigacion::query()->create(['titulo' => 'Marketing en pymes', 'slug' => 'marketing-pymes', 'responsables' => 'Equipo de investigación', 'fecha_inicio' => '2025-02-01']);

        $edicion = RevistaEdicion::query()->create(['numero' => 1, 'titulo' => 'Edición inaugural']);
        RevistaArticulo::query()->create(['revista_edicion_id' => $edicion->id, 'titulo' => 'El marketing en Bolivia', 'autores' => 'Ana Pérez']);
        $oculta = RevistaEdicion::query()->create(['numero' => 2, 'titulo' => 'Edición oculta', 'publicado' => false]);
        RevistaArticulo::query()->create(['revista_edicion_id' => $oculta->id, 'titulo' => 'Marketing no publicado', 'autores' => 'Anónimo']);

        Pagina::query()->create(['seccion' => 'academico', 'slug' => 'perfil-profesional', 'titulo' => 'Perfil profesional', 'resumen' => 'Competencias', 'contenido' => 'Domina finanzas y marketing.']);
        Pagina::query()->create(['seccion' => 'graduados', 'slug' => 'bolsa-marketing', 'titulo' => 'Marketing para graduados']);
    }

    public function test_busqueda_agrupa_por_seccion_con_rutas_de_la_spa(): void
    {
        $respuesta = $this->getJson('/api/buscar?q=marketing')
            ->assertOk()
            ->assertJsonStructure(['q', 'grupos' => [['seccion', 'resultados' => [['titulo', 'extracto', 'url', 'fecha']]]], 'total'])
            ->assertJsonPath('q', 'marketing')
            ->assertJsonPath('total', 8);

        $grupos = collect($respuesta->json('grupos'))->mapWithKeys(fn ($g) => [$g['seccion'] => collect($g['resultados'])->pluck('url')->all()]);

        $this->assertSame([
            'Noticias' => ['/noticias/feria-marketing', '/noticias/noticia-reciente'],
            'Eventos' => ['/eventos/taller-marketing'],
            'Documentos' => ['/biblioteca?q=Gu%C3%ADa%20de%20marketing'],
            'Materias' => ['/malla-curricular?materia='.$this->materia->id],
            'Proyectos de investigación' => ['/investigacion/marketing-pymes'],
            'Artículos de la Revista INNOVAR' => ['/revista-innovar/1'],
            'Páginas del sitio' => ['/academico/perfil-profesional'],
        ], $grupos->all());

        // El evento muestra un extracto sin etiquetas HTML; el artículo, su edición.
        $this->assertSame('Taller práctico', $respuesta->json('grupos.1.resultados.0.extracto'));
        $this->assertSame('10/05/2026', $respuesta->json('grupos.1.resultados.0.fecha'));
        $this->assertSame('Edición N° 1', $respuesta->json('grupos.5.resultados.0.fecha'));
    }

    public function test_prioriza_las_coincidencias_en_el_titulo(): void
    {
        // "Noticia reciente" es más nueva, pero solo menciona el texto en su contenido.
        $this->getJson('/api/buscar?q=marketing')
            ->assertOk()
            ->assertJsonPath('grupos.0.seccion', 'Noticias')
            ->assertJsonPath('grupos.0.resultados.0.titulo', 'Feria de marketing digital')
            ->assertJsonPath('grupos.0.resultados.1.titulo', 'Noticia reciente');
    }

    public function test_busqueda_requiere_al_menos_tres_caracteres(): void
    {
        $this->getJson('/api/buscar?q=ma')->assertOk()->assertExactJson(['q' => 'ma', 'grupos' => [], 'total' => 0]);
        $this->getJson('/api/buscar')->assertOk()->assertExactJson(['q' => '', 'grupos' => [], 'total' => 0]);
        $this->getJson('/api/buscar?q[]=marketing')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/buscar?q=inexistente')->assertOk()->assertExactJson(['q' => 'inexistente', 'grupos' => [], 'total' => 0]);
    }

    public function test_sugerencias_predictivas_solo_por_titulo_y_priorizadas(): void
    {
        $respuesta = $this->getJson('/api/buscar/sugerencias?q=marketing')
            ->assertOk()
            ->assertJsonStructure(['q', 'sugerencias' => [['titulo', 'seccion', 'url']]])
            ->assertJsonPath('q', 'marketing');

        $sugerencias = collect($respuesta->json('sugerencias'));

        // Primero los títulos que empiezan con el texto buscado.
        $this->assertSame(['titulo' => 'Marketing en pymes', 'seccion' => 'Proyectos de investigación', 'url' => '/investigacion/marketing-pymes'], $sugerencias->first());
        $this->assertSame(
            ['Marketing en pymes', 'Feria de marketing digital', 'Taller de marketing', 'Guía de marketing', 'MKT-300 - Marketing estratégico', 'El marketing en Bolivia'],
            $sugerencias->pluck('titulo')->all(),
        );

        // Solo compara títulos: lo que menciona el texto en el contenido no se sugiere.
        $this->assertNotContains('Noticia reciente', $sugerencias->pluck('titulo'));
        $this->assertNotContains('Perfil profesional', $sugerencias->pluck('titulo'));
        $this->assertTrue($sugerencias->every(fn ($s) => array_keys($s) === ['titulo', 'seccion', 'url']));
    }

    public function test_sugerencias_requieren_dos_caracteres_y_devuelven_ocho_como_maximo(): void
    {
        $this->getJson('/api/buscar/sugerencias?q=m')->assertOk()->assertExactJson(['q' => 'm', 'sugerencias' => []]);
        $this->getJson('/api/buscar/sugerencias')->assertOk()->assertExactJson(['q' => '', 'sugerencias' => []]);

        for ($i = 1; $i <= 10; $i++) {
            Noticia::query()->create(['titulo' => "Marketing semana $i", 'slug' => "marketing-semana-$i", 'fecha_publicacion' => now()->subDays($i), 'estado' => 'publicado']);
        }

        $this->getJson('/api/buscar/sugerencias?q=ma')->assertOk()->assertJsonCount(8, 'sugerencias');
        $this->getJson('/api/buscar/sugerencias?q=semana%201')
            ->assertOk()
            ->assertJsonPath('sugerencias.0', ['titulo' => 'Marketing semana 1', 'seccion' => 'Noticias', 'url' => '/noticias/marketing-semana-1']);
    }
}

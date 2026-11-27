<?php

namespace Tests\Feature\Api;

use App\Models\Album;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Noticia;
use App\Models\Pagina;
use App\Models\ProyectoInvestigacion;
use App\Models\RevistaEdicion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Prueba de humo con los datos iniciales del proyecto (seeders): todas las páginas públicas responden.
 */
class SitioDatosInicialesTest extends TestCase
{
    use RefreshDatabase;

    public function test_todas_las_paginas_publicas_responden_con_los_datos_iniciales(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $uris = [
            '/api/sitio', '/api/inicio', '/api/plantel-docente', '/api/malla-curricular', '/api/calendario-academico',
            '/api/preguntas-frecuentes', '/api/biblioteca', '/api/investigacion', '/api/revista-innovar',
            '/api/revista-innovar/normas-para-autores', '/api/vinculacion', '/api/innova', '/api/graduados', '/api/noticias',
            '/api/eventos', '/api/galeria', '/api/contacto', '/api/buscar?q=gestion', '/api/buscar/sugerencias?q=ad',
        ];

        foreach (Pagina::query()->where('publicado', true)->whereIn('seccion', ['institucional', 'academico'])->get() as $pagina) {
            $uris[] = '/api/'.$pagina->seccion.'/'.$pagina->slug;
        }

        $uris[] = '/api/noticias/'.Noticia::query()->where('estado', 'publicado')->value('slug');
        $uris[] = '/api/eventos/'.Evento::query()->where('publicado', true)->value('slug');
        $uris[] = '/api/galeria/'.Album::query()->where('publicado', true)->value('slug');
        $uris[] = '/api/investigacion/'.ProyectoInvestigacion::query()->where('publicado', true)->value('slug');
        $uris[] = '/api/revista-innovar/'.RevistaEdicion::query()->where('publicado', true)->value('numero');

        foreach ($uris as $uri) {
            $this->getJson($uri)->assertOk();
        }

        $this->getJson('/api/sitio')->assertJsonPath('menu.institucional', fn (array $menu) => count($menu) > 0);

        $documento = Documento::query()->where('publicado', true)->whereNotNull('archivo')->orderBy('id')->get()
            ->first(fn (Documento $d) => Storage::disk('public')->exists($d->archivo));

        if ($documento) {
            $this->get('/api/biblioteca/'.$documento->id.'/descargar')->assertOk()->assertDownload();
            $this->assertSame($documento->descargas + 1, $documento->fresh()->descargas);
        }
    }
}

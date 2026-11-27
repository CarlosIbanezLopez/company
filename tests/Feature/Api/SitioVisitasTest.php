<?php

namespace Tests\Feature\Api;

use App\Models\ContadorVisita;
use App\Models\User;
use App\Models\VisitaDiaria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/visitas: la API solo inserta la visita; el trigger de la base de datos
 * (trg_visitas_contador) acumula el total por página y por día.
 */
class SitioVisitasTest extends TestCase
{
    use RefreshDatabase;

    public function test_cada_visita_incrementa_el_contador_de_la_pagina(): void
    {
        $this->postJson('/api/visitas', ['ruta' => '/noticias/abc'])
            ->assertCreated()
            ->assertExactJson(['ruta' => '/noticias/abc', 'total' => 1]);

        $this->postJson('/api/visitas', ['ruta' => '/noticias/abc'])
            ->assertCreated()
            ->assertExactJson(['ruta' => '/noticias/abc', 'total' => 2]);

        $this->postJson('/api/visitas', ['ruta' => '/eventos'])->assertCreated()->assertJsonPath('total', 1);

        $this->assertDatabaseCount('visitas', 3);
        $this->assertSame(2, (int) ContadorVisita::query()->where('ruta', '/noticias/abc')->value('total'));
        $this->assertSame(2, (int) VisitaDiaria::query()->where('ruta', '/noticias/abc')->where('fecha', today()->toDateString())->value('total'));
        $this->assertSame(1, (int) ContadorVisita::query()->where('ruta', '/eventos')->value('total'));
    }

    public function test_continua_el_total_acumulado_de_la_pagina(): void
    {
        ContadorVisita::query()->create(['ruta' => '/malla-curricular', 'total' => 40]);

        $this->postJson('/api/visitas', ['ruta' => '/malla-curricular'])->assertCreated()->assertJsonPath('total', 41);
    }

    public function test_la_ruta_se_normaliza(): void
    {
        foreach ([
            'noticias/abc' => '/noticias/abc',
            '/noticias/abc/?page=2#comentarios' => '/noticias/abc',
            '/buscar?q=gestion' => '/buscar',
            '//galeria//aniversario/' => '/galeria/aniversario',
            'http://localhost:8000/eventos/congreso?x=1' => '/eventos/congreso',
            '/' => '/',
        ] as $enviada => $esperada) {
            $this->postJson('/api/visitas', ['ruta' => $enviada])->assertCreated()->assertJsonPath('ruta', $esperada);
        }

        $this->assertSame(2, (int) ContadorVisita::query()->where('ruta', '/noticias/abc')->value('total'));
    }

    public function test_valida_la_ruta_con_mensajes_en_espanol(): void
    {
        $this->postJson('/api/visitas', [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.ruta.0', 'El campo ruta es obligatorio.');

        $this->postJson('/api/visitas', ['ruta' => '/'.str_repeat('a', 191)])
            ->assertUnprocessable()
            ->assertJsonPath('errors.ruta.0', 'El campo ruta no debe tener más de 191 caracteres.');

        $this->assertDatabaseCount('visitas', 0);
    }

    public function test_registra_ip_agente_y_usuario_opcional(): void
    {
        $this->withHeader('User-Agent', 'Navegador de prueba')->postJson('/api/visitas', ['ruta' => '/contacto'])->assertCreated();
        $this->assertDatabaseHas('visitas', ['ruta' => '/contacto', 'user_id' => null, 'ip' => '127.0.0.1', 'agente' => 'Navegador de prueba']);

        $usuario = User::factory()->create();

        $this->withToken($usuario->createToken('spa')->plainTextToken)->postJson('/api/visitas', ['ruta' => '/cuenta'])->assertCreated();
        $this->assertDatabaseHas('visitas', ['ruta' => '/cuenta', 'user_id' => $usuario->id]);
    }
}

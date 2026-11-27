<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cada límite de solicitudes (throttle) usa su propio contador: navegar por el sitio
 * (contador de visitas, sugerencias) no debe bloquear el envío de formularios.
 */
class LimitesSolicitudesTest extends TestCase
{
    use RefreshDatabase;

    public function test_navegar_varias_paginas_no_bloquea_los_formularios(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->postJson('/api/visitas', ['ruta' => "/pagina-$i"])->assertStatus(201);
            $this->getJson('/api/buscar/sugerencias?q=ad')->assertOk();
        }

        // El formulario responde por validación (422), no por límite de solicitudes (429).
        $this->postJson('/api/contacto', [])->assertStatus(422);
        $this->postJson('/api/auth/olvide-contrasena', [])->assertStatus(422);
    }

    public function test_el_limite_de_formularios_sigue_activo(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/contacto', [])->assertStatus(422);
        }

        $this->postJson('/api/contacto', [])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Demasiadas solicitudes. Espere un momento e intente de nuevo.');
    }
}

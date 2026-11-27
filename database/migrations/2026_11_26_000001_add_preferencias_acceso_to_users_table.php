<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU1 - Preferencias de visualización y control de acceso de cada cuenta.
 *
 * - preferencias: tema (ninos | jovenes | adultos), modo (auto | dia | noche), fuente (%) y contraste.
 * - ultimo_acceso / intentos_fallidos: los mantiene el trigger trg_inicios_sesion_usuario
 *   a partir de la tabla inicios_sesion (la aplicación no los modifica directamente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('preferencias')->nullable()->after('activo');
            $table->timestamp('ultimo_acceso')->nullable()->after('preferencias');
            $table->unsignedInteger('intentos_fallidos')->default(0)->after('ultimo_acceso');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['preferencias', 'ultimo_acceso', 'intentos_fallidos']);
        });
    }
};

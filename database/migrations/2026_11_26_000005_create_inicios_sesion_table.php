<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU1 - Bitácora de autenticación: cada intento de inicio de sesión (exitoso, fallido o bloqueado).
 * El trigger trg_inicios_sesion_usuario actualiza users.ultimo_acceso e users.intentos_fallidos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inicios_sesion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('email', 150);
            $table->boolean('exitoso')->index();
            $table->string('motivo', 150)->nullable();
            $table->string('ip', 45)->nullable()->index();
            $table->string('agente', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inicios_sesion');
    }
};

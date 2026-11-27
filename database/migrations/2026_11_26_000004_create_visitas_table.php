<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU8 - Registro crudo de páginas vistas en la SPA (una fila por visita).
 * El trigger trg_visitas_contador acumula cada fila en contador_visitas
 * (total histórico por página) y en visitas_diarias (total por día y página).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitas', function (Blueprint $table) {
            $table->id();
            $table->string('ruta', 191)->index();
            $table->foreignId('user_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('agente', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas');
    }
};

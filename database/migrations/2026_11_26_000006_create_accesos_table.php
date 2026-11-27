<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU8 - Tráfico de la API REST: una fila por solicitud atendida (usuario, IP, recurso, estado y duración)
 * y el contador acumulado por recurso, que mantiene el trigger trg_accesos_contador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accesos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('ip', 45)->nullable()->index();
            $table->string('metodo', 10);
            $table->string('ruta', 191);
            $table->string('recurso', 150)->index();
            $table->unsignedSmallInteger('estado');
            $table->unsignedInteger('duracion_ms')->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('contador_recursos', function (Blueprint $table) {
            $table->id();
            $table->string('recurso', 150);
            $table->string('metodo', 10);
            $table->string('ruta', 191);
            $table->unsignedBigInteger('total')->default(0);
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamps();
            $table->unique(['recurso', 'metodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contador_recursos');
        Schema::dropIfExists('accesos');
    }
};

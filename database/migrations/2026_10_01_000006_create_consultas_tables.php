<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU7 - Consultas mediante Formulario de Contacto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_consulta', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->string('correo_destino', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_consulta_id')->nullable()->index()->constrained('categorias_consulta')->nullOnDelete();
            $table->string('nombre', 150);
            $table->string('correo', 150);
            $table->string('telefono', 30)->nullable();
            $table->text('mensaje');
            $table->string('estado', 20)->default('nueva');
            $table->text('respuesta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
        Schema::dropIfExists('categorias_consulta');
    }
};

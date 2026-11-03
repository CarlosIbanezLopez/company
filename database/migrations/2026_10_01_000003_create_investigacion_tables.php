<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU4 - Investigaciones, Ediciones de la Revista INNOVAR y Repositorio Documental.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_documento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->string('slug', 100)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_documento_id')->constrained('categorias_documento')->restrictOnDelete();
            $table->string('titulo', 200);
            $table->string('descripcion', 500)->nullable();
            $table->string('archivo')->nullable();
            $table->date('fecha_publicacion')->nullable();
            $table->unsignedInteger('descargas')->default(0);
            $table->boolean('publicado')->default(true);
            $table->timestamps();
        });

        Schema::create('lineas_investigacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('proyectos_investigacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_investigacion_id')->nullable()->constrained('lineas_investigacion')->nullOnDelete();
            $table->string('tipo', 20)->default('proyecto')->index();
            $table->string('titulo', 200);
            $table->string('slug', 220)->unique();
            $table->text('descripcion')->nullable();
            $table->string('responsables', 255)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('estado', 20)->default('en_curso');
            $table->text('resultados')->nullable();
            $table->string('enlace_publicacion', 255)->nullable();
            $table->string('enlace_repositorio', 255)->nullable();
            $table->string('imagen')->nullable();
            $table->boolean('publicado')->default(true);
            $table->timestamps();
        });

        Schema::create('revista_ediciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero')->unique();
            $table->string('titulo', 200);
            $table->date('fecha_publicacion')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('portada')->nullable();
            $table->string('archivo_pdf')->nullable();
            $table->boolean('publicado')->default(true);
            $table->timestamps();
        });

        Schema::create('revista_articulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revista_edicion_id')->constrained('revista_ediciones')->cascadeOnDelete();
            $table->string('titulo', 255);
            $table->string('autores', 255);
            $table->text('resumen')->nullable();
            $table->string('palabras_clave', 255)->nullable();
            $table->string('archivo_pdf')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revista_articulos');
        Schema::dropIfExists('revista_ediciones');
        Schema::dropIfExists('proyectos_investigacion');
        Schema::dropIfExists('lineas_investigacion');
        Schema::dropIfExists('documentos');
        Schema::dropIfExists('categorias_documento');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU6 - Difusión de Noticias, Comunicados, Eventos Académicos y Galería Multimedia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_noticia', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });

        Schema::create('noticias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_noticia_id')->nullable()->index()->constrained('categorias_noticia')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('titulo', 200);
            $table->string('slug', 220)->unique();
            $table->string('resumen', 300)->nullable();
            $table->longText('contenido')->nullable();
            $table->string('imagen')->nullable();
            $table->date('fecha_publicacion');
            $table->string('estado', 20)->default('publicado');
            $table->boolean('destacado')->default(false);
            $table->timestamps();
        });

        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->string('slug', 220)->unique();
            $table->string('tipo', 30)->default('conferencia');
            $table->text('descripcion')->nullable();
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->string('lugar', 200)->nullable();
            $table->string('afiche')->nullable();
            $table->string('enlace_inscripcion', 255)->nullable();
            $table->boolean('publicado')->default(true);
            $table->timestamps();
        });

        Schema::create('albumes', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->string('slug', 220)->unique();
            $table->string('descripcion', 500)->nullable();
            $table->date('fecha')->nullable();
            $table->string('portada')->nullable();
            $table->boolean('publicado')->default(true);
            $table->timestamps();
        });

        Schema::create('fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->index()->constrained('albumes')->cascadeOnDelete();
            $table->string('imagen');
            $table->string('descripcion', 255)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->nullable()->index()->constrained('albumes')->nullOnDelete();
            $table->string('titulo', 200);
            $table->string('url', 255);
            $table->string('descripcion', 500)->nullable();
            $table->boolean('publicado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
        Schema::dropIfExists('fotos');
        Schema::dropIfExists('albumes');
        Schema::dropIfExists('eventos');
        Schema::dropIfExists('noticias');
        Schema::dropIfExists('categorias_noticia');
    }
};

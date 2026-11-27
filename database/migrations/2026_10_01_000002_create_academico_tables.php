<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU3 - Información Académica, Malla Curricular Interactiva y Plantel Docente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paginas', function (Blueprint $table) {
            $table->id();
            $table->string('seccion', 30)->index();
            $table->string('slug', 120)->unique();
            $table->string('titulo', 150);
            $table->string('resumen', 255)->nullable();
            $table->longText('contenido')->nullable();
            $table->string('imagen')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('publicado')->default(true);
            $table->timestamps();
        });

        Schema::create('docentes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('tipo', 20)->default('docente');
            $table->string('cargo', 120)->nullable();
            $table->string('formacion', 255)->nullable();
            $table->string('especialidad', 150)->nullable();
            $table->string('materias', 255)->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('foto')->nullable();
            $table->boolean('mostrar_correo')->default(false);
            $table->boolean('mostrar_formacion')->default(true);
            $table->boolean('visible')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('materias', function (Blueprint $table) {
            $table->id();
            $table->string('sigla', 20)->unique();
            $table->string('nombre', 150);
            $table->unsignedTinyInteger('semestre');
            $table->unsignedTinyInteger('creditos')->default(0);
            $table->unsignedTinyInteger('horas_teoricas')->default(0);
            $table->unsignedTinyInteger('horas_practicas')->default(0);
            $table->string('tipo', 20)->default('obligatoria');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('materia_prerequisito', function (Blueprint $table) {
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();
            $table->foreignId('prerequisito_id')->index()->constrained('materias')->cascadeOnDelete();
            $table->primary(['materia_id', 'prerequisito_id']);
        });

        Schema::create('actividades_calendario', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 150);
            $table->string('descripcion', 255)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('tipo', 30)->default('academico');
            $table->string('gestion', 20);
            $table->timestamps();
        });

        Schema::create('preguntas_frecuentes', function (Blueprint $table) {
            $table->id();
            $table->string('pregunta', 255);
            $table->text('respuesta');
            $table->string('categoria', 60)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas_frecuentes');
        Schema::dropIfExists('actividades_calendario');
        Schema::dropIfExists('materia_prerequisito');
        Schema::dropIfExists('materias');
        Schema::dropIfExists('docentes');
        Schema::dropIfExists('paginas');
    }
};

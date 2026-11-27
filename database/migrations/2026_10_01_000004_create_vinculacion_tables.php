<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU5 - Vinculación, Empresa Junior INNOVA y Seguimiento a Graduados (Alumni).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convenios', function (Blueprint $table) {
            $table->id();
            $table->string('institucion', 200);
            $table->string('tipo', 30)->default('convenio');
            $table->text('finalidad')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('logo')->nullable();
            $table->string('estado', 20)->default('vigente');
            $table->boolean('publicado')->default(true);
            $table->timestamps();
        });

        Schema::create('innova_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('innova_integrantes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('cargo', 100)->nullable();
            $table->string('semestre', 30)->nullable();
            $table->string('foto')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('innova_proyectos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->string('cliente', 150)->nullable();
            $table->text('descripcion')->nullable();
            $table->date('fecha')->nullable();
            $table->string('imagen')->nullable();
            $table->boolean('publicado')->default(true);
            $table->timestamps();
        });

        Schema::create('innova_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('innova_servicio_id')->nullable()->index()->constrained('innova_servicios')->nullOnDelete();
            $table->string('empresa', 150);
            $table->string('contacto', 150);
            $table->string('correo', 150);
            $table->string('telefono', 30)->nullable();
            $table->text('mensaje');
            $table->string('estado', 20)->default('pendiente');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('innova_postulaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('registro', 20);
            $table->string('correo', 150);
            $table->string('telefono', 30)->nullable();
            $table->string('semestre', 30)->nullable();
            $table->text('motivacion');
            $table->string('estado', 20)->default('pendiente');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('graduados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('nombre', 150);
            $table->string('ci', 20);
            $table->string('registro', 20)->nullable();
            $table->unsignedSmallInteger('anio_graduacion');
            $table->string('modalidad_titulacion', 60)->nullable();
            $table->string('situacion_laboral', 30);
            $table->string('empresa', 150)->nullable();
            $table->string('cargo', 120)->nullable();
            $table->string('ciudad', 80)->nullable();
            $table->string('correo', 150);
            $table->string('telefono', 30)->nullable();
            $table->string('linkedin', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduados');
        Schema::dropIfExists('innova_postulaciones');
        Schema::dropIfExists('innova_solicitudes');
        Schema::dropIfExists('innova_proyectos');
        Schema::dropIfExists('innova_integrantes');
        Schema::dropIfExists('innova_servicios');
        Schema::dropIfExists('convenios');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU2 - Gestión de Portal Web y Bitácora de Auditoría.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('accion', 30);
            $table->string('modulo', 60);
            $table->string('descripcion', 500);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('agente', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('etiqueta', 80);
            $table->string('ruta', 120)->nullable();
            $table->string('icono', 40)->nullable();
            $table->string('permiso', 80)->nullable();
            $table->foreignId('padre_id')->nullable()->index()->constrained('menu_items')->nullOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 150);
            $table->string('subtitulo', 255)->nullable();
            $table->string('imagen')->nullable();
            $table->string('enlace', 255)->nullable();
            $table->string('texto_boton', 40)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('destacados', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 120);
            $table->string('descripcion', 255)->nullable();
            $table->string('valor', 40)->nullable();
            $table->string('enlace', 255)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('enlaces_plataforma', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('url', 255);
            $table->string('descripcion', 255)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 80)->unique();
            $table->text('valor')->nullable();
            $table->string('grupo', 40)->default('general');
            $table->timestamps();
        });

        Schema::create('contador_visitas', function (Blueprint $table) {
            $table->id();
            $table->string('ruta', 191)->unique();
            $table->unsignedBigInteger('total')->default(0);
            $table->timestamp('ultima_visita')->nullable();
            $table->timestamps();
        });

        Schema::create('visitas_diarias', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('ruta', 191);
            $table->unsignedInteger('total')->default(0);
            $table->unique(['fecha', 'ruta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas_diarias');
        Schema::dropIfExists('contador_visitas');
        Schema::dropIfExists('configuraciones');
        Schema::dropIfExists('enlaces_plataforma');
        Schema::dropIfExists('destacados');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('bitacora');
    }
};

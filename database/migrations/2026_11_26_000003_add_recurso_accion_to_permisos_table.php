<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU1 - Matriz de control de acceso (RBAC): cada permiso es la combinación de un
 * recurso (noticias, usuarios, ...) y una acción (ver | crear | editar | eliminar).
 * El nombre del permiso sigue siendo único y vale "{recurso}.{accion}".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permisos', function (Blueprint $table) {
            $table->string('recurso', 60)->nullable()->after('nombre');
            $table->string('accion', 20)->nullable()->after('recurso');
            $table->unique(['recurso', 'accion']);
        });
    }

    public function down(): void
    {
        Schema::table('permisos', function (Blueprint $table) {
            $table->dropUnique(['recurso', 'accion']);
            $table->dropColumn(['recurso', 'accion']);
        });
    }
};

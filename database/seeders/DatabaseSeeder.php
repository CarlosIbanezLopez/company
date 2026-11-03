<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Carga los datos iniciales del sistema.
     * Usuario administrador: admin@carrera.test / password
     */
    public function run(): void
    {
        foreach (['banners', 'paginas', 'documentos', 'investigacion', 'revista', 'convenios', 'innova', 'noticias', 'eventos', 'galeria'] as $carpeta) {
            Storage::disk('public')->deleteDirectory($carpeta);
        }

        $this->call([
            SeguridadSeeder::class,
            PortalSeeder::class,
            AcademicoSeeder::class,
            InvestigacionSeeder::class,
            VinculacionSeeder::class,
            DifusionSeeder::class,
            ConsultasSeeder::class,
            VisitasSeeder::class,
        ]);
    }
}

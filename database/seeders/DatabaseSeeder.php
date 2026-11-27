<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Carga los datos iniciales del sistema (contraseña de todas las cuentas: "password"):
     * admin@carrera.test, noticias@carrera.test, revista@carrera.test, graduados@carrera.test,
     * secretaria@carrera.test y graduado@carrera.test (rol Graduado, con ficha).
     * VisitasSeeder genera tráfico de ejemplo; los contadores los calculan los triggers.
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

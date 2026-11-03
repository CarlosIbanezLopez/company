<?php

namespace Database\Seeders;

use App\Models\ContadorVisita;
use App\Models\VisitaDiaria;
use Illuminate\Database\Seeder;

/**
 * CU8 - Visitas de ejemplo de los últimos 30 días para que los reportes muestren datos.
 */
class VisitasSeeder extends Seeder
{
    public function run(): void
    {
        $paginas = [
            '/' => 60, '/malla-curricular' => 25, '/noticias' => 20, '/plantel-docente' => 12, '/biblioteca' => 15,
            '/revista-innovar' => 8, '/eventos' => 10, '/innova' => 6, '/graduados' => 5, '/contacto' => 9,
            '/galeria' => 7, '/institucional/historia' => 4, '/institucional/mision' => 6, '/academico/admision' => 11,
        ];

        foreach ($paginas as $ruta => $promedio) {
            $total = 0;

            for ($d = 29; $d >= 0; $d--) {
                $cantidad = max(0, (int) round($promedio * (0.5 + mt_rand() / mt_getrandmax())));
                VisitaDiaria::query()->create(['fecha' => now()->subDays($d)->toDateString(), 'ruta' => $ruta, 'total' => $cantidad]);
                $total += $cantidad;
            }

            ContadorVisita::query()->create(['ruta' => $ruta, 'total' => $total + $promedio * 20, 'ultima_visita' => now()]);
        }
    }
}

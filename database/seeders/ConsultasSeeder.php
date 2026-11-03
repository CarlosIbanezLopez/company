<?php

namespace Database\Seeders;

use App\Models\CategoriaConsulta;
use App\Models\Consulta;
use Illuminate\Database\Seeder;

/**
 * CU7 - Categorías de consulta y consultas de ejemplo.
 */
class ConsultasSeeder extends Seeder
{
    public function run(): void
    {
        $ids = [];
        foreach (['Admisión e inscripciones', 'Trámites académicos', 'Titulación', 'Revista INNOVAR', 'Empresa Junior INNOVA', 'Otros'] as $nombre) {
            $ids[] = CategoriaConsulta::query()->create(['nombre' => $nombre])->id;
        }

        $consultas = [
            ['Ana Gómez', 'ana@ejemplo.test', 0, '¿Cuándo son las fechas de inscripción para el próximo semestre?', 'nueva'],
            ['Luis Rojas', 'luis@ejemplo.test', 2, 'Quisiera saber los requisitos para la modalidad de trabajo dirigido.', 'en_revision'],
            ['Carla Vaca', 'carla@ejemplo.test', 3, '¿Cuál es la fecha límite para enviar artículos a la revista?', 'respondida'],
            ['Pedro Suárez', 'pedro@ejemplo.test', 1, 'Necesito un certificado de notas, ¿qué debo presentar?', 'nueva'],
        ];

        foreach ($consultas as $i => [$nombre, $correo, $categoria, $mensaje, $estado]) {
            $consulta = Consulta::query()->create(['nombre' => $nombre, 'correo' => $correo, 'categoria_consulta_id' => $ids[$categoria], 'mensaje' => $mensaje, 'estado' => $estado, 'ip' => '127.0.0.1']);
            $consulta->forceFill(['created_at' => now()->subDays($i * 3)])->saveQuietly();
        }
    }
}

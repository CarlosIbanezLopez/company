<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\CategoriaNoticia;
use App\Models\Evento;
use App\Models\Noticia;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * CU6 - Noticias, eventos y galería multimedia (datos de ejemplo).
 */
class DifusionSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [];
        foreach (['Institucional', 'Académico', 'Investigación', 'Convocatorias', 'Comunicados', 'Reconocimientos'] as $nombre) {
            $categorias[$nombre] = CategoriaNoticia::query()->create(['nombre' => $nombre, 'slug' => Str::slug($nombre)])->id;
        }

        $autor = User::query()->where('email', 'noticias@carrera.test')->value('id');

        $noticias = [
            ['Inicio del segundo semestre 2026', 'Académico', 'Las clases del segundo semestre comenzaron con normalidad en todos los grupos.', true, 20],
            ['Se publicó la edición N° 3 de la Revista INNOVAR', 'Investigación', 'La nueva edición está dedicada a la transformación digital de las organizaciones.', true, 35],
            ['Convocatoria a auxiliares de docencia', 'Convocatorias', 'Estudiantes de 5° semestre en adelante pueden postular hasta la fecha indicada.', false, 12],
            ['Estudiantes de INNOVA concluyen consultoría a PyME local', 'Institucional', 'El equipo entregó un plan de negocios a un emprendimiento de la ciudad.', true, 8],
            ['Comunicado: horario de atención en Secretaría', 'Comunicados', 'Se informa el nuevo horario de atención al público de Secretaría de Carrera.', false, 4],
            ['Feria empresarial de la Carrera', 'Institucional', 'Estudiantes expondrán sus proyectos de emprendimiento en la feria anual.', false, 2],
            ['Taller de elaboración de perfiles de tesis', 'Académico', 'Taller gratuito para estudiantes de Taller de Grado I.', false, 50],
            ['Reconocimiento a los mejores promedios de la gestión', 'Reconocimientos', 'La Carrera reconoció a los estudiantes con mejor rendimiento académico.', false, 28],
            ['Seguimiento a graduados: actualice sus datos', 'Comunicados', 'Invitamos a los titulados a registrar su situación laboral en el portal.', false, 60],
        ];

        foreach ($noticias as $i => [$titulo, $categoria, $resumen, $destacado, $dias]) {
            Noticia::query()->create([
                'categoria_noticia_id' => $categorias[$categoria],
                'user_id' => $autor,
                'titulo' => $titulo,
                'slug' => Str::slug($titulo),
                'resumen' => $resumen,
                'contenido' => "{$resumen}\n\nLa Dirección de Carrera informa a la comunidad universitaria los detalles de esta actividad. Para más información puede comunicarse mediante el formulario de contacto.\n\n> Noticia de ejemplo generada al instalar el sistema.",
                'imagen' => Recursos::imagen('noticias', $titulo, 1200, 675, $i),
                'fecha_publicacion' => now()->subDays($dias)->toDateString(),
                'estado' => 'publicado',
                'destacado' => $destacado,
            ]);
        }

        Noticia::query()->create([
            'categoria_noticia_id' => $categorias['Comunicados'], 'user_id' => $autor, 'titulo' => 'Borrador: cronograma de defensas',
            'slug' => 'borrador-cronograma-de-defensas', 'resumen' => 'Pendiente de aprobación.', 'contenido' => 'Borrador.',
            'fecha_publicacion' => now()->toDateString(), 'estado' => 'borrador', 'destacado' => false,
        ]);

        $eventos = [
            ['Conferencia: Liderazgo en tiempos de cambio', 'conferencia', 10, '19:00', '21:00', 'Auditorio de la Facultad'],
            ['Seminario de finanzas para emprendedores', 'seminario', 18, '18:30', '21:30', 'Aula magna'],
            ['Feria empresarial de la Carrera', 'feria', 25, '09:00', '18:00', 'Patio central de la Facultad'],
            ['Taller de marketing digital', 'taller', -15, '18:00', '20:00', 'Laboratorio de computación'],
            ['Congreso de estudiantes de Administración', 'congreso', -40, '08:30', '18:00', 'Centro de convenciones (ejemplo)'],
            ['Conferencia: Economía regional 2026', 'conferencia', -70, '19:00', '21:00', 'Auditorio de la Facultad'],
        ];

        foreach ($eventos as $i => [$titulo, $tipo, $dias, $inicio, $fin, $lugar]) {
            Evento::query()->create([
                'titulo' => $titulo,
                'slug' => Str::slug($titulo),
                'tipo' => $tipo,
                'descripcion' => "Actividad académica organizada por la Carrera de Administración de Empresas.\n\nDirigido a estudiantes, docentes, graduados y público en general.",
                'fecha' => now()->addDays($dias)->toDateString(),
                'hora_inicio' => $inicio,
                'hora_fin' => $fin,
                'lugar' => $lugar,
                'afiche' => Recursos::imagen('eventos', $titulo, 800, 1000, $i + 1, 'Carrera de Administración de Empresas', true),
                'enlace_inscripcion' => $dias > 0 ? 'https://forms.gle/' : null,
                'publicado' => true,
            ]);
        }

        $albumes = [
            ['Feria empresarial 2025', -300],
            ['Congreso de estudiantes', -40],
            ['Entrega de títulos', -120],
        ];

        foreach ($albumes as $i => [$titulo, $dias]) {
            $album = Album::query()->create([
                'titulo' => $titulo,
                'slug' => Str::slug($titulo),
                'descripcion' => 'Registro fotográfico de la actividad.',
                'fecha' => now()->addDays($dias)->toDateString(),
                'publicado' => true,
            ]);

            for ($f = 1; $f <= 6; $f++) {
                $album->fotos()->create([
                    'imagen' => Recursos::imagen('galeria/'.$album->id, $titulo.' - foto '.$f, 1200, 800, $i + $f),
                    'descripcion' => $titulo.' - fotografía '.$f,
                    'orden' => $f,
                ]);
            }

            $album->update(['portada' => $album->fotos()->orderBy('orden')->value('imagen')]);
        }

        Video::query()->create(['titulo' => 'Presentación de la Carrera', 'url' => 'https://www.youtube.com/', 'descripcion' => 'Video institucional (enlace de ejemplo).', 'publicado' => true]);
        Video::query()->create(['titulo' => 'Resumen del congreso de estudiantes', 'url' => 'https://www.youtube.com/', 'album_id' => Album::query()->where('slug', 'congreso-de-estudiantes')->value('id'), 'publicado' => true]);
    }
}

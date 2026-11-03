<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Configuracion;
use App\Models\Destacado;
use App\Models\EnlacePlataforma;
use Illuminate\Database\Seeder;

/**
 * CU2 - Datos iniciales de la página principal, contacto y redes sociales.
 * Los valores son de ejemplo y se editan desde el panel (Portal web).
 */
class PortalSeeder extends Seeder
{
    public function run(): void
    {
        $config = [
            'portal' => [
                'nombre_carrera' => 'Administración de Empresas',
                'nombre_facultad' => 'Facultad de Ciencias Económicas y Empresariales',
                'nombre_universidad' => 'Universidad Autónoma Gabriel René Moreno',
                'presentacion_titulo' => 'Bienvenidos a la Carrera de Administración de Empresas',
                'presentacion_texto' => "Formamos profesionales capaces de **planificar, organizar, dirigir y controlar** organizaciones públicas y privadas, con visión emprendedora, ética y compromiso con el desarrollo de la región.\n\nEn este portal encontrará la información institucional y académica de la Carrera, la malla curricular, el plantel docente, la Revista INNOVAR, la Empresa Junior INNOVA y las últimas noticias y actividades.",
                'presentacion_autor' => 'Dirección de Carrera',
                'video_presentacion' => '',
            ],
            'contacto' => [
                'direccion' => 'Ciudad Universitaria UAGRM, Av. Busch, Santa Cruz de la Sierra, Bolivia',
                'mapa_url' => 'https://www.google.com/maps?q=Universidad+Aut%C3%B3noma+Gabriel+Ren%C3%A9+Moreno+Santa+Cruz&output=embed',
                'telefonos' => '(591-3) 000-0000',
                'whatsapp' => '',
                'correo' => 'administracion@carrera.test',
                'correo_notificaciones' => 'consultas@carrera.test',
                'horario' => 'Lunes a viernes de 08:00 a 12:00 y de 14:30 a 18:30',
                'facebook' => 'https://www.facebook.com/',
                'instagram' => 'https://www.instagram.com/',
                'youtube' => 'https://www.youtube.com/',
                'tiktok' => '',
                'linkedin' => '',
            ],
        ];

        foreach ($config as $grupo => $valores) {
            foreach ($valores as $clave => $valor) {
                Configuracion::guardar($clave, $valor, $grupo);
            }
        }

        $banners = [
            ['Formando líderes empresariales', 'Conoce el perfil profesional y la malla curricular de la Carrera', '/malla-curricular', 'Ver malla curricular'],
            ['Revista Académica INNOVAR', 'Investigación y producción académica de docentes y estudiantes', '/revista-innovar', 'Leer la revista'],
            ['Empresa Junior INNOVA', 'Servicios de consultoría realizados por estudiantes de la Carrera', '/innova', 'Solicitar un servicio'],
        ];

        foreach ($banners as $i => [$titulo, $subtitulo, $enlace, $boton]) {
            Banner::query()->create([
                'titulo' => $titulo,
                'subtitulo' => $subtitulo,
                'imagen' => Recursos::imagen('banners', $titulo, 1600, 600, $i),
                'enlace' => $enlace,
                'texto_boton' => $boton,
                'orden' => $i + 1,
                'activo' => true,
            ]);
        }

        $destacados = [
            ['10', 'Semestres', 'Plan de estudios por semestres con modalidad de graduación', '/malla-curricular'],
            ['55+', 'Materias', 'Asignaturas organizadas en una malla interactiva', '/malla-curricular'],
            ['3', 'Ediciones INNOVAR', 'Números publicados de la revista académica', '/revista-innovar'],
            ['5', 'Convenios', 'Alianzas con instituciones y empresas', '/vinculacion'],
        ];

        foreach ($destacados as $i => [$valor, $titulo, $descripcion, $enlace]) {
            Destacado::query()->create(['valor' => $valor, 'titulo' => $titulo, 'descripcion' => $descripcion, 'enlace' => $enlace, 'orden' => $i + 1]);
        }

        $enlaces = [
            ['Portal UAGRM', 'https://www.uagrm.edu.bo/', 'Sitio oficial de la Universidad'],
            ['Sistema de inscripciones', 'https://www.uagrm.edu.bo/', 'Inscripción y adición de materias'],
            ['Biblioteca UAGRM', 'https://www.uagrm.edu.bo/', 'Catálogo bibliográfico de la Universidad'],
            ['Aula virtual', 'https://www.uagrm.edu.bo/', 'Plataforma de clases virtuales'],
        ];

        foreach ($enlaces as $i => [$nombre, $url, $descripcion]) {
            EnlacePlataforma::query()->create(['nombre' => $nombre, 'url' => $url, 'descripcion' => $descripcion, 'orden' => $i + 1]);
        }
    }
}

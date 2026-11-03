<?php

namespace Database\Seeders;

use App\Models\CategoriaDocumento;
use App\Models\Documento;
use App\Models\LineaInvestigacion;
use App\Models\ProyectoInvestigacion;
use App\Models\RevistaEdicion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * CU4 - Biblioteca digital, investigación y Revista INNOVAR (datos de ejemplo).
 */
class InvestigacionSeeder extends Seeder
{
    public function run(): void
    {
        $documentos = [
            'Reglamentos' => ['Normativas que rigen la vida académica', ['Reglamento de Graduación', 'Reglamento de la Empresa Junior INNOVA', 'Reglamento de Prácticas Empresariales']],
            'Instructivos' => ['Guías para trámites y procesos', ['Instructivo de inscripción de materias', 'Guía para la presentación del perfil de tesis']],
            'Formularios' => ['Formularios descargables', ['Formulario de convalidación de materias', 'Formulario de solicitud de modalidad de titulación', 'Formulario de solicitud de certificado de notas']],
            'Convocatorias' => ['Convocatorias vigentes y pasadas', ['Convocatoria a auxiliares de docencia 2-2026', 'Convocatoria a artículos - Revista INNOVAR N° 4']],
        ];

        foreach ($documentos as $categoria => [$descripcion, $titulos]) {
            $cat = CategoriaDocumento::query()->create(['nombre' => $categoria, 'slug' => Str::slug($categoria), 'descripcion' => $descripcion]);

            foreach ($titulos as $i => $titulo) {
                Documento::query()->create([
                    'categoria_documento_id' => $cat->id,
                    'titulo' => $titulo,
                    'descripcion' => "Documento de la categoría {$categoria}.",
                    'archivo' => Recursos::pdf('documentos', $titulo),
                    'fecha_publicacion' => now()->subDays(random_int(5, 200))->toDateString(),
                    'descargas' => random_int(3, 180),
                    'publicado' => true,
                ]);
            }
        }

        $lineas = [
            'Gestión empresarial y estrategia' => 'Estudios sobre dirección, planificación estratégica y competitividad de las organizaciones de la región.',
            'Emprendimiento e innovación' => 'Creación de empresas, ecosistemas emprendedores e innovación en PyMEs.',
            'Finanzas y mercados' => 'Finanzas corporativas, inclusión financiera y mercados de capitales.',
            'Gestión pública y responsabilidad social' => 'Administración de entidades públicas, transparencia y responsabilidad social empresarial.',
        ];

        $ids = [];
        foreach ($lineas as $nombre => $descripcion) {
            $ids[] = LineaInvestigacion::query()->create(['nombre' => $nombre, 'descripcion' => $descripcion])->id;
        }

        $proyectos = [
            ['proyecto', 'Diagnóstico de la gestión en PyMEs comerciales de Santa Cruz', 0, 'finalizado', '2025-03-01', '2025-12-15', 'Equipo docente de la Carrera'],
            ['proyecto', 'Seguimiento laboral de graduados 2020-2025', 0, 'finalizado', '2025-06-01', '2026-03-30', 'Coordinación de Graduados'],
            ['pia', 'PIA: Análisis de costos en microempresas del mercado Los Pozos', 2, 'en_curso', '2026-08-17', null, 'Estudiantes de Contabilidad de Costos'],
            ['pia', 'PIA: Plan de marketing para emprendimientos universitarios', 1, 'finalizado', '2026-03-01', '2026-07-10', 'Estudiantes de Marketing II'],
            ['semillero', 'Semillero de investigación en emprendimiento e innovación', 1, 'en_curso', '2026-02-10', null, 'Docentes investigadores y estudiantes de INNOVA'],
            ['semillero', 'Semillero de finanzas y mercados', 2, 'en_curso', '2026-04-01', null, 'Coordinación de Investigación'],
            ['produccion', 'Transparencia en la gestión municipal (artículo)', 3, 'finalizado', '2025-08-01', '2026-01-15', 'Equipo de investigación de gestión pública'],
        ];

        foreach ($proyectos as [$tipo, $titulo, $linea, $estado, $inicio, $fin, $responsables]) {
            ProyectoInvestigacion::query()->create([
                'tipo' => $tipo,
                'linea_investigacion_id' => $ids[$linea],
                'titulo' => $titulo,
                'slug' => Str::slug($titulo),
                'descripcion' => "Proyecto de investigación de la Carrera.\n\n**Objetivo:** analizar la problemática planteada y proponer recomendaciones aplicables.\n\n> Descripción de ejemplo.",
                'responsables' => $responsables,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'estado' => $estado,
                'resultados' => $estado === 'finalizado' ? 'Informe final y artículo publicado en la Revista INNOVAR.' : null,
                'imagen' => Recursos::imagen('investigacion', $titulo, 1200, 675),
                'publicado' => true,
            ]);
        }

        $ediciones = [
            [1, 'Gestión y competitividad regional', '2024-11-20'],
            [2, 'Emprendimiento e innovación en Bolivia', '2025-06-15'],
            [3, 'Transformación digital de las organizaciones', '2026-05-30'],
        ];

        foreach ($ediciones as [$numero, $titulo, $fecha]) {
            $edicion = RevistaEdicion::query()->create([
                'numero' => $numero,
                'titulo' => $titulo,
                'fecha_publicacion' => $fecha,
                'descripcion' => "Edición N° {$numero} de la Revista Académica INNOVAR dedicada a: {$titulo}.",
                'portada' => Recursos::imagen('revista', 'INNOVAR N° '.$numero, 600, 820, $numero, $titulo, true),
                'archivo_pdf' => Recursos::pdf('revista', 'Revista INNOVAR N° '.$numero),
                'publicado' => true,
            ]);

            foreach (['Análisis de', 'Propuesta de', 'Estudio de caso:'] as $i => $prefijo) {
                $tituloArticulo = $prefijo.' '.mb_strtolower($titulo).' en el contexto cruceño';
                $edicion->articulos()->create([
                    'titulo' => $tituloArticulo,
                    'autores' => ['Docente investigador; Estudiante de la Carrera', 'Equipo de investigación INNOVAR', 'Graduado de la Carrera'][$i],
                    'resumen' => 'Resumen de ejemplo del artículo. Reemplace este texto por el resumen oficial.',
                    'palabras_clave' => 'gestión, innovación, empresa, Santa Cruz',
                    'archivo_pdf' => Recursos::pdf('revista/articulos', $tituloArticulo),
                    'orden' => $i + 1,
                ]);
            }
        }
    }
}

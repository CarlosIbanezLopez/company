<?php

namespace Database\Seeders;

use App\Models\ActividadCalendario;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Pagina;
use App\Models\PreguntaFrecuente;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * CU3 - Contenido institucional y académico, plantel docente, malla curricular,
 * calendario y preguntas frecuentes. Textos de ejemplo para reemplazar por los oficiales.
 */
class AcademicoSeeder extends Seeder
{
    public function run(): void
    {
        $this->paginas();
        $this->docentes();
        $this->malla();
        $this->calendario();
        $this->preguntas();
    }

    private function paginas(): void
    {
        $paginas = [
            // RF-02 Contenido institucional
            ['institucional', 'Historia', 'Antecedentes históricos de la Carrera', "La Carrera de Administración de Empresas forma parte de la Facultad de Ciencias Económicas y Empresariales (FCEE) de la Universidad Autónoma Gabriel René Moreno.\n\nEntre sus iniciativas actuales se encuentran el Rediseño Curricular, el Proyecto de Investigación en Aula (PIA), los Semilleros de Investigación, la Empresa Junior INNOVA, la Revista Académica Institucional INNOVAR y el seguimiento a graduados.\n\nDesde su creación ha formado profesionales que hoy dirigen empresas, instituciones públicas y emprendimientos propios en Santa Cruz y en todo el país.\n\n> Texto de ejemplo: reemplace este contenido con la reseña histórica oficial de la Carrera."],
            ['institucional', 'Misión', 'Razón de ser de la Carrera', "Formar profesionales en Administración de Empresas con sólida base científica, técnica y humanística, capaces de gestionar organizaciones de manera eficiente, ética y socialmente responsable, contribuyendo al desarrollo económico y social de la región y del país.\n\n> Texto de ejemplo: reemplace por la misión oficial."],
            ['institucional', 'Visión', 'Horizonte de la Carrera', "Ser una Carrera acreditada y referente a nivel nacional en la formación de administradores, reconocida por la calidad de sus graduados, su producción investigativa y su vinculación con el sector productivo.\n\n> Texto de ejemplo: reemplace por la visión oficial."],
            ['institucional', 'Objetivos', 'Objetivos de la Carrera', "1. Formar administradores con capacidad de análisis, planificación y toma de decisiones.\n2. Promover la investigación aplicada a la realidad empresarial de la región.\n3. Fortalecer la vinculación con empresas e instituciones mediante convenios, prácticas y consultorías.\n4. Fomentar el emprendimiento y la innovación a través de la Empresa Junior INNOVA.\n5. Realizar el seguimiento a graduados para mejorar continuamente el plan de estudios."],
            ['institucional', 'Perfil profesional', 'Competencias del Licenciado en Administración de Empresas', "El Licenciado en Administración de Empresas está capacitado para:\n\n- Diseñar e implementar estrategias de gestión en organizaciones públicas y privadas.\n- Administrar los recursos humanos, financieros, materiales y tecnológicos.\n- Formular, evaluar y gestionar proyectos de inversión.\n- Emprender y dirigir sus propias unidades productivas.\n- Desempeñarse con ética, liderazgo y responsabilidad social."],
            ['institucional', 'Áreas de desempeño laboral', 'Dónde trabaja un administrador de empresas', "- Gerencia general y gerencias de área (finanzas, marketing, recursos humanos, producción).\n- Instituciones públicas y gobiernos municipales y departamentales.\n- Entidades financieras y cooperativas.\n- Consultoría y asesoramiento empresarial.\n- Emprendimiento y gestión de empresas propias.\n- Organizaciones no gubernamentales y de cooperación."],
            ['institucional', 'Estructura organizacional', 'Organigrama de la Carrera', "La Carrera está dirigida por la **Dirección de Carrera**, apoyada por el Consejo de Carrera, la Secretaría Académica, las coordinaciones de investigación, vinculación y graduados, y el plantel docente.\n\n> Cargue la imagen del organigrama oficial desde el panel de administración."],
            // RF-05 Contenido académico
            ['academico', 'Perfil de ingreso', 'Características deseables del postulante', "El postulante a la Carrera debe contar con:\n\n- Título de bachiller.\n- Interés por el mundo de los negocios y la gestión de organizaciones.\n- Habilidades de comunicación, razonamiento lógico y trabajo en equipo.\n- Actitud emprendedora y disposición al aprendizaje continuo."],
            ['academico', 'Perfil de egreso', 'Lo que logra el graduado al concluir la Carrera', 'Al concluir sus estudios, el graduado será capaz de dirigir organizaciones, tomar decisiones basadas en información, formular proyectos y liderar equipos de trabajo con responsabilidad social y visión estratégica.'],
            ['academico', 'Admisión', 'Condiciones y modalidades de admisión', "## Modalidades de ingreso\n\n- Prueba de Suficiencia Académica (PSA).\n- Curso preuniversitario.\n- Admisión especial según reglamento universitario.\n\n## Requisitos generales\n\n1. Título de bachiller (original y fotocopia legalizada).\n2. Cédula de identidad vigente.\n3. Certificado de nacimiento.\n4. Fotografías según formato institucional.\n\n> Verifique las fechas y requisitos vigentes en el calendario académico y en las convocatorias publicadas."],
            ['academico', 'Modalidades de titulación', 'Alternativas para obtener el título', "- **Tesis de grado:** investigación individual sobre un problema del área.\n- **Proyecto de grado:** propuesta aplicada a una organización.\n- **Trabajo dirigido:** trabajo práctico en una institución con convenio.\n- **Examen de grado:** evaluación de conocimientos del plan de estudios.\n- **Graduación por excelencia:** según rendimiento académico.\n- **Diplomado:** según reglamento vigente."],
            ['academico', 'Requisitos y procedimientos', 'Trámites académicos frecuentes', "## Trámites más solicitados\n\n- Certificado de notas.\n- Plan de estudios y programas analíticos.\n- Convalidación de materias.\n- Solicitud de modalidad de titulación.\n\nLos formularios están disponibles en la **Biblioteca digital**, categoría *Formularios*."],
            // RF-08 Revista
            ['revista', 'Revista INNOVAR', 'Presentación de la revista', 'La **Revista Académica INNOVAR** difunde artículos científicos, ensayos y estudios de caso elaborados por docentes, estudiantes y graduados de la Carrera, sobre gestión empresarial, emprendimiento, innovación y economía regional.'],
            ['revista', 'Normas para autores', 'Requisitos para publicar en INNOVAR', "## Requisitos de los artículos\n\n1. Trabajo original e inédito.\n2. Extensión de 8 a 15 páginas, tamaño carta, letra de 12 puntos.\n3. Título, resumen (máximo 250 palabras) y entre 3 y 5 palabras clave.\n4. Referencias bibliográficas en formato APA (7.ª edición).\n5. Enviar el archivo en formato Word al correo de la revista.\n\n## Proceso de evaluación\n\nCada artículo es revisado por el comité editorial y evaluado por pares. El resultado se comunica al autor por correo electrónico."],
            // RF-10 INNOVA
            ['innova', 'Empresa Junior INNOVA', 'Consultoría estudiantil', '**INNOVA** es la Empresa Junior de la Carrera de Administración de Empresas. Está conformada por estudiantes que, bajo la supervisión de docentes, brindan servicios de consultoría a micro, pequeñas y medianas empresas, aplicando lo aprendido en las aulas.'],
            // RF-11 Graduados
            ['graduados', 'Programa Alumni', 'Información para graduados', 'El **Programa Alumni** mantiene el vínculo entre la Carrera y sus titulados. Registre sus datos para recibir información de actividades, cursos de actualización y oportunidades laborales. La información también se utiliza para el seguimiento a titulados y la mejora del plan de estudios.'],
            ['graduados', 'Asociación de graduados', 'Organización de titulados de la Carrera', "La Asociación de Graduados reúne a los profesionales titulados de la Carrera para promover la actualización profesional, el networking y el apoyo a los estudiantes.\n\n> Texto de ejemplo: complete con la información oficial de la asociación."],
            ['graduados', 'Oportunidades de vinculación', 'Empleo, mentoría y formación continua', "- Ofertas laborales y pasantías compartidas por empresas con convenio.\n- Mentoría a estudiantes de últimos semestres.\n- Participación como expositores en conferencias y seminarios.\n- Programas de posgrado y formación continua."],
        ];

        foreach ($paginas as $i => [$seccion, $titulo, $resumen, $contenido]) {
            Pagina::query()->create([
                'seccion' => $seccion,
                'slug' => Str::slug($titulo),
                'titulo' => $titulo,
                'resumen' => $resumen,
                'contenido' => $contenido,
                'imagen' => $titulo === 'Estructura organizacional' ? Recursos::imagen('paginas', 'Organigrama de la Carrera', 1200, 700, 5, 'Imagen de ejemplo', true) : null,
                'orden' => $i + 1,
                'publicado' => true,
            ]);
        }
    }

    private function docentes(): void
    {
        $docentes = [
            ['Lic. Freddy Fuentes', 'autoridad', 'Director de Carrera', 'Licenciado en Administración de Empresas', 'Gestión universitaria', null],
            ['M.Sc. Ana María Rojas', 'autoridad', 'Coordinadora Académica', 'Magíster en Administración de Empresas', 'Planificación estratégica', 'Planificación Estratégica'],
            ['Lic. Carlos Méndez Vargas', 'docente', 'Docente titular', 'Licenciado en Administración, Diplomado en Educación Superior', 'Finanzas', 'Administración Financiera I y II'],
            ['M.Sc. Patricia Suárez', 'docente', 'Docente titular', 'Magíster en Marketing', 'Marketing', 'Marketing I y II, Investigación de Mercados'],
            ['Lic. Jorge Gutiérrez', 'docente', 'Docente', 'Licenciado en Economía', 'Economía', 'Microeconomía, Macroeconomía'],
            ['M.Sc. Lucía Fernández', 'docente', 'Docente', 'Magíster en Gestión del Talento Humano', 'Recursos humanos', 'Recursos Humanos I y II'],
            ['Ing. Roberto Paz', 'docente', 'Docente', 'Ingeniero Industrial, M.Sc. en Operaciones', 'Producción y operaciones', 'Investigación Operativa, Administración de la Producción'],
            ['Lic. Daniela Cuéllar', 'docente', 'Docente', 'Licenciada en Contaduría Pública', 'Contabilidad', 'Contabilidad I, II y Costos'],
        ];

        foreach ($docentes as $i => [$nombre, $tipo, $cargo, $formacion, $especialidad, $materias]) {
            Docente::query()->create([
                'nombre' => $nombre,
                'tipo' => $tipo,
                'cargo' => $cargo,
                'formacion' => $formacion,
                'especialidad' => $especialidad,
                'materias' => $materias,
                'correo' => Str::slug(Str::after($nombre, ' '), '.').'@carrera.test',
                'mostrar_correo' => $i % 3 !== 2,
                'mostrar_formacion' => true,
                'visible' => true,
                'orden' => $i + 1,
            ]);
        }
    }

    private function malla(): void
    {
        // [sigla, nombre, semestre, créditos, prerrequisitos]
        $materias = [
            ['ADM-100', 'Introducción a la Administración', 1, 5, []],
            ['MAT-101', 'Matemática I', 1, 5, []],
            ['CON-101', 'Contabilidad I', 1, 5, []],
            ['ECO-101', 'Introducción a la Economía', 1, 4, []],
            ['MET-101', 'Metodología de la Investigación', 1, 4, []],
            ['LEN-101', 'Comunicación Oral y Escrita', 1, 3, []],
            ['ADM-200', 'Administración I', 2, 5, ['ADM-100']],
            ['MAT-201', 'Matemática II', 2, 5, ['MAT-101']],
            ['CON-201', 'Contabilidad II', 2, 5, ['CON-101']],
            ['ECO-201', 'Microeconomía', 2, 4, ['ECO-101']],
            ['DER-201', 'Derecho Empresarial', 2, 4, []],
            ['INF-201', 'Informática Aplicada', 2, 3, []],
            ['ADM-300', 'Administración II', 3, 5, ['ADM-200']],
            ['EST-301', 'Estadística I', 3, 5, ['MAT-201']],
            ['CON-301', 'Contabilidad de Costos', 3, 5, ['CON-201']],
            ['ECO-301', 'Macroeconomía', 3, 4, ['ECO-201']],
            ['DER-301', 'Derecho Laboral', 3, 4, ['DER-201']],
            ['PSI-301', 'Psicología Organizacional', 3, 3, []],
            ['RRH-401', 'Administración de Recursos Humanos I', 4, 5, ['ADM-300', 'PSI-301']],
            ['EST-401', 'Estadística II', 4, 5, ['EST-301']],
            ['FIN-401', 'Matemática Financiera', 4, 5, ['MAT-201']],
            ['MKT-401', 'Marketing I', 4, 5, ['ADM-300']],
            ['INF-401', 'Sistemas de Información Gerencial', 4, 4, ['INF-201']],
            ['ADM-401', 'Comportamiento Organizacional', 4, 4, ['PSI-301']],
            ['RRH-501', 'Administración de Recursos Humanos II', 5, 5, ['RRH-401']],
            ['IOP-501', 'Investigación Operativa I', 5, 5, ['EST-401']],
            ['FIN-501', 'Administración Financiera I', 5, 5, ['FIN-401', 'CON-301']],
            ['MKT-501', 'Marketing II', 5, 5, ['MKT-401']],
            ['ADM-501', 'Gestión de la Calidad', 5, 4, ['ADM-300']],
            ['TRI-501', 'Sistema Tributario', 5, 4, ['CON-201']],
            ['IOP-601', 'Investigación Operativa II', 6, 5, ['IOP-501']],
            ['FIN-601', 'Administración Financiera II', 6, 5, ['FIN-501']],
            ['PRO-601', 'Administración de la Producción', 6, 5, ['IOP-501']],
            ['MKT-601', 'Investigación de Mercados', 6, 5, ['MKT-501', 'EST-401']],
            ['ADM-601', 'Gestión Pública', 6, 4, ['ADM-300']],
            ['FIN-602', 'Presupuestos', 6, 4, ['CON-301']],
            ['ADM-701', 'Planificación Estratégica', 7, 5, ['ADM-501']],
            ['PRY-701', 'Formulación y Evaluación de Proyectos', 7, 5, ['FIN-601']],
            ['PRO-701', 'Logística y Cadena de Suministro', 7, 5, ['PRO-601']],
            ['COM-701', 'Comercio Internacional', 7, 4, ['ECO-301']],
            ['AUD-701', 'Auditoría Administrativa', 7, 4, ['ADM-501']],
            ['MET-701', 'Seminario de Investigación', 7, 4, ['MET-101', 'EST-401']],
            ['PRY-801', 'Gestión de Proyectos', 8, 5, ['PRY-701']],
            ['EMP-801', 'Emprendimiento e Innovación', 8, 5, ['ADM-701']],
            ['ADM-801', 'Dirección Estratégica', 8, 5, ['ADM-701']],
            ['ADM-802', 'Ética y Responsabilidad Social Empresarial', 8, 3, []],
            ['TG-801', 'Taller de Grado I', 8, 5, ['MET-701']],
            ['ELE-801', 'Electiva I', 8, 3, []],
            ['TG-901', 'Taller de Grado II', 9, 5, ['TG-801']],
            ['ADM-901', 'Simulación Gerencial', 9, 5, ['ADM-801']],
            ['PRA-901', 'Práctica Empresarial', 9, 5, ['PRY-801']],
            ['NEG-901', 'Negociación y Resolución de Conflictos', 9, 4, ['RRH-501']],
            ['ELE-901', 'Electiva II', 9, 3, ['ELE-801']],
            ['GRA-1000', 'Modalidad de Graduación', 10, 10, ['TG-901', 'PRA-901']],
        ];

        foreach ($materias as [$sigla, $nombre, $semestre, $creditos]) {
            Materia::query()->create([
                'sigla' => $sigla,
                'nombre' => $nombre,
                'semestre' => $semestre,
                'creditos' => $creditos,
                'horas_teoricas' => min(4, $creditos),
                'horas_practicas' => max(0, $creditos - 3),
                'tipo' => str_starts_with($sigla, 'ELE') ? 'electiva' : ($sigla === 'GRA-1000' ? 'modalidad' : 'obligatoria'),
                'descripcion' => "Asignatura del {$semestre}° semestre. Contenido mínimo de ejemplo: reemplazar por el programa analítico oficial.",
            ]);
        }

        // Id de cada materia por sigla (una sigla de prerrequisito mal escrita detiene el seeder).
        $ids = Materia::query()->pluck('id', 'sigla')->all();

        foreach ($materias as [$sigla, , , , $pre]) {
            if ($pre) {
                Materia::query()->where('sigla', $sigla)->firstOrFail()->prerequisitos()->sync(array_map(fn ($p) => $ids[$p], $pre));
            }
        }
    }

    private function calendario(): void
    {
        $actividades = [
            ['Inscripción de materias', 'inscripciones', '2026-08-03', '2026-08-14', 'Inscripción regular por sistema'],
            ['Inicio de clases', 'clases', '2026-08-17', null, 'Inicio de clases del segundo semestre'],
            ['Adición y retiro de materias', 'inscripciones', '2026-08-24', '2026-08-28', null],
            ['Primer parcial', 'examenes', '2026-09-21', '2026-09-26', null],
            ['Feriado - Aniversario de Santa Cruz', 'feriado', '2026-09-24', null, 'Sin actividades académicas'],
            ['Segundo parcial', 'examenes', '2026-10-26', '2026-10-31', null],
            ['Feria empresarial de la Carrera', 'academico', '2026-11-12', '2026-11-13', 'Exposición de proyectos de estudiantes'],
            ['Exámenes finales', 'examenes', '2026-11-30', '2026-12-05', null],
            ['Segunda instancia', 'examenes', '2026-12-09', '2026-12-12', null],
            ['Defensas de grado', 'titulacion', '2026-12-14', '2026-12-18', 'Según cronograma de la Comisión de Graduación'],
        ];

        foreach ($actividades as [$titulo, $tipo, $inicio, $fin, $descripcion]) {
            ActividadCalendario::query()->create(['titulo' => $titulo, 'tipo' => $tipo, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'descripcion' => $descripcion, 'gestion' => '2-2026']);
        }
    }

    private function preguntas(): void
    {
        $preguntas = [
            ['Admisión', '¿Cómo ingreso a la Carrera?', 'Puede ingresar mediante la Prueba de Suficiencia Académica, el curso preuniversitario o las modalidades de admisión especial. Revise la sección Admisión y el calendario académico.'],
            ['Admisión', '¿Cuántos semestres dura la Carrera?', 'El plan de estudios está organizado en semestres y concluye con la modalidad de graduación. Puede ver el detalle en la malla curricular interactiva.'],
            ['Trámites', '¿Dónde descargo los formularios?', 'Todos los formularios están en la Biblioteca digital, categoría Formularios.'],
            ['Trámites', '¿Cómo solicito una convalidación de materias?', 'Presente el formulario de convalidación con los programas analíticos certificados en Secretaría de Carrera dentro de las fechas del calendario.'],
            ['Titulación', '¿Qué modalidades de titulación existen?', 'Tesis, proyecto de grado, trabajo dirigido, examen de grado, graduación por excelencia y diplomado, según el reglamento vigente.'],
            ['Titulación', '¿Cuándo puedo inscribir mi modalidad de graduación?', 'Una vez aprobadas todas las materias del plan de estudios, en las fechas que establece la Comisión de Graduación.'],
            ['INNOVA', '¿Cómo formo parte de la Empresa Junior INNOVA?', 'Complete el formulario de postulación en la sección INNOVA. El equipo revisará su solicitud y lo contactará.'],
            ['General', '¿Cómo me comunico con la Dirección de Carrera?', 'Use el formulario de la sección Contacto o escriba al correo institucional que figura en el pie de página.'],
        ];

        foreach ($preguntas as $i => [$categoria, $pregunta, $respuesta]) {
            PreguntaFrecuente::query()->create(['categoria' => $categoria, 'pregunta' => $pregunta, 'respuesta' => $respuesta, 'orden' => $i + 1]);
        }
    }
}

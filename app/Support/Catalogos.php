<?php

namespace App\Support;

/**
 * Listas de valores usadas en formularios, filtros y páginas públicas.
 */
class Catalogos
{
    public const SECCIONES_PAGINA = [
        'institucional' => 'Institucional',
        'academico' => 'Académico',
        'revista' => 'Revista INNOVAR',
        'innova' => 'Empresa Junior INNOVA',
        'graduados' => 'Graduados',
    ];

    public const TIPOS_DOCENTE = [
        'autoridad' => 'Autoridad',
        'docente' => 'Docente',
        'administrativo' => 'Administrativo',
    ];

    public const TIPOS_MATERIA = [
        'obligatoria' => 'Obligatoria',
        'electiva' => 'Electiva',
        'modalidad' => 'Modalidad de graduación',
    ];

    public const TIPOS_CALENDARIO = [
        'inscripciones' => 'Inscripciones',
        'clases' => 'Clases',
        'examenes' => 'Exámenes',
        'feriado' => 'Feriado / descanso',
        'academico' => 'Actividad académica',
        'titulacion' => 'Titulación',
    ];

    public const TIPOS_INVESTIGACION = [
        'proyecto' => 'Proyecto de investigación',
        'pia' => 'Proyecto de Investigación en Aula (PIA)',
        'semillero' => 'Semillero de investigación',
        'produccion' => 'Producción académica',
    ];

    public const ESTADOS_PROYECTO = [
        'planificado' => 'Planificado',
        'en_curso' => 'En curso',
        'finalizado' => 'Finalizado',
    ];

    public const TIPOS_CONVENIO = [
        'convenio' => 'Convenio institucional',
        'practica' => 'Práctica empresarial',
        'proyecto_conjunto' => 'Proyecto con empresa',
        'consultoria' => 'Consultoría',
        'interaccion_social' => 'Interacción social',
    ];

    public const ESTADOS_CONVENIO = [
        'vigente' => 'Vigente',
        'en_tramite' => 'En trámite',
        'finalizado' => 'Finalizado',
    ];

    public const ESTADOS_SOLICITUD = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En proceso',
        'atendida' => 'Atendida',
        'rechazada' => 'Rechazada',
    ];

    public const ESTADOS_POSTULACION = [
        'pendiente' => 'Pendiente',
        'entrevista' => 'En entrevista',
        'aceptada' => 'Aceptada',
        'rechazada' => 'Rechazada',
    ];

    public const SITUACIONES_LABORALES = [
        'dependiente' => 'Trabaja en relación de dependencia',
        'independiente' => 'Trabajo independiente / emprendedor',
        'busca_empleo' => 'Buscando empleo',
        'estudiando' => 'Estudios de posgrado',
        'otro' => 'Otro',
    ];

    public const MODALIDADES_TITULACION = [
        'tesis' => 'Tesis de grado',
        'proyecto_grado' => 'Proyecto de grado',
        'trabajo_dirigido' => 'Trabajo dirigido',
        'examen_grado' => 'Examen de grado',
        'excelencia' => 'Graduación por excelencia',
        'diplomado' => 'Diplomado',
    ];

    public const ESTADOS_NOTICIA = [
        'borrador' => 'Borrador',
        'publicado' => 'Publicado',
        'archivado' => 'Archivado',
    ];

    public const TIPOS_EVENTO = [
        'conferencia' => 'Conferencia',
        'seminario' => 'Seminario',
        'taller' => 'Taller',
        'feria' => 'Feria',
        'congreso' => 'Congreso',
        'otro' => 'Otro',
    ];

    public const ESTADOS_CONSULTA = [
        'nueva' => 'Nueva',
        'en_revision' => 'En revisión',
        'respondida' => 'Respondida',
        'cerrada' => 'Cerrada',
    ];

    public const ACCIONES_BITACORA = [
        'crear' => 'Crear',
        'editar' => 'Editar',
        'publicar' => 'Publicar',
        'archivar' => 'Archivar',
        'eliminar' => 'Eliminar',
        'inicio_sesion' => 'Inicio de sesión',
        'cierre_sesion' => 'Cierre de sesión',
        'exportar' => 'Exportar',
    ];

    public const ICONOS_MENU = [
        'layout-dashboard', 'users', 'shield', 'history', 'menu', 'home', 'image', 'star', 'link', 'settings',
        'file-text', 'graduation-cap', 'network', 'calendar', 'help-circle', 'library', 'folder', 'flask-conical',
        'book-open', 'handshake', 'briefcase', 'user-check', 'newspaper', 'tags', 'calendar-days', 'images',
        'video', 'mail', 'inbox', 'bar-chart-3', 'contact',
    ];
}

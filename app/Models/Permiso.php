<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permiso de la matriz de control de acceso (RBAC): nombre = "{recurso}.{accion}".
 * El recurso agrupa una funcionalidad del panel (noticias, usuarios, ...) y la acción
 * es la operación permitida sobre él (ver | crear | editar | eliminar).
 */
class Permiso extends Model
{
    /** Acciones de la matriz, en el orden en que se muestran. */
    public const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    /**
     * Recursos de la matriz de control de acceso: módulo (grupo visible), etiqueta,
     * complemento de la descripción legible y acciones disponibles.
     */
    public const RECURSOS = [
        'usuarios' => ['modulo' => 'Usuarios y seguridad', 'etiqueta' => 'Usuarios', 'objeto' => 'usuarios del sistema', 'acciones' => self::ACCIONES],
        'roles' => ['modulo' => 'Usuarios y seguridad', 'etiqueta' => 'Roles y permisos', 'objeto' => 'roles y sus permisos', 'acciones' => self::ACCIONES],
        'menu' => ['modulo' => 'Usuarios y seguridad', 'etiqueta' => 'Menú del panel', 'objeto' => 'opciones del menú del panel', 'acciones' => self::ACCIONES],
        'bitacora' => ['modulo' => 'Usuarios y seguridad', 'etiqueta' => 'Bitácora y accesos', 'objeto' => 'la bitácora de auditoría, inicios de sesión y tráfico', 'acciones' => ['ver']],
        'portal' => ['modulo' => 'Portal web', 'etiqueta' => 'Página principal, banners, destacados, enlaces y contacto', 'objeto' => 'contenido de la página principal (banners, destacados, enlaces y contacto)', 'acciones' => self::ACCIONES],
        'academico' => ['modulo' => 'Académico', 'etiqueta' => 'Páginas, malla, calendario y preguntas', 'objeto' => 'páginas de contenido, malla curricular, calendario y preguntas frecuentes', 'acciones' => self::ACCIONES],
        'docentes' => ['modulo' => 'Académico', 'etiqueta' => 'Autoridades y docentes', 'objeto' => 'autoridades y docentes', 'acciones' => self::ACCIONES],
        'documentos' => ['modulo' => 'Investigación', 'etiqueta' => 'Biblioteca digital', 'objeto' => 'documentos de la biblioteca digital', 'acciones' => self::ACCIONES],
        'investigacion' => ['modulo' => 'Investigación', 'etiqueta' => 'Líneas y proyectos', 'objeto' => 'líneas y proyectos de investigación', 'acciones' => self::ACCIONES],
        'revista' => ['modulo' => 'Investigación', 'etiqueta' => 'Revista INNOVAR', 'objeto' => 'ediciones y artículos de la Revista INNOVAR', 'acciones' => self::ACCIONES],
        'vinculacion' => ['modulo' => 'Vinculación', 'etiqueta' => 'Convenios', 'objeto' => 'convenios institucionales', 'acciones' => self::ACCIONES],
        'innova' => ['modulo' => 'Vinculación', 'etiqueta' => 'Empresa Junior INNOVA', 'objeto' => 'servicios, integrantes, proyectos, solicitudes y postulaciones de INNOVA', 'acciones' => self::ACCIONES],
        'graduados' => ['modulo' => 'Vinculación', 'etiqueta' => 'Graduados', 'objeto' => 'fichas de seguimiento a graduados', 'acciones' => self::ACCIONES],
        'noticias' => ['modulo' => 'Difusión', 'etiqueta' => 'Noticias y categorías', 'objeto' => 'noticias, comunicados y sus categorías', 'acciones' => self::ACCIONES],
        'eventos' => ['modulo' => 'Difusión', 'etiqueta' => 'Eventos académicos', 'objeto' => 'eventos académicos', 'acciones' => self::ACCIONES],
        'galeria' => ['modulo' => 'Difusión', 'etiqueta' => 'Álbumes y videos', 'objeto' => 'álbumes de fotos y videos', 'acciones' => self::ACCIONES],
        'consultas' => ['modulo' => 'Consultas', 'etiqueta' => 'Consultas y categorías', 'objeto' => 'consultas recibidas y sus categorías', 'acciones' => self::ACCIONES],
        'reportes' => ['modulo' => 'Reportes', 'etiqueta' => 'Reportes y estadísticas', 'objeto' => 'reportes y estadísticas', 'acciones' => ['ver']],
    ];

    protected $table = 'permisos';

    protected $fillable = [
        'nombre',
        'recurso',
        'accion',
        'descripcion',
        'modulo',
    ];

    /** @return BelongsToMany<Rol, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'permiso_id', 'rol_id');
    }

    /**
     * Todos los permisos de la matriz, en orden de recurso y acción.
     *
     * @return array<int, array{nombre: string, recurso: string, accion: string, modulo: string, descripcion: string}>
     */
    public static function matriz(): array
    {
        $permisos = [];

        foreach (self::RECURSOS as $recurso => $definicion) {
            foreach ($definicion['acciones'] as $accion) {
                $permisos[] = [
                    'nombre' => "{$recurso}.{$accion}",
                    'recurso' => $recurso,
                    'accion' => $accion,
                    'modulo' => $definicion['modulo'],
                    'descripcion' => ucfirst($accion).' '.$definicion['objeto'],
                ];
            }
        }

        return $permisos;
    }

    /** Etiqueta visible de un recurso (p. ej. "noticias" => "Noticias y categorías"). */
    public static function etiquetaRecurso(string $recurso): string
    {
        return self::RECURSOS[$recurso]['etiqueta'] ?? ucfirst($recurso);
    }
}

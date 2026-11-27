<?php

namespace Database\Seeders;

use App\Models\Graduado;
use App\Models\MenuItem;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * CU1 - Matriz de control de acceso (permisos "{recurso}.{accion}"), roles, cuentas de
 * prueba y menú dinámico del panel. Se puede ejecutar varias veces (actualiza sin duplicar).
 * Todas las cuentas de prueba usan la contraseña "password".
 */
class SeguridadSeeder extends Seeder
{
    /**
     * Roles => [descripción, acceso al panel, permisos]. Admite comodines: "*" (todos)
     * y "recurso.*" (todas las acciones del recurso).
     */
    public const ROLES = [
        'Administrador' => ['Acceso total al sistema', true, ['*']],
        'Responsable de Noticias' => ['Publica noticias, eventos y la galería multimedia', true, ['noticias.*', 'eventos.*', 'galeria.*', 'reportes.ver']],
        'Encargado Revista INNOVAR' => ['Administra la Revista INNOVAR, la investigación y la biblioteca digital', true, ['revista.*', 'investigacion.*', 'documentos.ver', 'documentos.crear', 'documentos.editar']],
        'Administrador de Graduados' => ['Seguimiento a graduados, Empresa Junior INNOVA y convenios', true, ['graduados.*', 'innova.*', 'vinculacion.*', 'reportes.ver']],
        'Secretaría Académica' => ['Contenido académico, plantel docente, biblioteca y consultas', true, ['academico.*', 'docentes.*', 'documentos.*', 'consultas.ver', 'consultas.editar']],
        'Graduado' => ['Titulado que actualiza sus datos desde el sitio (sin acceso al panel)', false, []],
    ];

    /** Cuentas de prueba: [nombre, correo, rol]. */
    public const USUARIOS = [
        ['Administrador del Sistema', 'admin@carrera.test', 'Administrador'],
        ['Responsable de Noticias', 'noticias@carrera.test', 'Responsable de Noticias'],
        ['Encargado Revista INNOVAR', 'revista@carrera.test', 'Encargado Revista INNOVAR'],
        ['Administrador de Graduados', 'graduados@carrera.test', 'Administrador de Graduados'],
        ['Secretaría Académica', 'secretaria@carrera.test', 'Secretaría Académica'],
        ['Graduado de Prueba', 'graduado@carrera.test', 'Graduado'],
    ];

    /**
     * Menú dinámico del panel: [etiqueta, ícono lucide, permiso requerido, ruta de la SPA, ítems].
     * Un grupo (sin ruta) se muestra si el usuario puede ver al menos uno de sus ítems.
     */
    public const MENU = [
        ['Inicio', 'layout-dashboard', null, '/admin', []],
        ['Usuarios y seguridad', 'shield', null, null, [
            ['Usuarios', 'users', 'usuarios.ver', '/admin/usuarios'],
            ['Roles y permisos', 'shield', 'roles.ver', '/admin/roles'],
            ['Matriz de acceso', 'network', 'roles.ver', '/admin/roles/matriz'],
            ['Menú del panel', 'menu', 'menu.ver', '/admin/menu'],
            ['Bitácora y accesos', 'history', 'bitacora.ver', '/admin/bitacora'],
        ]],
        ['Portal web', 'home', null, null, [
            ['Página principal', 'settings', 'portal.ver', '/admin/configuracion/portal'],
            ['Carrusel de banners', 'image', 'portal.ver', '/admin/banners'],
            ['Información destacada', 'star', 'portal.ver', '/admin/destacados'],
            ['Enlaces a plataformas', 'link', 'portal.ver', '/admin/enlaces'],
            ['Contacto y redes', 'contact', 'portal.ver', '/admin/configuracion/contacto'],
        ]],
        ['Académico', 'graduation-cap', null, null, [
            ['Páginas de contenido', 'file-text', 'academico.ver', '/admin/paginas'],
            ['Autoridades y docentes', 'users', 'docentes.ver', '/admin/docentes'],
            ['Malla curricular', 'network', 'academico.ver', '/admin/materias'],
            ['Calendario académico', 'calendar', 'academico.ver', '/admin/calendario'],
            ['Preguntas frecuentes', 'help-circle', 'academico.ver', '/admin/preguntas'],
        ]],
        ['Investigación', 'flask-conical', null, null, [
            ['Biblioteca digital', 'library', 'documentos.ver', '/admin/documentos'],
            ['Categorías de documentos', 'folder', 'documentos.ver', '/admin/categorias-documento'],
            ['Líneas de investigación', 'flask-conical', 'investigacion.ver', '/admin/lineas'],
            ['Proyectos de investigación', 'flask-conical', 'investigacion.ver', '/admin/proyectos'],
            ['Revista INNOVAR - Ediciones', 'book-open', 'revista.ver', '/admin/ediciones'],
            ['Revista INNOVAR - Artículos', 'file-text', 'revista.ver', '/admin/articulos'],
        ]],
        ['Vinculación', 'handshake', null, null, [
            ['Convenios', 'handshake', 'vinculacion.ver', '/admin/convenios'],
            ['INNOVA - Servicios', 'briefcase', 'innova.ver', '/admin/innova-servicios'],
            ['INNOVA - Integrantes', 'users', 'innova.ver', '/admin/innova-integrantes'],
            ['INNOVA - Proyectos', 'briefcase', 'innova.ver', '/admin/innova-proyectos'],
            ['INNOVA - Solicitudes', 'inbox', 'innova.ver', '/admin/innova-solicitudes'],
            ['INNOVA - Postulaciones', 'user-check', 'innova.ver', '/admin/innova-postulaciones'],
            ['Graduados', 'graduation-cap', 'graduados.ver', '/admin/graduados'],
        ]],
        ['Difusión', 'newspaper', null, null, [
            ['Noticias', 'newspaper', 'noticias.ver', '/admin/noticias'],
            ['Categorías de noticias', 'tags', 'noticias.ver', '/admin/categorias-noticia'],
            ['Eventos', 'calendar-days', 'eventos.ver', '/admin/eventos'],
            ['Álbumes de fotos', 'images', 'galeria.ver', '/admin/albumes'],
            ['Videos', 'video', 'galeria.ver', '/admin/videos'],
        ]],
        ['Consultas', 'mail', null, null, [
            ['Consultas recibidas', 'inbox', 'consultas.ver', '/admin/consultas'],
            ['Categorías de consulta', 'tags', 'consultas.ver', '/admin/categorias-consulta'],
        ]],
        ['Reportes y estadísticas', 'bar-chart-3', 'reportes.ver', '/admin/reportes', []],
    ];

    public function run(): void
    {
        $this->permisos();
        $this->roles();
        $this->usuarios();
        $this->menu();
    }

    private function permisos(): void
    {
        $matriz = Permiso::matriz();

        foreach ($matriz as $permiso) {
            Permiso::query()->updateOrCreate(['nombre' => $permiso['nombre']], $permiso);
        }

        // Permisos de versiones anteriores (p. ej. "noticias.gestionar") que ya no forman parte de la matriz.
        Permiso::query()->whereNotIn('nombre', array_column($matriz, 'nombre'))->delete();
    }

    private function roles(): void
    {
        $permisos = Permiso::query()->pluck('id', 'nombre');

        foreach (self::ROLES as $nombre => [$descripcion, $panel, $patrones]) {
            $rol = Rol::query()->updateOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion, 'acceso_panel' => $panel]);

            $ids = $permisos->filter(fn ($id, $permiso) => Str::is($patrones, $permiso))->values()->all();
            $rol->permisos()->sync($ids);
        }
    }

    private function usuarios(): void
    {
        $roles = Rol::query()->pluck('id', 'nombre');
        $clave = Hash::make('password');

        foreach (self::USUARIOS as [$nombre, $correo, $rol]) {
            User::query()->updateOrCreate(['email' => $correo], [
                'name' => $nombre,
                'password' => $clave,
                'rol_id' => $roles[$rol],
                'activo' => true,
                'email_verified_at' => now(),
            ]);
        }

        // Ficha del graduado de prueba (actualiza sus datos desde /graduados/mi-ficha).
        Graduado::query()->updateOrCreate(['user_id' => User::query()->where('email', 'graduado@carrera.test')->value('id')], [
            'nombre' => 'Graduado de Prueba',
            'ci' => '1234567',
            'registro' => '210000001',
            'anio_graduacion' => 2023,
            'modalidad_titulacion' => 'proyecto_grado',
            'situacion_laboral' => 'dependiente',
            'empresa' => 'Empresa de ejemplo',
            'cargo' => 'Analista administrativo',
            'ciudad' => 'Santa Cruz de la Sierra',
            'correo' => 'graduado@carrera.test',
            'telefono' => '70000001',
        ]);
    }

    private function menu(): void
    {
        MenuItem::query()->delete();

        foreach (self::MENU as $i => [$etiqueta, $icono, $permiso, $ruta, $items]) {
            $grupo = MenuItem::query()->create([
                'etiqueta' => $etiqueta,
                'icono' => $icono,
                'permiso' => $permiso,
                'ruta' => $ruta,
                'orden' => $i + 1,
                'activo' => true,
            ]);

            foreach ($items as $j => [$e, $ic, $p, $r]) {
                MenuItem::query()->create([
                    'etiqueta' => $e,
                    'icono' => $ic,
                    'permiso' => $p,
                    'ruta' => $r,
                    'padre_id' => $grupo->id,
                    'orden' => $j + 1,
                    'activo' => true,
                ]);
            }
        }
    }
}

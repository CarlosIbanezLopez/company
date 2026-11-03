<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * CU1 - Roles, permisos, usuarios iniciales y menú dinámico del panel.
 */
class SeguridadSeeder extends Seeder
{
    public const PERMISOS = [
        'usuarios.gestionar' => ['Usuarios', 'Gestionar usuarios, roles y menú'],
        'portal.gestionar' => ['Portal web', 'Administrar página principal, contacto y redes'],
        'bitacora.ver' => ['Portal web', 'Consultar la bitácora de auditoría'],
        'academico.gestionar' => ['Académico', 'Contenido institucional, académico, malla y calendario'],
        'docentes.gestionar' => ['Académico', 'Autoridades y docentes'],
        'documentos.gestionar' => ['Investigación', 'Biblioteca digital de documentos'],
        'investigacion.gestionar' => ['Investigación', 'Líneas y proyectos de investigación'],
        'revista.gestionar' => ['Investigación', 'Revista INNOVAR'],
        'vinculacion.gestionar' => ['Vinculación', 'Convenios y relaciones externas'],
        'innova.gestionar' => ['Vinculación', 'Empresa Junior INNOVA'],
        'graduados.gestionar' => ['Vinculación', 'Seguimiento a graduados'],
        'noticias.gestionar' => ['Difusión', 'Noticias y comunicados'],
        'eventos.gestionar' => ['Difusión', 'Eventos académicos'],
        'galeria.gestionar' => ['Difusión', 'Galería multimedia'],
        'consultas.gestionar' => ['Consultas', 'Consultas del formulario de contacto'],
        'reportes.ver' => ['Reportes', 'Reportes y estadísticas'],
    ];

    public function run(): void
    {
        foreach (self::PERMISOS as $nombre => [$modulo, $descripcion]) {
            Permiso::query()->updateOrCreate(['nombre' => $nombre], ['modulo' => $modulo, 'descripcion' => $descripcion]);
        }

        $roles = [
            'Administrador' => ['Acceso total al sistema', true, array_keys(self::PERMISOS)],
            'Responsable de Noticias' => ['Publica noticias, eventos y galería', true, ['noticias.gestionar', 'eventos.gestionar', 'galeria.gestionar', 'reportes.ver']],
            'Encargado Revista INNOVAR' => ['Administra la revista, investigación y biblioteca', true, ['revista.gestionar', 'investigacion.gestionar', 'documentos.gestionar']],
            'Administrador de Graduados' => ['Seguimiento a graduados, INNOVA y convenios', true, ['graduados.gestionar', 'innova.gestionar', 'vinculacion.gestionar', 'reportes.ver']],
            'Secretaría Académica' => ['Contenido académico, plantel y consultas', true, ['academico.gestionar', 'docentes.gestionar', 'documentos.gestionar', 'consultas.gestionar']],
            'Graduado' => ['Titulado que actualiza sus datos desde el sitio', false, []],
        ];

        foreach ($roles as $nombre => [$descripcion, $panel, $permisos]) {
            $rol = Rol::query()->updateOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion, 'acceso_panel' => $panel]);
            $rol->permisos()->sync(Permiso::query()->whereIn('nombre', $permisos)->pluck('id'));
        }

        $usuarios = [
            ['Administrador del Sistema', 'admin@carrera.test', 'Administrador'],
            ['Responsable de Noticias', 'noticias@carrera.test', 'Responsable de Noticias'],
            ['Encargado Revista INNOVAR', 'revista@carrera.test', 'Encargado Revista INNOVAR'],
            ['Administrador de Graduados', 'graduados@carrera.test', 'Administrador de Graduados'],
            ['Secretaría Académica', 'secretaria@carrera.test', 'Secretaría Académica'],
        ];

        foreach ($usuarios as [$nombre, $correo, $rol]) {
            User::query()->updateOrCreate(['email' => $correo], [
                'name' => $nombre,
                'password' => 'password',
                'rol_id' => Rol::query()->where('nombre', $rol)->value('id'),
                'activo' => true,
                'email_verified_at' => now(),
            ]);
        }

        $this->menu();
    }

    private function menu(): void
    {
        MenuItem::query()->delete();

        $menu = [
            ['Inicio', 'layout-dashboard', null, 'admin.dashboard', []],
            ['Usuarios y seguridad', 'shield', 'usuarios.gestionar', null, [
                ['Usuarios', 'users', 'usuarios.gestionar', 'admin.usuarios.index'],
                ['Roles y permisos', 'shield', 'usuarios.gestionar', 'admin.roles.index'],
                ['Menú del panel', 'menu', 'usuarios.gestionar', 'admin.menu.index'],
            ]],
            ['Portal web', 'home', null, null, [
                ['Página principal', 'settings', 'portal.gestionar', 'admin.configuracion.portal'],
                ['Carrusel de banners', 'image', 'portal.gestionar', 'admin.banners.index'],
                ['Información destacada', 'star', 'portal.gestionar', 'admin.destacados.index'],
                ['Enlaces a plataformas', 'link', 'portal.gestionar', 'admin.enlaces.index'],
                ['Contacto y redes', 'contact', 'portal.gestionar', 'admin.configuracion.contacto'],
                ['Bitácora', 'history', 'bitacora.ver', 'admin.bitacora.index'],
            ]],
            ['Académico', 'graduation-cap', null, null, [
                ['Páginas de contenido', 'file-text', 'academico.gestionar', 'admin.paginas.index'],
                ['Autoridades y docentes', 'users', 'docentes.gestionar', 'admin.docentes.index'],
                ['Malla curricular', 'network', 'academico.gestionar', 'admin.materias.index'],
                ['Calendario académico', 'calendar', 'academico.gestionar', 'admin.calendario.index'],
                ['Preguntas frecuentes', 'help-circle', 'academico.gestionar', 'admin.preguntas.index'],
            ]],
            ['Investigación', 'flask-conical', null, null, [
                ['Biblioteca digital', 'library', 'documentos.gestionar', 'admin.documentos.index'],
                ['Categorías de documentos', 'folder', 'documentos.gestionar', 'admin.categorias-documento.index'],
                ['Líneas de investigación', 'flask-conical', 'investigacion.gestionar', 'admin.lineas.index'],
                ['Proyectos de investigación', 'flask-conical', 'investigacion.gestionar', 'admin.proyectos.index'],
                ['Revista INNOVAR - Ediciones', 'book-open', 'revista.gestionar', 'admin.ediciones.index'],
                ['Revista INNOVAR - Artículos', 'file-text', 'revista.gestionar', 'admin.articulos.index'],
            ]],
            ['Vinculación', 'handshake', null, null, [
                ['Convenios', 'handshake', 'vinculacion.gestionar', 'admin.convenios.index'],
                ['INNOVA - Servicios', 'briefcase', 'innova.gestionar', 'admin.innova-servicios.index'],
                ['INNOVA - Integrantes', 'users', 'innova.gestionar', 'admin.innova-integrantes.index'],
                ['INNOVA - Proyectos', 'briefcase', 'innova.gestionar', 'admin.innova-proyectos.index'],
                ['INNOVA - Solicitudes', 'inbox', 'innova.gestionar', 'admin.innova-solicitudes.index'],
                ['INNOVA - Postulaciones', 'user-check', 'innova.gestionar', 'admin.innova-postulaciones.index'],
                ['Graduados', 'graduation-cap', 'graduados.gestionar', 'admin.graduados.index'],
            ]],
            ['Difusión', 'newspaper', null, null, [
                ['Noticias', 'newspaper', 'noticias.gestionar', 'admin.noticias.index'],
                ['Categorías de noticias', 'tags', 'noticias.gestionar', 'admin.categorias-noticia.index'],
                ['Eventos', 'calendar-days', 'eventos.gestionar', 'admin.eventos.index'],
                ['Álbumes de fotos', 'images', 'galeria.gestionar', 'admin.albumes.index'],
                ['Videos', 'video', 'galeria.gestionar', 'admin.videos.index'],
            ]],
            ['Consultas', 'mail', null, null, [
                ['Consultas recibidas', 'inbox', 'consultas.gestionar', 'admin.consultas.index'],
                ['Categorías de consulta', 'tags', 'consultas.gestionar', 'admin.categorias-consulta.index'],
            ]],
            ['Reportes y estadísticas', 'bar-chart-3', 'reportes.ver', 'admin.reportes.index', []],
        ];

        foreach ($menu as $i => [$etiqueta, $icono, $permiso, $ruta, $hijos]) {
            $padre = MenuItem::query()->create(['etiqueta' => $etiqueta, 'icono' => $icono, 'permiso' => $permiso, 'ruta' => $ruta, 'orden' => $i + 1, 'activo' => true]);

            foreach ($hijos as $j => [$e, $ic, $p, $r]) {
                MenuItem::query()->create(['etiqueta' => $e, 'icono' => $ic, 'permiso' => $p, 'ruta' => $r, 'padre_id' => $padre->id, 'orden' => $j + 1, 'activo' => true]);
            }
        }
    }
}

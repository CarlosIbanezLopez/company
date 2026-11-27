<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

/*
| API del panel de administración. Matriz de control de acceso (RBAC):
| cada recurso exige el permiso "{recurso}.{accion}" según la operación:
|   GET (listar / ver)  -> ver      POST (registrar) -> crear
|   PUT (actualizar)    -> editar   DELETE (eliminar) -> eliminar
*/

/**
 * Registra las rutas REST de un controlador CRUD protegidas por la matriz de acceso.
 *
 * @param  array<int, string>  $solo
 */
$crud = function (string $uri, string $controlador, string $recurso, array $solo = ['index', 'create', 'store', 'show', 'update', 'destroy']): void {
    $nombre = "api.admin.$uri";

    in_array('index', $solo) && Route::get($uri, [$controlador, 'index'])->middleware("permiso:$recurso.ver")->name("$nombre.index");
    in_array('create', $solo) && Route::get("$uri/formulario", [$controlador, 'create'])->middleware("permiso:$recurso.crear")->name("$nombre.create");
    in_array('store', $solo) && Route::post($uri, [$controlador, 'store'])->middleware("permiso:$recurso.crear")->name("$nombre.store");
    in_array('show', $solo) && Route::get("$uri/{id}", [$controlador, 'show'])->whereNumber('id')->middleware("permiso:$recurso.ver")->name("$nombre.show");
    in_array('update', $solo) && Route::put("$uri/{id}", [$controlador, 'update'])->whereNumber('id')->middleware("permiso:$recurso.editar")->name("$nombre.update");
    in_array('destroy', $solo) && Route::delete("$uri/{id}", [$controlador, 'destroy'])->whereNumber('id')->middleware("permiso:$recurso.eliminar")->name("$nombre.destroy");
};

Route::middleware(['auth:sanctum', 'panel'])->prefix('admin')->group(function () use ($crud) {
    // Tablero analítico (métricas del negocio y del flujo de accesos).
    Route::get('dashboard', [Admin\DashboardController::class, 'index'])->name('api.admin.dashboard');

    // CU1 - Usuarios, roles (matriz de acceso) y menú dinámico
    $crud('usuarios', Admin\UsuarioController::class, 'usuarios');
    Route::get('roles/matriz', [Admin\RolController::class, 'matriz'])->middleware('permiso:roles.ver')->name('api.admin.roles.matriz');
    $crud('roles', Admin\RolController::class, 'roles');
    $crud('menu', Admin\MenuItemController::class, 'menu');

    // CU2 - Portal web
    $crud('banners', Admin\BannerController::class, 'portal');
    $crud('destacados', Admin\DestacadoController::class, 'portal');
    $crud('enlaces', Admin\EnlacePlataformaController::class, 'portal');
    Route::get('configuracion/{grupo}', [Admin\ConfiguracionController::class, 'show'])->whereIn('grupo', ['portal', 'contacto'])->middleware('permiso:portal.ver')->name('api.admin.configuracion.show');
    Route::put('configuracion/{grupo}', [Admin\ConfiguracionController::class, 'update'])->whereIn('grupo', ['portal', 'contacto'])->middleware('permiso:portal.editar')->name('api.admin.configuracion.update');

    // Bitácora de auditoría: acciones, inicios de sesión (exitosos y fallidos) y tráfico (IP / recursos).
    Route::middleware('permiso:bitacora.ver')->group(function () {
        Route::get('bitacora', [Admin\BitacoraController::class, 'index'])->name('api.admin.bitacora.index');
        Route::get('bitacora/sesiones', [Admin\BitacoraController::class, 'sesiones'])->name('api.admin.bitacora.sesiones');
        Route::get('bitacora/trafico', [Admin\BitacoraController::class, 'trafico'])->name('api.admin.bitacora.trafico');
    });

    // CU3 - Información académica, malla curricular y plantel docente
    $crud('paginas', Admin\PaginaController::class, 'academico');
    $crud('materias', Admin\MateriaController::class, 'academico');
    $crud('calendario', Admin\ActividadCalendarioController::class, 'academico');
    $crud('preguntas', Admin\PreguntaFrecuenteController::class, 'academico');
    $crud('docentes', Admin\DocenteController::class, 'docentes');

    // CU4 - Investigación, Revista INNOVAR y repositorio documental
    $crud('categorias-documento', Admin\CategoriaDocumentoController::class, 'documentos');
    $crud('documentos', Admin\DocumentoController::class, 'documentos');
    $crud('lineas', Admin\LineaInvestigacionController::class, 'investigacion');
    $crud('proyectos', Admin\ProyectoInvestigacionController::class, 'investigacion');
    $crud('ediciones', Admin\RevistaEdicionController::class, 'revista');
    $crud('articulos', Admin\RevistaArticuloController::class, 'revista');

    // CU5 - Vinculación, Empresa Junior INNOVA y graduados
    $crud('convenios', Admin\ConvenioController::class, 'vinculacion');
    $crud('innova-servicios', Admin\InnovaServicioController::class, 'innova');
    $crud('innova-integrantes', Admin\InnovaIntegranteController::class, 'innova');
    $crud('innova-proyectos', Admin\InnovaProyectoController::class, 'innova');
    $crud('innova-solicitudes', Admin\InnovaSolicitudController::class, 'innova', ['index', 'show', 'update', 'destroy']);
    $crud('innova-postulaciones', Admin\InnovaPostulacionController::class, 'innova', ['index', 'show', 'update', 'destroy']);
    Route::get('graduados/exportar', [Admin\GraduadoController::class, 'exportar'])->middleware('permiso:graduados.ver')->name('api.admin.graduados.exportar');
    $crud('graduados', Admin\GraduadoController::class, 'graduados');

    // CU6 - Noticias, comunicados, eventos y galería multimedia
    $crud('categorias-noticia', Admin\CategoriaNoticiaController::class, 'noticias');
    $crud('noticias', Admin\NoticiaController::class, 'noticias');
    $crud('eventos', Admin\EventoController::class, 'eventos');
    $crud('albumes', Admin\AlbumController::class, 'galeria');
    Route::get('albumes/{id}/fotos', [Admin\AlbumController::class, 'fotos'])->whereNumber('id')->middleware('permiso:galeria.ver')->name('api.admin.albumes.fotos');
    Route::post('albumes/{id}/fotos', [Admin\AlbumController::class, 'subirFotos'])->whereNumber('id')->middleware('permiso:galeria.crear')->name('api.admin.albumes.fotos.store');
    Route::delete('albumes/{id}/fotos/{foto}', [Admin\AlbumController::class, 'eliminarFoto'])->whereNumber(['id', 'foto'])->middleware('permiso:galeria.eliminar')->name('api.admin.albumes.fotos.destroy');
    $crud('videos', Admin\VideoController::class, 'galeria');

    // CU7 - Consultas del formulario de contacto
    $crud('categorias-consulta', Admin\CategoriaConsultaController::class, 'consultas');
    $crud('consultas', Admin\ConsultaController::class, 'consultas', ['index', 'show', 'update', 'destroy']);

    // CU8 - Reportes y estadísticas
    Route::middleware('permiso:reportes.ver')->group(function () {
        Route::get('reportes', [Admin\ReporteController::class, 'index'])->name('api.admin.reportes.index');
        Route::get('reportes/visitas.csv', [Admin\ReporteController::class, 'exportarVisitas'])->name('api.admin.reportes.visitas');
    });
});

<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

/*
| Panel de administración. Cada módulo exige el permiso correspondiente (RF-18).
*/

/** Registra las rutas index/create/store/edit/update/destroy de un controlador CRUD. */
$crud = function (string $uri, string $controlador, array $solo = ['index', 'create', 'store', 'edit', 'update', 'destroy']): void {
    $nombre = $uri;
    in_array('index', $solo) && Route::get($uri, [$controlador, 'index'])->name("$nombre.index");
    in_array('create', $solo) && Route::get("$uri/crear", [$controlador, 'create'])->name("$nombre.create");
    in_array('store', $solo) && Route::post($uri, [$controlador, 'store'])->name("$nombre.store");
    in_array('edit', $solo) && Route::get("$uri/{id}/editar", [$controlador, 'edit'])->whereNumber('id')->name("$nombre.edit");
    in_array('update', $solo) && Route::put("$uri/{id}", [$controlador, 'update'])->whereNumber('id')->name("$nombre.update");
    in_array('destroy', $solo) && Route::delete("$uri/{id}", [$controlador, 'destroy'])->whereNumber('id')->name("$nombre.destroy");
};

Route::middleware(['auth', 'panel', 'visitas'])->prefix('admin')->name('admin.')->group(function () use ($crud) {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // CU1 - Gestión de usuarios
    Route::middleware('permiso:usuarios.gestionar')->group(function () use ($crud) {
        $crud('usuarios', Admin\UsuarioController::class);
        $crud('roles', Admin\RolController::class);
        $crud('menu', Admin\MenuItemController::class);
    });

    // CU2 - Portal web y bitácora
    Route::middleware('permiso:portal.gestionar')->group(function () use ($crud) {
        $crud('banners', Admin\BannerController::class);
        $crud('destacados', Admin\DestacadoController::class);
        $crud('enlaces', Admin\EnlacePlataformaController::class);
        Route::get('configuracion/portal', [Admin\ConfiguracionController::class, 'edit'])->defaults('grupo', 'portal')->name('configuracion.portal');
        Route::get('configuracion/contacto', [Admin\ConfiguracionController::class, 'edit'])->defaults('grupo', 'contacto')->name('configuracion.contacto');
        Route::put('configuracion/{grupo}', [Admin\ConfiguracionController::class, 'update'])->whereIn('grupo', ['portal', 'contacto'])->name('configuracion.update');
    });
    Route::middleware('permiso:bitacora.ver')->group(function () use ($crud) {
        $crud('bitacora', Admin\BitacoraController::class, ['index']);
    });

    // CU3 - Información académica, malla curricular y plantel docente
    Route::middleware('permiso:academico.gestionar')->group(function () use ($crud) {
        $crud('paginas', Admin\PaginaController::class);
        $crud('materias', Admin\MateriaController::class);
        $crud('calendario', Admin\ActividadCalendarioController::class);
        $crud('preguntas', Admin\PreguntaFrecuenteController::class);
    });
    Route::middleware('permiso:docentes.gestionar')->group(function () use ($crud) {
        $crud('docentes', Admin\DocenteController::class);
    });

    // CU4 - Investigación, Revista INNOVAR y repositorio documental
    Route::middleware('permiso:documentos.gestionar')->group(function () use ($crud) {
        $crud('categorias-documento', Admin\CategoriaDocumentoController::class);
        $crud('documentos', Admin\DocumentoController::class);
    });
    Route::middleware('permiso:investigacion.gestionar')->group(function () use ($crud) {
        $crud('lineas', Admin\LineaInvestigacionController::class);
        $crud('proyectos', Admin\ProyectoInvestigacionController::class);
    });
    Route::middleware('permiso:revista.gestionar')->group(function () use ($crud) {
        $crud('ediciones', Admin\RevistaEdicionController::class);
        $crud('articulos', Admin\RevistaArticuloController::class);
    });

    // CU5 - Vinculación, Empresa Junior INNOVA y graduados
    Route::middleware('permiso:vinculacion.gestionar')->group(function () use ($crud) {
        $crud('convenios', Admin\ConvenioController::class);
    });
    Route::middleware('permiso:innova.gestionar')->group(function () use ($crud) {
        $crud('innova-servicios', Admin\InnovaServicioController::class);
        $crud('innova-integrantes', Admin\InnovaIntegranteController::class);
        $crud('innova-proyectos', Admin\InnovaProyectoController::class);
        $crud('innova-solicitudes', Admin\InnovaSolicitudController::class, ['index', 'edit', 'update', 'destroy']);
        $crud('innova-postulaciones', Admin\InnovaPostulacionController::class, ['index', 'edit', 'update', 'destroy']);
    });
    Route::middleware('permiso:graduados.gestionar')->group(function () use ($crud) {
        Route::get('graduados/exportar', [Admin\GraduadoController::class, 'exportar'])->name('graduados.exportar');
        $crud('graduados', Admin\GraduadoController::class);
    });

    // CU6 - Noticias, comunicados, eventos y galería multimedia
    Route::middleware('permiso:noticias.gestionar')->group(function () use ($crud) {
        $crud('categorias-noticia', Admin\CategoriaNoticiaController::class);
        $crud('noticias', Admin\NoticiaController::class);
    });
    Route::middleware('permiso:eventos.gestionar')->group(function () use ($crud) {
        $crud('eventos', Admin\EventoController::class);
    });
    Route::middleware('permiso:galeria.gestionar')->group(function () use ($crud) {
        $crud('albumes', Admin\AlbumController::class);
        Route::get('albumes/{id}/fotos', [Admin\AlbumController::class, 'fotos'])->whereNumber('id')->name('albumes.fotos');
        Route::post('albumes/{id}/fotos', [Admin\AlbumController::class, 'subirFotos'])->whereNumber('id')->name('albumes.fotos.store');
        Route::delete('albumes/{id}/fotos/{foto}', [Admin\AlbumController::class, 'eliminarFoto'])->whereNumber(['id', 'foto'])->name('albumes.fotos.destroy');
        $crud('videos', Admin\VideoController::class);
    });

    // CU7 - Consultas del formulario de contacto
    Route::middleware('permiso:consultas.gestionar')->group(function () use ($crud) {
        $crud('categorias-consulta', Admin\CategoriaConsultaController::class);
        $crud('consultas', Admin\ConsultaController::class, ['index', 'edit', 'update', 'destroy']);
    });

    // CU8 - Reportes y estadísticas
    Route::middleware('permiso:reportes.ver')->group(function () {
        Route::get('reportes', [Admin\ReporteController::class, 'index'])->name('reportes.index');
        Route::get('reportes/visitas.csv', [Admin\ReporteController::class, 'exportarVisitas'])->name('reportes.visitas');
    });
});

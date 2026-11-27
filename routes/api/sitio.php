<?php

use App\Http\Controllers\Sitio;
use App\Http\Middleware\CuentaActiva;
use Illuminate\Support\Facades\Route;

/*
| API pública del portal. Las URI coinciden con las rutas de la SPA:
| la página /noticias/{slug} de React consume GET /api/noticias/{slug}.
| Las rutas públicas aceptan un token Bearer opcional: use $request->user('sanctum').
*/
Route::name('api.sitio.')->group(function () {
    // Datos compartidos del encabezado y pie de página (carrera, contacto, redes, menú).
    Route::get('sitio', [Sitio\SitioController::class, 'index'])->name('datos');

    // Contador de visitas por página (lo llama el pie de página en cada navegación).
    Route::post('visitas', [Sitio\VisitaController::class, 'store'])->middleware('throttle:120,1,visitas')->name('visitas');

    Route::get('inicio', [Sitio\InicioController::class, 'index'])->name('inicio');

    // CU3 - Información institucional y académica
    Route::get('institucional/{slug}', [Sitio\PaginaController::class, 'institucional'])->name('institucional');
    Route::get('academico/{slug}', [Sitio\PaginaController::class, 'academico'])->name('academico');
    Route::get('plantel-docente', [Sitio\PlantelController::class, 'index'])->name('plantel');
    Route::get('malla-curricular', [Sitio\MallaController::class, 'index'])->name('malla');
    Route::get('calendario-academico', [Sitio\AcademicoController::class, 'calendario'])->name('calendario');
    Route::get('preguntas-frecuentes', [Sitio\AcademicoController::class, 'preguntas'])->name('preguntas');

    // CU4 - Biblioteca digital, investigación y Revista INNOVAR
    Route::get('biblioteca', [Sitio\DocumentoController::class, 'index'])->name('documentos');
    Route::get('biblioteca/{id}/descargar', [Sitio\DocumentoController::class, 'descargar'])->whereNumber('id')->name('documentos.descargar');
    Route::get('investigacion', [Sitio\InvestigacionController::class, 'index'])->name('investigacion');
    Route::get('investigacion/{slug}', [Sitio\InvestigacionController::class, 'show'])->name('investigacion.show');
    Route::get('revista-innovar', [Sitio\RevistaController::class, 'index'])->name('revista');
    Route::get('revista-innovar/normas-para-autores', [Sitio\RevistaController::class, 'normas'])->name('revista.normas');
    Route::get('revista-innovar/{numero}', [Sitio\RevistaController::class, 'show'])->whereNumber('numero')->name('revista.show');

    // CU5 - Vinculación, INNOVA y graduados
    Route::get('vinculacion', [Sitio\VinculacionController::class, 'index'])->name('vinculacion');
    Route::get('innova', [Sitio\InnovaController::class, 'index'])->name('innova');
    Route::get('graduados', [Sitio\GraduadoController::class, 'index'])->name('graduados');

    // CU6 - Noticias, eventos y galería
    Route::get('noticias', [Sitio\NoticiaController::class, 'index'])->name('noticias');
    Route::get('noticias/{slug}', [Sitio\NoticiaController::class, 'show'])->name('noticias.show');
    Route::get('eventos', [Sitio\EventoController::class, 'index'])->name('eventos');
    Route::get('eventos/{slug}', [Sitio\EventoController::class, 'show'])->name('eventos.show');
    Route::get('galeria', [Sitio\GaleriaController::class, 'index'])->name('galeria');
    Route::get('galeria/{slug}', [Sitio\GaleriaController::class, 'show'])->name('galeria.show');

    // CU7 - Contacto y búsqueda global (resultados completos y sugerencias predictivas del encabezado)
    Route::get('contacto', [Sitio\ContactoController::class, 'index'])->name('contacto');
    Route::get('buscar', [Sitio\BuscadorController::class, 'index'])->name('buscar');
    Route::get('buscar/sugerencias', [Sitio\BuscadorController::class, 'sugerencias'])->middleware('throttle:90,1,sugerencias')->name('buscar.sugerencias');

    // Envío de formularios públicos (con límite de intentos para frenar envíos automatizados).
    Route::middleware('throttle:6,1,formularios')->group(function () {
        Route::post('contacto', [Sitio\ContactoController::class, 'store'])->name('contacto.store');
        Route::post('innova/solicitudes', [Sitio\InnovaController::class, 'solicitar'])->name('innova.solicitar');
        Route::post('innova/postulaciones', [Sitio\InnovaController::class, 'postular'])->name('innova.postular');
        Route::post('graduados', [Sitio\GraduadoController::class, 'store'])->name('graduados.store');
    });

    // Ficha personal del graduado autenticado.
    Route::middleware(['auth:sanctum', CuentaActiva::class])->group(function () {
        Route::get('graduados/mi-ficha', [Sitio\GraduadoController::class, 'ficha'])->name('graduados.ficha');
        Route::put('graduados/mi-ficha', [Sitio\GraduadoController::class, 'update'])->name('graduados.ficha.update');
    });
});

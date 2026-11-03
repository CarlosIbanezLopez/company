<?php

use App\Http\Controllers\Sitio;
use Illuminate\Support\Facades\Route;

/*
| Sitio web público de la Carrera. Cada página cuenta sus visitas (middleware "visitas").
*/
Route::middleware('visitas')->group(function () {
    Route::get('/', [Sitio\InicioController::class, 'index'])->name('home');

    // CU3 - Información institucional y académica
    Route::get('institucional/{slug}', [Sitio\PaginaController::class, 'institucional'])->name('institucional');
    Route::get('academico/{slug}', [Sitio\PaginaController::class, 'academico'])->name('academico');
    Route::get('plantel-docente', [Sitio\PlantelController::class, 'index'])->name('plantel');
    Route::get('malla-curricular', [Sitio\MallaController::class, 'index'])->name('malla');
    Route::get('calendario-academico', [Sitio\AcademicoController::class, 'calendario'])->name('calendario');
    Route::get('preguntas-frecuentes', [Sitio\AcademicoController::class, 'preguntas'])->name('preguntas');

    // CU4 - Biblioteca digital, investigación y Revista INNOVAR
    Route::get('biblioteca', [Sitio\DocumentoController::class, 'index'])->name('documentos');
    Route::get('investigacion', [Sitio\InvestigacionController::class, 'index'])->name('investigacion');
    Route::get('investigacion/{slug}', [Sitio\InvestigacionController::class, 'show'])->name('investigacion.show');
    Route::get('revista-innovar', [Sitio\RevistaController::class, 'index'])->name('revista');
    Route::get('revista-innovar/normas-para-autores', [Sitio\RevistaController::class, 'normas'])->name('revista.normas');
    Route::get('revista-innovar/{numero}', [Sitio\RevistaController::class, 'show'])->whereNumber('numero')->name('revista.show');

    // CU5 - Vinculación, INNOVA y graduados
    Route::get('vinculacion', [Sitio\VinculacionController::class, 'index'])->name('vinculacion');
    Route::get('innova', [Sitio\InnovaController::class, 'index'])->name('innova');
    Route::get('graduados', [Sitio\GraduadoController::class, 'index'])->name('graduados');
    Route::get('graduados/mi-ficha', [Sitio\GraduadoController::class, 'ficha'])->middleware('auth')->name('graduados.ficha');

    // CU6 - Noticias, eventos y galería
    Route::get('noticias', [Sitio\NoticiaController::class, 'index'])->name('noticias');
    Route::get('noticias/{slug}', [Sitio\NoticiaController::class, 'show'])->name('noticias.show');
    Route::get('eventos', [Sitio\EventoController::class, 'index'])->name('eventos');
    Route::get('eventos/{slug}', [Sitio\EventoController::class, 'show'])->name('eventos.show');
    Route::get('galeria', [Sitio\GaleriaController::class, 'index'])->name('galeria');
    Route::get('galeria/{slug}', [Sitio\GaleriaController::class, 'show'])->name('galeria.show');

    // CU7 - Contacto y búsqueda interna
    Route::get('contacto', [Sitio\ContactoController::class, 'index'])->name('contacto');
    Route::get('buscar', [Sitio\BuscadorController::class, 'index'])->name('buscar');
});

// Envío de formularios públicos (con límite de intentos para frenar envíos automatizados).
Route::middleware('throttle:6,1')->group(function () {
    Route::post('contacto', [Sitio\ContactoController::class, 'store'])->name('contacto.store');
    Route::post('innova/solicitudes', [Sitio\InnovaController::class, 'solicitar'])->name('innova.solicitar');
    Route::post('innova/postulaciones', [Sitio\InnovaController::class, 'postular'])->name('innova.postular');
    Route::post('graduados', [Sitio\GraduadoController::class, 'store'])->middleware('guest')->name('graduados.store');
});
Route::put('graduados/mi-ficha', [Sitio\GraduadoController::class, 'update'])->middleware('auth')->name('graduados.ficha.update');
Route::get('biblioteca/{id}/descargar', [Sitio\DocumentoController::class, 'descargar'])->whereNumber('id')->name('documentos.descargar');

require __DIR__.'/settings.php';

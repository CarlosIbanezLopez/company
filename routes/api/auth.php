<?php

use App\Http\Controllers\Api;
use App\Http\Middleware\CuentaActiva;
use Illuminate\Support\Facades\Route;

/*
| Autenticación por tokens (Laravel Sanctum), perfil del usuario y menú dinámico.
| Cada inicio de sesión exitoso o fallido queda registrado en la bitácora (inicios_sesion).
| CuentaActiva revoca el token de una cuenta deshabilitada después de haber iniciado sesión.
*/
Route::prefix('auth')->name('api.auth.')->group(function () {
    // El límite de intentos lo controla AuthController para registrar también los bloqueos.
    Route::post('login', [Api\AuthController::class, 'login'])->name('login');
    Route::post('olvide-contrasena', [Api\AuthController::class, 'olvide'])->middleware('throttle:6,1,recuperacion')->name('olvide');
    Route::post('restablecer-contrasena', [Api\AuthController::class, 'restablecer'])->middleware('throttle:6,1,recuperacion')->name('restablecer');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [Api\AuthController::class, 'logout'])->name('logout');
        Route::get('usuario', [Api\AuthController::class, 'usuario'])->middleware(CuentaActiva::class)->name('usuario');
    });
});

Route::middleware(['auth:sanctum', CuentaActiva::class])->name('api.')->group(function () {
    // Menú del panel renderizado desde la base de datos según los permisos del rol.
    Route::get('menu', [Api\MenuController::class, 'index'])->name('menu');

    Route::put('perfil', [Api\PerfilController::class, 'update'])->name('perfil.update');
    Route::put('perfil/contrasena', [Api\PerfilController::class, 'contrasena'])->middleware('throttle:6,1,contrasena')->name('perfil.contrasena');
    Route::put('perfil/preferencias', [Api\PerfilController::class, 'preferencias'])->name('perfil.preferencias');
});

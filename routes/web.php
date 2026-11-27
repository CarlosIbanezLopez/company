<?php

use Illuminate\Support\Facades\Route;

/*
| Capa 1 - Presentación: la SPA React se sirve desde una única vista.
| Todas las rutas que no pertenecen a la API (/api), a los archivos públicos (/storage)
| ni al chequeo de salud (/up) devuelven la aplicación React, que resuelve la navegación
| en el navegador (React Router) y consume la API RESTful mediante Axios.
*/
Route::view('/{ruta?}', 'app')
    ->where('ruta', '^(?!api(/|$)|storage(/|$)|up$|build(/|$)).*$')
    ->name('spa');

// Ruta con nombre usada por el correo de restablecimiento de contraseña (la resuelve la SPA).
Route::view('/restablecer-contrasena/{token}', 'app')->name('password.reset');

// Laravel redirige aquí a los usuarios no autenticados que no piden JSON.
Route::view('/login', 'app')->name('login');

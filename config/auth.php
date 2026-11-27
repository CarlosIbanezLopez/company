<?php

use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Guard por defecto
    |--------------------------------------------------------------------------
    |
    | La API (routes/api) se protege con el guard "sanctum" (tokens Bearer):
    | `auth:sanctum` en las rutas privadas y `$request->user('sanctum')` en las
    | públicas con usuario opcional. El guard "web" solo sirve la SPA (sin sesión).
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    |
    | "sanctum": tokens personales de Laravel Sanctum (tabla personal_access_tokens).
    | La vigencia de los tokens se configura en config/sanctum.php.
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Proveedores de usuarios
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Restablecimiento de contraseñas
    |--------------------------------------------------------------------------
    |
    | "expire": minutos de validez del enlace enviado por correo.
    | "throttle": segundos que deben pasar antes de pedir otro enlace.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => (int) env('AUTH_PASSWORD_RESET_EXPIRE', 60),
            'throttle' => (int) env('AUTH_PASSWORD_RESET_THROTTLE', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tiempo de confirmación de contraseña
    |--------------------------------------------------------------------------
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];

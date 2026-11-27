<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;
use Laravel\Sanctum\Sanctum;

/*
| Laravel Sanctum: la SPA React se autentica con tokens Bearer (cabecera
| "Authorization: Bearer {token}") emitidos por POST /api/auth/login.
| La API es stateless: no usa cookies de sesión ni CSRF.
*/

$minutos = fn (string $variable, ?int $defecto) => ($valor = env($variable)) === null || $valor === '' ? $defecto : (int) $valor;

return [

    /*
    |--------------------------------------------------------------------------
    | Dominios con sesión (no se usan)
    |--------------------------------------------------------------------------
    |
    | Solo aplican a SPAs autenticadas con cookies (EnsureFrontendRequestsAreStateful),
    | que este proyecto no activa. Se deja la configuración por defecto.
    |
    */

    'stateful' => explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Guards revisados antes del token
    |--------------------------------------------------------------------------
    |
    | Las rutas de la API no inician sesión, así que en la práctica solo
    | autentica el token Bearer. Se conserva "web" para que las pruebas
    | automatizadas puedan usar actingAs($usuario).
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Vencimiento de los tokens (minutos)
    |--------------------------------------------------------------------------
    |
    | "expiration": límite absoluto para cualquier token desde su creación
    |   (null = sin límite global). Variable SANCTUM_EXPIRATION.
    | "duracion_sesion": vigencia del token de un inicio de sesión normal.
    |   Variable SANCTUM_DURACION_SESION (por defecto 720 = 12 horas; 0 = sin vencimiento).
    | "duracion_recordar": vigencia cuando el usuario marca "Recordarme".
    |   Variable SANCTUM_DURACION_RECORDAR (por defecto 43200 = 30 días; 0 = sin vencimiento).
    |
    | Los tokens vencidos se pueden depurar con: php artisan sanctum:prune-expired --hours=24
    |
    */

    'expiration' => $minutos('SANCTUM_EXPIRATION', null),

    'duracion_sesion' => $minutos('SANCTUM_DURACION_SESION', 720),

    'duracion_recordar' => $minutos('SANCTUM_DURACION_RECORDAR', 43200),

    /*
    |--------------------------------------------------------------------------
    | Prefijo de los tokens
    |--------------------------------------------------------------------------
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Middleware de Sanctum
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],

];

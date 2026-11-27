<?php

use App\Http\Middleware\AccesoPanel;
use App\Http\Middleware\RegistrarAcceso;
use App\Http\Middleware\VerificarPermiso;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
| Arquitectura de 3 capas:
|   Capa 1 (Presentación): SPA React servida por routes/web.php (resources/js).
|   Capa 2 (Lógica de negocio): API RESTful JSON en routes/api.php (prefijo /api).
|   Capa 3 (Persistencia): PostgreSQL con tablas relacionales, triggers y contadores.
*/
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permiso' => VerificarPermiso::class,
            'panel' => AccesoPanel::class,
        ]);

        // Bitácora de tráfico: cada solicitud a la API queda registrada (recurso, IP, usuario, estado).
        $middleware->api(append: [
            RegistrarAcceso::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $esApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($esApi);

        // Respuestas de error de la API con mensajes en español.
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($esApi) {
            if ($esApi($request)) {
                return response()->json(['message' => 'Su sesión no es válida o expiró. Inicie sesión nuevamente.'], 401);
            }
        });

        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) use ($esApi) {
            if ($esApi($request)) {
                $mensaje = $e->getMessage();

                return response()->json([
                    'message' => $mensaje && ! in_array($mensaje, ['This action is unauthorized.', 'Forbidden'], true)
                        ? $mensaje
                        : 'No tiene permiso para realizar esta acción.',
                ], 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) use ($esApi) {
            if ($esApi($request)) {
                return response()->json(['message' => 'El recurso solicitado no existe.'], 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($esApi) {
            if ($esApi($request)) {
                return response()->json(['message' => 'Método HTTP no permitido para este recurso.'], 405);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($esApi) {
            if ($esApi($request)) {
                return response()->json(['message' => 'Demasiadas solicitudes. Espere un momento e intente de nuevo.'], 429, $e->getHeaders());
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) use ($esApi) {
            if ($esApi($request) && $e->getStatusCode() === 419) {
                return response()->json(['message' => 'La solicitud expiró. Recargue la página e intente de nuevo.'], 419);
            }

            if ($esApi($request) && $e->getStatusCode() === 503) {
                return response()->json(['message' => 'El sistema está en mantenimiento. Intente nuevamente en unos minutos.'], 503);
            }
        });

        // Errores no controlados: en producción no se muestran detalles internos, solo un mensaje en español.
        $exceptions->render(function (Throwable $e, Request $request) use ($esApi) {
            $controladas = [
                HttpException::class,
                HttpResponseException::class,
                ValidationException::class,
                AuthenticationException::class,
                AuthorizationException::class,
                ModelNotFoundException::class,
            ];

            foreach ($controladas as $clase) {
                if ($e instanceof $clase) {
                    return null;
                }
            }

            if ($esApi($request) && ! config('app.debug')) {
                return response()->json(['message' => 'Ocurrió un error en el servidor. Intente nuevamente más tarde.'], 500);
            }
        });
    })->create();

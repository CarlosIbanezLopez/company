<?php

namespace App\Http\Middleware;

use App\Models\Acceso;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Bitácora de tráfico de la API (middleware terminable del grupo "api").
 *
 * Después de enviar la respuesta inserta una fila en `accesos` con el usuario (token Sanctum
 * opcional), la IP, el método, la URI y el nombre de la ruta, el código HTTP y la duración.
 * El trigger `trg_accesos_contador` acumula el total por recurso en `contador_recursos`.
 * Nunca interrumpe la respuesta: cualquier error se reporta y se ignora.
 */
class RegistrarAcceso
{
    /** Rutas que no se registran (las visitas de páginas ya quedan en la tabla `visitas`). */
    public const EXCLUIDAS = ['api.sitio.visitas'];

    private const INICIO = 'acceso.inicio';

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::INICIO, microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $ruta = $request->route();

            if ($request->isMethod('OPTIONS') || ! $ruta instanceof Route || in_array($ruta->getName(), self::EXCLUIDAS, true)) {
                return;
            }

            $inicio = (float) ($request->attributes->get(self::INICIO) ?? $request->server('REQUEST_TIME_FLOAT') ?? microtime(true));

            Acceso::query()->forceCreate([
                'user_id' => $this->usuarioId($request),
                'ip' => $request->ip(),
                'metodo' => Str::limit($request->method(), 10, ''),
                'ruta' => Str::limit($ruta->uri(), 191, ''),
                'recurso' => Str::limit($ruta->getName() ?: $ruta->uri(), 150, ''),
                'estado' => $response->getStatusCode(),
                'duracion_ms' => max(0, (int) round((microtime(true) - $inicio) * 1000)),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function usuarioId(Request $request): ?int
    {
        // Inicio de sesión: AuthController deja el id del usuario que acaba de autenticarse.
        if ($id = $request->attributes->get('acceso.user_id')) {
            return (int) $id;
        }

        $id = $request->user('sanctum')?->getAuthIdentifier();

        return $id !== null ? (int) $id : null;
    }
}

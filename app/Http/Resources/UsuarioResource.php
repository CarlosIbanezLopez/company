<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Objeto "Usuario" que la API entrega a la SPA (contrato §4.1).
 *
 * Uso dentro de una respuesta JSON:
 *   'usuario' => UsuarioResource::make($user)->resolve()
 * o directamente como respuesta: `return UsuarioResource::make($user);` → { "usuario": {...} }
 *
 * @mixin User
 */
class UsuarioResource extends JsonResource
{
    /** Clave con la que se envuelve al devolver el recurso como respuesta. */
    public static $wrap = 'usuario';

    /** Valores por defecto de las preferencias de accesibilidad y apariencia. */
    public const PREFERENCIAS_POR_DEFECTO = [
        'tema' => 'adultos',
        'modo' => 'auto',
        'fuente' => 100,
        'contraste' => false,
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $usuario */
        $usuario = $this->resource;
        $usuario->loadMissing(['rol', 'graduado']);

        return [
            'id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'telefono' => $usuario->telefono,
            'rol' => $usuario->rol?->nombre,
            'panel' => (bool) $usuario->activo && $usuario->accesoPanel(),
            'es_graduado' => $usuario->esGraduado(),
            'permisos' => array_values($usuario->permisos()),
            'preferencias' => static::preferencias($usuario->preferencias),
            'ultimo_acceso' => $usuario->ultimo_acceso?->toJSON(),
            'created_at' => $usuario->created_at?->toJSON(),
        ];
    }

    /**
     * Normaliza las preferencias guardadas al formato {tema, modo, fuente, contraste} (o null si no hay).
     *
     * @param  array<string, mixed>|null  $preferencias
     * @return array{tema: string, modo: string, fuente: int, contraste: bool}|null
     */
    public static function preferencias(?array $preferencias): ?array
    {
        if (empty($preferencias)) {
            return null;
        }

        $valores = array_merge(self::PREFERENCIAS_POR_DEFECTO, array_intersect_key($preferencias, self::PREFERENCIAS_POR_DEFECTO));

        return [
            'tema' => (string) $valores['tema'],
            'modo' => (string) $valores['modo'],
            'fuente' => (int) $valores['fuente'],
            'contraste' => (bool) $valores['contraste'],
        ];
    }
}

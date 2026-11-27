<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Historial de operaciones realizadas por usuarios con permisos administrativos (RF-19).
 */
class Bitacora extends Model
{
    protected $table = 'bitacora';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'accion',
        'modulo',
        'descripcion',
        'registro_id',
        'ip',
        'agente',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Guarda una acción en la bitácora.
     *
     * Por defecto solo registra acciones de usuarios autenticados: el indicado en $userId o,
     * si no se indica, el autenticado (guard por defecto o token Sanctum de la API).
     * Con $anonimo = true también registra acciones sin usuario (user_id NULL).
     */
    public static function registrar(string $accion, string $modulo, string $descripcion, ?int $registroId = null, ?int $userId = null, bool $anonimo = false): void
    {
        $userId ??= Auth::id() ?? Auth::guard('sanctum')->id();

        if (! $userId && ! $anonimo) {
            return;
        }

        static::query()->create([
            'user_id' => $userId,
            'accion' => $accion,
            'modulo' => $modulo,
            'descripcion' => Str::limit($descripcion, 495),
            'registro_id' => $registroId,
            'ip' => Request::ip(),
            'agente' => Str::limit((string) Request::userAgent(), 250),
        ]);
    }
}

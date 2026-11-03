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
            'created_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Guarda una acción en la bitácora. Solo registra acciones de usuarios autenticados.
     */
    public static function registrar(string $accion, string $modulo, string $descripcion, ?int $registroId = null, ?int $userId = null): void
    {
        $userId ??= Auth::id();

        if (! $userId) {
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

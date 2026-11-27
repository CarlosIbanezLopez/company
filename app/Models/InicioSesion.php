<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Intento de inicio de sesión (exitoso, fallido o bloqueado) para la bitácora de autenticación.
 * Al insertarlo, el trigger trg_inicios_sesion_usuario actualiza users.ultimo_acceso
 * (intento exitoso) o suma users.intentos_fallidos (intento fallido de un usuario existente).
 */
class InicioSesion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'inicios_sesion';

    protected $fillable = [
        'user_id',
        'email',
        'exitoso',
        'motivo',
        'ip',
        'agente',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'exitoso' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

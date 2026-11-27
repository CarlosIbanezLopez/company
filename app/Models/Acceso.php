<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Solicitud atendida por la API REST (tráfico por usuario, IP y recurso).
 * Al insertarla, el trigger trg_accesos_contador suma el acceso en contador_recursos.
 */
class Acceso extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'accesos';

    protected $fillable = [
        'user_id',
        'ip',
        'metodo',
        'ruta',
        'recurso',
        'estado',
        'duracion_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'integer',
            'duracion_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

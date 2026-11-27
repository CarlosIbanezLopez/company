<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Página vista en la SPA (registro crudo, una fila por visita).
 * Al insertarla, el trigger trg_visitas_contador suma la visita en
 * contador_visitas y visitas_diarias: la aplicación nunca incrementa esos contadores.
 */
class Visita extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'visitas';

    protected $fillable = [
        'ruta',
        'user_id',
        'ip',
        'agente',
        'created_at',
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
}

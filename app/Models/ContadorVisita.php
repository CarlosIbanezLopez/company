<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Total histórico de visitas por página de la SPA.
 * Lo mantiene el trigger trg_visitas_contador al insertar en visitas:
 * la aplicación solo lo lee (no incrementa el total por su cuenta).
 */
class ContadorVisita extends Model
{
    protected $table = 'contador_visitas';

    protected $fillable = [
        'ruta',
        'total',
        'ultima_visita',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'ultima_visita' => 'datetime',
        ];
    }
}

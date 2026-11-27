<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Total de visitas por día y página de la SPA (fecha + ruta únicas).
 * Lo mantiene el trigger trg_visitas_contador al insertar en visitas:
 * la aplicación solo lo lee (no incrementa el total por su cuenta).
 */
class VisitaDiaria extends Model
{
    protected $table = 'visitas_diarias';

    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'ruta',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
        ];
    }
}

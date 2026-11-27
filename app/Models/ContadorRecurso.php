<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Total de solicitudes por recurso de la API y método HTTP.
 * Lo mantiene el trigger trg_accesos_contador (solo lectura para la aplicación).
 */
class ContadorRecurso extends Model
{
    protected $table = 'contador_recursos';

    protected $fillable = [
        'recurso',
        'metodo',
        'ruta',
        'total',
        'ultimo_acceso',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'ultimo_acceso' => 'datetime',
        ];
    }
}

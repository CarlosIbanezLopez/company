<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
            'ultima_visita' => 'datetime',
        ];
    }
}

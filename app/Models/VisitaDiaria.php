<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitaDiaria extends Model
{
    protected $table = 'visitas_diarias';

    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'ruta',
        'total',
    ];
}

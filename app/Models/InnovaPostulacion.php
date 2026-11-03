<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;

class InnovaPostulacion extends Model
{
    use RegistraBitacora;

    protected $table = 'innova_postulaciones';

    protected string $moduloBitacora = 'Empresa Junior INNOVA';

    protected $fillable = [
        'nombre',
        'registro',
        'correo',
        'telefono',
        'semestre',
        'motivacion',
        'estado',
        'observacion',
    ];
}

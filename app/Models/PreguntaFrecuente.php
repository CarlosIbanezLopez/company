<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;

class PreguntaFrecuente extends Model
{
    use RegistraBitacora;

    protected $table = 'preguntas_frecuentes';

    protected string $moduloBitacora = 'Preguntas frecuentes';

    protected $fillable = [
        'pregunta',
        'respuesta',
        'categoria',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}

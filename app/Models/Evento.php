<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'eventos';

    protected string $moduloBitacora = 'Eventos';

    protected $fillable = [
        'titulo',
        'slug',
        'tipo',
        'descripcion',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'lugar',
        'afiche',
        'enlace_inscripcion',
        'publicado',
    ];

    /** Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['afiche'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'publicado' => 'boolean',
        ];
    }
}

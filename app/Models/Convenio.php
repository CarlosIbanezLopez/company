<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;

class Convenio extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'convenios';

    protected string $moduloBitacora = 'Vinculación';

    protected $fillable = [
        'institucion',
        'tipo',
        'finalidad',
        'fecha_inicio',
        'fecha_fin',
        'logo',
        'estado',
        'publicado',
    ];

    /** Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['logo'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
            'publicado' => 'boolean',
        ];
    }
}

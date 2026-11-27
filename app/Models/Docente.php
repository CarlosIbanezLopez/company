<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;

class Docente extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'docentes';

    protected string $moduloBitacora = 'Plantel docente';

    protected $fillable = [
        'nombre',
        'tipo',
        'cargo',
        'formacion',
        'especialidad',
        'materias',
        'correo',
        'foto',
        'mostrar_correo',
        'mostrar_formacion',
        'visible',
        'orden',
    ];

    /** @var array<int, string> Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['foto'];

    protected function casts(): array
    {
        return [
            'mostrar_correo' => 'boolean',
            'mostrar_formacion' => 'boolean',
            'visible' => 'boolean',
        ];
    }
}

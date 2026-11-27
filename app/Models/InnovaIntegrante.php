<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;

class InnovaIntegrante extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'innova_integrantes';

    protected string $moduloBitacora = 'Empresa Junior INNOVA';

    protected $fillable = [
        'nombre',
        'cargo',
        'semestre',
        'foto',
        'orden',
        'activo',
    ];

    /** @var array<int, string> Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['foto'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}

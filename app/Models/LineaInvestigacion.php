<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LineaInvestigacion extends Model
{
    use RegistraBitacora;

    protected $table = 'lineas_investigacion';

    protected string $moduloBitacora = 'Investigación';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /** @return HasMany<ProyectoInvestigacion, $this> */
    public function proyectos(): HasMany
    {
        return $this->hasMany(ProyectoInvestigacion::class, 'linea_investigacion_id');
    }
}

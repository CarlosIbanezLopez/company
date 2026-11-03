<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InnovaServicio extends Model
{
    use RegistraBitacora;

    protected $table = 'innova_servicios';

    protected string $moduloBitacora = 'Empresa Junior INNOVA';

    protected $fillable = [
        'nombre',
        'descripcion',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(InnovaSolicitud::class, 'innova_servicio_id');
    }
}

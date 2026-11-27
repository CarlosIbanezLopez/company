<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InnovaSolicitud extends Model
{
    use RegistraBitacora;

    protected $table = 'innova_solicitudes';

    protected string $moduloBitacora = 'Empresa Junior INNOVA';

    protected $fillable = [
        'innova_servicio_id',
        'empresa',
        'contacto',
        'correo',
        'telefono',
        'mensaje',
        'estado',
        'observacion',
    ];

    /** @return BelongsTo<InnovaServicio, $this> */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(InnovaServicio::class, 'innova_servicio_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consulta extends Model
{
    use RegistraBitacora;

    protected $table = 'consultas';

    protected string $moduloBitacora = 'Consultas';

    protected $fillable = [
        'categoria_consulta_id',
        'nombre',
        'correo',
        'telefono',
        'mensaje',
        'estado',
        'respuesta',
        'ip',
    ];

    /** @return BelongsTo<CategoriaConsulta, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaConsulta::class, 'categoria_consulta_id');
    }
}

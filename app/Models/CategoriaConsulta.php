<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaConsulta extends Model
{
    use RegistraBitacora;

    protected $table = 'categorias_consulta';

    protected string $moduloBitacora = 'Consultas';

    protected $fillable = [
        'nombre',
        'correo_destino',
    ];

    /** @return HasMany<Consulta, $this> */
    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'categoria_consulta_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Graduado extends Model
{
    use RegistraBitacora;

    protected $table = 'graduados';

    protected string $moduloBitacora = 'Graduados';

    protected $fillable = [
        'user_id',
        'nombre',
        'ci',
        'registro',
        'anio_graduacion',
        'modalidad_titulacion',
        'situacion_laboral',
        'empresa',
        'cargo',
        'ciudad',
        'correo',
        'telefono',
        'linkedin',
    ];

    protected function casts(): array
    {
        return [
            'anio_graduacion' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Materia extends Model
{
    use RegistraBitacora;

    protected $table = 'materias';

    protected string $moduloBitacora = 'Malla curricular';

    protected $fillable = [
        'sigla',
        'nombre',
        'semestre',
        'creditos',
        'horas_teoricas',
        'horas_practicas',
        'tipo',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'semestre' => 'integer',
            'creditos' => 'integer',
            'horas_teoricas' => 'integer',
            'horas_practicas' => 'integer',
        ];
    }

    /** @return BelongsToMany<Materia, $this> */
    public function prerequisitos(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'materia_prerequisito', 'materia_id', 'prerequisito_id');
    }

    /** @return BelongsToMany<Materia, $this> */
    public function posteriores(): BelongsToMany
    {
        return $this->belongsToMany(Materia::class, 'materia_prerequisito', 'prerequisito_id', 'materia_id');
    }
}

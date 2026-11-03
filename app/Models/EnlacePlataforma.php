<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;

class EnlacePlataforma extends Model
{
    use RegistraBitacora;

    protected $table = 'enlaces_plataforma';

    protected string $moduloBitacora = 'Portal';

    protected $fillable = [
        'nombre',
        'url',
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
}

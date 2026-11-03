<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;

class Destacado extends Model
{
    use RegistraBitacora;

    protected $table = 'destacados';

    protected string $moduloBitacora = 'Portal';

    protected $fillable = [
        'titulo',
        'descripcion',
        'valor',
        'enlace',
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

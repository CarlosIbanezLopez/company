<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;

class InnovaProyecto extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'innova_proyectos';

    protected string $moduloBitacora = 'Empresa Junior INNOVA';

    protected $fillable = [
        'titulo',
        'cliente',
        'descripcion',
        'fecha',
        'imagen',
        'publicado',
    ];

    /** @var array<int, string> Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['imagen'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'publicado' => 'boolean',
        ];
    }
}

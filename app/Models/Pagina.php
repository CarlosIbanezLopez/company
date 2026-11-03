<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;

class Pagina extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'paginas';

    protected string $moduloBitacora = 'Contenido';

    protected $fillable = [
        'seccion',
        'slug',
        'titulo',
        'resumen',
        'contenido',
        'imagen',
        'orden',
        'publicado',
    ];

    /** Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['imagen'];

    protected function casts(): array
    {
        return [
            'publicado' => 'boolean',
        ];
    }
}

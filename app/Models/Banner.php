<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'banners';

    protected string $moduloBitacora = 'Portal';

    protected $fillable = [
        'titulo',
        'subtitulo',
        'imagen',
        'enlace',
        'texto_boton',
        'orden',
        'activo',
    ];

    /** @var array<int, string> Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['imagen'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}

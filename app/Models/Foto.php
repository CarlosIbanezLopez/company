<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Foto extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'fotos';

    protected string $moduloBitacora = 'Galería multimedia';

    protected $fillable = [
        'album_id',
        'imagen',
        'descripcion',
        'orden',
    ];

    /** Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['imagen'];

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class, 'album_id');
    }
}

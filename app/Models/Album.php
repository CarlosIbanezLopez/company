<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Album extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'albumes';

    protected string $moduloBitacora = 'Galería multimedia';

    protected $fillable = [
        'titulo',
        'slug',
        'descripcion',
        'fecha',
        'portada',
        'publicado',
    ];

    /** Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['portada'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'publicado' => 'boolean',
        ];
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class, 'album_id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'album_id');
    }
}

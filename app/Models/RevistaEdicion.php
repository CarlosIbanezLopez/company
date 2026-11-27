<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RevistaEdicion extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'revista_ediciones';

    protected string $moduloBitacora = 'Revista INNOVAR';

    protected $fillable = [
        'numero',
        'titulo',
        'fecha_publicacion',
        'descripcion',
        'portada',
        'archivo_pdf',
        'publicado',
    ];

    /** @var array<int, string> Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['portada', 'archivo_pdf'];

    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'date:Y-m-d',
            'publicado' => 'boolean',
            'numero' => 'integer',
        ];
    }

    /** @return HasMany<RevistaArticulo, $this> */
    public function articulos(): HasMany
    {
        return $this->hasMany(RevistaArticulo::class, 'revista_edicion_id');
    }
}

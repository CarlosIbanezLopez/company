<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevistaArticulo extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'revista_articulos';

    protected string $moduloBitacora = 'Revista INNOVAR';

    protected $fillable = [
        'revista_edicion_id',
        'titulo',
        'autores',
        'resumen',
        'palabras_clave',
        'archivo_pdf',
        'orden',
    ];

    /** @var array<int, string> Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['archivo_pdf'];

    /** @return BelongsTo<RevistaEdicion, $this> */
    public function edicion(): BelongsTo
    {
        return $this->belongsTo(RevistaEdicion::class, 'revista_edicion_id');
    }
}

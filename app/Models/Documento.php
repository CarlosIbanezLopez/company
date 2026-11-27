<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documento extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'documentos';

    protected string $moduloBitacora = 'Biblioteca digital';

    protected $fillable = [
        'categoria_documento_id',
        'titulo',
        'descripcion',
        'archivo',
        'fecha_publicacion',
        'descargas',
        'publicado',
    ];

    /** @var array<int, string> Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['archivo'];

    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'date:Y-m-d',
            'publicado' => 'boolean',
            'descargas' => 'integer',
        ];
    }

    /** @return BelongsTo<CategoriaDocumento, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaDocumento::class, 'categoria_documento_id');
    }
}

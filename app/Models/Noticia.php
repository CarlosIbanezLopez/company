<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Noticia extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'noticias';

    protected string $moduloBitacora = 'Noticias';

    protected $fillable = [
        'categoria_noticia_id',
        'user_id',
        'titulo',
        'slug',
        'resumen',
        'contenido',
        'imagen',
        'fecha_publicacion',
        'estado',
        'destacado',
    ];

    /** Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['imagen'];

    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'date:Y-m-d',
            'destacado' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaNoticia::class, 'categoria_noticia_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

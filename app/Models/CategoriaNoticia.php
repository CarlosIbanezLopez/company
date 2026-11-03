<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaNoticia extends Model
{
    use RegistraBitacora;

    protected $table = 'categorias_noticia';

    protected string $moduloBitacora = 'Noticias';

    protected $fillable = [
        'nombre',
        'slug',
    ];

    public function noticias(): HasMany
    {
        return $this->hasMany(Noticia::class, 'categoria_noticia_id');
    }
}

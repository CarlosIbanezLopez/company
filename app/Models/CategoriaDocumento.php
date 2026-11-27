<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaDocumento extends Model
{
    use RegistraBitacora;

    protected $table = 'categorias_documento';

    protected string $moduloBitacora = 'Biblioteca digital';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
    ];

    /** @return HasMany<Documento, $this> */
    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'categoria_documento_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use RegistraBitacora;

    protected $table = 'menu_items';

    protected string $moduloBitacora = 'Menú';

    protected $fillable = [
        'etiqueta',
        'ruta',
        'icono',
        'permiso',
        'padre_id',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'padre_id');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    use RegistraBitacora;

    protected $table = 'roles';

    protected string $moduloBitacora = 'Usuarios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'acceso_panel',
    ];

    protected function casts(): array
    {
        return [
            'acceso_panel' => 'boolean',
        ];
    }

    /** @return BelongsToMany<Permiso, $this> */
    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'rol_id', 'permiso_id');
    }

    /** @return HasMany<User, $this> */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}

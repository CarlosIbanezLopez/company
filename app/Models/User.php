<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Cuenta de usuario del sistema (RF-18). Su rol define los permisos.
 */
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, RegistraBitacora, TwoFactorAuthenticatable;

    protected string $moduloBitacora = 'Usuarios';

    protected $fillable = [
        'name',
        'email',
        'telefono',
        'rol_id',
        'activo',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /** @var array<int, string>|null */
    private ?array $permisosCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function graduado(): HasOne
    {
        return $this->hasOne(Graduado::class, 'user_id');
    }

    public function esAdministrador(): bool
    {
        return $this->rol?->nombre === 'Administrador';
    }

    public function accesoPanel(): bool
    {
        return (bool) $this->rol?->acceso_panel;
    }

    /** @return array<int, string> */
    public function permisos(): array
    {
        if ($this->permisosCache !== null) {
            return $this->permisosCache;
        }

        if ($this->esAdministrador()) {
            return $this->permisosCache = Permiso::query()->pluck('nombre')->all();
        }

        return $this->permisosCache = $this->rol?->permisos()->pluck('nombre')->all() ?? [];
    }

    public function tienePermiso(string $permiso): bool
    {
        return $this->esAdministrador() || in_array($permiso, $this->permisos(), true);
    }
}

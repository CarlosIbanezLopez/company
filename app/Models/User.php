<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;

/**
 * Cuenta de usuario del sistema (RF-18). Su rol define los permisos.
 * Se autentica contra la API REST mediante tokens de Laravel Sanctum.
 *
 * @property array<string, mixed>|null $preferencias tema, modo, fuente y contraste
 * @property CarbonInterface|null $ultimo_acceso lo mantiene el trigger trg_inicios_sesion_usuario
 * @property int $intentos_fallidos lo mantiene el trigger trg_inicios_sesion_usuario
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, RegistraBitacora;

    protected string $moduloBitacora = 'Usuarios';

    protected $fillable = [
        'name',
        'email',
        'telefono',
        'rol_id',
        'activo',
        'password',
        'email_verified_at',
        'preferencias',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var array<int, string>|null */
    private ?array $permisosCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'preferencias' => 'array',
            'ultimo_acceso' => 'datetime',
            'intentos_fallidos' => 'integer',
        ];
    }

    /** @return BelongsTo<Rol, $this> */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    /** @return HasOne<Graduado, $this> */
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

    /** Titulado: tiene ficha de graduado o el rol Graduado. */
    public function esGraduado(): bool
    {
        if ($this->rol?->nombre === 'Graduado') {
            return true;
        }

        return $this->relationLoaded('graduado') ? $this->graduado !== null : $this->graduado()->exists();
    }

    /**
     * Emite un token Bearer de Sanctum para la SPA (inicio de sesión o registro de graduados).
     * Vence a los `sanctum.duracion_sesion` minutos, o a los `sanctum.duracion_recordar` si el
     * usuario marcó "recordarme" (0 = sin vencimiento).
     */
    public function emitirToken(bool $recordar = false, ?string $nombre = null): string
    {
        $minutos = (int) config($recordar ? 'sanctum.duracion_recordar' : 'sanctum.duracion_sesion', 0);
        $nombre = trim((string) $nombre);

        return $this->createToken(
            Str::limit($nombre !== '' ? $nombre : 'SPA', 120, ''),
            ['*'],
            $minutos > 0 ? now()->addMinutes($minutos) : null,
        )->plainTextToken;
    }

    /**
     * Token con el que se autenticó la solicitud actual: persistente (Bearer), transitorio
     * (autenticación por sesión del guard "web") o ninguno si el usuario no viene de Sanctum.
     */
    public function tokenActual(): PersonalAccessToken|TransientToken|null
    {
        return $this->accessToken;
    }

    /** Revoca el token Bearer con el que se hizo la solicitud actual (los transitorios no se guardan). */
    public function revocarTokenActual(): void
    {
        $token = $this->tokenActual();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /** Revoca todos los tokens del usuario salvo, opcionalmente, el de la solicitud actual. */
    public function revocarTokens(bool $conservarActual = false): void
    {
        $actual = $conservarActual ? $this->tokenActual() : null;
        $idActual = $actual instanceof PersonalAccessToken ? $actual->getKey() : null;

        $this->tokens()
            ->when($idActual !== null, fn ($consulta) => $consulta->whereKeyNot($idActual))
            ->delete();
    }

    /** @return array<int, string> */
    public function permisos(): array
    {
        if ($this->permisosCache !== null) {
            return $this->permisosCache;
        }

        if ($this->esAdministrador()) {
            return $this->permisosCache = Permiso::query()->orderBy('nombre')->pluck('nombre')->all();
        }

        return $this->permisosCache = $this->rol?->permisos()->orderBy('nombre')->pluck('nombre')->all() ?? [];
    }

    public function tienePermiso(string $permiso): bool
    {
        return $this->esAdministrador() || in_array($permiso, $this->permisos(), true);
    }
}

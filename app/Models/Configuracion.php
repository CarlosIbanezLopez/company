<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Parámetros del portal en formato clave/valor: mensaje de presentación,
 * datos de contacto, redes sociales y correo de notificaciones (RF-01, RF-16).
 */
class Configuracion extends Model
{
    use RegistraBitacora;

    protected $table = 'configuraciones';

    protected string $moduloBitacora = 'Configuración del portal';

    protected $fillable = ['clave', 'valor', 'grupo'];

    private const CACHE = 'configuraciones.todas';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE));
        static::deleted(fn () => Cache::forget(self::CACHE));
    }

    /** @return array<string, string|null> */
    public static function todas(): array
    {
        return Cache::remember(self::CACHE, 600, fn () => static::query()->pluck('valor', 'clave')->all());
    }

    public static function valor(string $clave, ?string $defecto = null): ?string
    {
        return static::todas()[$clave] ?? $defecto;
    }

    public static function guardar(string $clave, ?string $valor, string $grupo = 'general'): void
    {
        $registro = static::query()->firstOrNew(['clave' => $clave]);
        $registro->grupo = $grupo;
        $registro->valor = $valor;

        if ($registro->isDirty()) {
            $registro->save();
        }
    }

    public function etiquetaBitacora(): string
    {
        return $this->clave;
    }
}

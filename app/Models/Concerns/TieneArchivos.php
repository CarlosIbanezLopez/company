<?php

namespace App\Models\Concerns;

use App\Support\Archivo;
use Illuminate\Support\Facades\Storage;

/**
 * Agrega la URL pública y absoluta de cada archivo ({campo}_url) al serializar a JSON
 * y elimina los archivos del disco cuando se borra el registro.
 *
 * El modelo declara los campos con archivo en `protected array $archivos = ['imagen'];`.
 */
trait TieneArchivos
{
    public static function bootTieneArchivos(): void
    {
        static::deleted(function ($modelo) {
            foreach ($modelo->camposArchivo() as $campo) {
                $ruta = $modelo->getAttribute($campo);

                if ($ruta && ! str_starts_with($ruta, 'http')) {
                    Storage::disk('public')->delete($ruta);
                }
            }
        });
    }

    /** @return array<int, string> */
    public function camposArchivo(): array
    {
        return $this->archivos;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $datos = parent::toArray();

        foreach ($this->camposArchivo() as $campo) {
            $datos[$campo.'_url'] = Archivo::url($this->getAttribute($campo));
        }

        return $datos;
    }
}

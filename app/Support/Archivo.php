<?php

namespace App\Support;

class Archivo
{
    /**
     * URL pública de un archivo guardado en storage/app/public.
     */
    public static function url(?string $ruta): ?string
    {
        if (! $ruta) {
            return null;
        }

        if (str_starts_with($ruta, 'http://') || str_starts_with($ruta, 'https://')) {
            return $ruta;
        }

        return asset('storage/'.ltrim($ruta, '/'));
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Str;

class Markdown
{
    /**
     * Convierte el contenido escrito en Markdown a HTML seguro (sin HTML incrustado).
     */
    public static function html(?string $texto): string
    {
        if (! $texto) {
            return '';
        }

        return Str::markdown($texto, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}

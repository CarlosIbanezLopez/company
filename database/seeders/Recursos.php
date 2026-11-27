<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Genera imágenes y PDF de ejemplo para que el sitio se vea completo
 * después de instalarlo. Deben reemplazarse por el material oficial.
 */
class Recursos
{
    private const PALETAS = [
        ['#0f2a5c', '#1d4ed8'],
        ['#7a1426', '#b91c1c'],
        ['#0f3d3e', '#0e7490'],
        ['#3b2a63', '#6d28d9'],
        ['#3d2e0f', '#b45309'],
        ['#1f2937', '#475569'],
    ];

    /**
     * @param  positive-int  $ancho
     * @param  positive-int  $alto
     */
    public static function imagen(string $carpeta, string $texto, int $ancho = 1200, int $alto = 675, ?int $paleta = null, ?string $subtexto = null, bool $conTexto = false): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $paleta ??= abs(crc32($texto)) % count(self::PALETAS);
        [$c1, $c2] = self::PALETAS[$paleta % count(self::PALETAS)];

        $img = imagecreatetruecolor($ancho, $alto);

        if ($img === false) {
            return null;
        }

        [$r1, $g1, $b1] = sscanf($c1, '#%02x%02x%02x');
        [$r2, $g2, $b2] = sscanf($c2, '#%02x%02x%02x');

        // Degradado diagonal simple.
        for ($x = 0; $x < $ancho; $x += 4) {
            $t = $x / $ancho;
            $color = self::color($img, self::canal($r1 + ($r2 - $r1) * $t), self::canal($g1 + ($g2 - $g1) * $t), self::canal($b1 + ($b2 - $b1) * $t));
            imagefilledrectangle($img, $x, 0, $x + 3, $alto, $color);
        }

        // Bloques geométricos rectos (sin bordes redondeados).
        $claro = self::color($img, 255, 255, 255, 110);
        imagefilledrectangle($img, (int) ($ancho * 0.62), (int) ($alto * 0.12), (int) ($ancho * 0.92), (int) ($alto * 0.88), $claro);
        imagefilledrectangle($img, (int) ($ancho * 0.70), (int) ($alto * 0.22), (int) ($ancho * 0.98), (int) ($alto * 0.62), $claro);

        $blanco = self::color($img, 255, 255, 255);
        $fuente = __DIR__.'/recursos/fuente.ttf';

        if ($conTexto && is_file($fuente) && function_exists('imagettftext')) {
            $tam = max(16, (int) ($alto / 13));
            $lineas = explode("\n", wordwrap($texto, (int) max(12, $ancho / ($tam * 0.75)), "\n", true));
            $y = (int) ($alto * 0.5) - (count($lineas) - 1) * (int) ($tam * 0.75);

            foreach (array_slice($lineas, 0, 4) as $linea) {
                imagettftext($img, $tam, 0, (int) ($ancho * 0.06), $y, $blanco, $fuente, $linea);
                $y += (int) ($tam * 1.5);
            }

            if ($subtexto) {
                imagettftext($img, (int) ($tam * 0.45), 0, (int) ($ancho * 0.06), $y + (int) ($tam * 0.3), $blanco, $fuente, $subtexto);
            }
        }

        ob_start();
        imagejpeg($img, null, 82);
        $contenido = ob_get_clean();
        imagedestroy($img);

        // Nombre determinista: volver a sembrar sobrescribe el mismo archivo en lugar de acumular copias.
        $huella = substr(md5(serialize([$carpeta, $texto, $ancho, $alto, $paleta, $subtexto, $conTexto])), 0, 6);
        $ruta = trim($carpeta, '/').'/'.Str::slug(Str::limit($texto, 40, '')).'-'.$huella.'.jpg';
        Storage::disk('public')->put($ruta, $contenido);

        return $ruta;
    }

    /**
     * Componente de color (rojo, verde o azul) dentro del rango válido 0..255.
     *
     * @return int<0, 255>
     */
    private static function canal(float|int $valor): int
    {
        return max(0, min(255, (int) $valor));
    }

    /**
     * Reserva un color en la imagen (alfa 0 = opaco, 127 = transparente).
     *
     * @param  int<0, 255>  $rojo
     * @param  int<0, 255>  $verde
     * @param  int<0, 255>  $azul
     * @param  int<0, 127>  $alfa
     */
    private static function color(\GdImage $img, int $rojo, int $verde, int $azul, int $alfa = 0): int
    {
        $color = imagecolorallocatealpha($img, $rojo, $verde, $azul, $alfa);

        if ($color === false) {
            throw new \RuntimeException('No se pudo reservar un color para la imagen de ejemplo.');
        }

        return $color;
    }

    /**
     * PDF mínimo de una página con un título y un párrafo.
     */
    public static function pdf(string $carpeta, string $titulo, string $texto = 'Documento de ejemplo generado por el sistema. Reemplácelo por el documento oficial.'): string
    {
        $limpiar = fn (string $s) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], iconv('UTF-8', 'windows-1252//TRANSLIT', $s) ?: $s);

        $lineas = explode("\n", wordwrap($texto, 85, "\n", true));
        $flujo = 'BT /F1 18 Tf 60 770 Td ('.$limpiar(Str::limit($titulo, 60)).") Tj ET\n";
        $y = 730;
        foreach ($lineas as $linea) {
            $flujo .= "BT /F2 11 Tf 60 $y Td (".$limpiar($linea).") Tj ET\n";
            $y -= 16;
        }

        $objetos = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>',
            '<< /Length '.strlen($flujo)." >>\nstream\n".$flujo.'endstream',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objetos as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$obj."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objetos) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= "trailer\n<< /Size ".(count($objetos) + 1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

        $huella = substr(md5(serialize([$carpeta, $titulo, $texto])), 0, 6);
        $ruta = trim($carpeta, '/').'/'.Str::slug(Str::limit($titulo, 50, '')).'-'.$huella.'.pdf';
        Storage::disk('public')->put($ruta, $pdf);

        return $ruta;
    }
}

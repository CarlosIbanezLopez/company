<?php

namespace App\Concerns;

use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Utilidades para las consultas estadísticas del panel (tablero, reportes y bitácora).
 * Las expresiones SQL de fecha y hora dependen del motor para funcionar igual en
 * PostgreSQL (producción), SQLite (pruebas) y MySQL.
 */
trait ConsultasAnaliticas
{
    /**
     * Expresión SQL que obtiene la fecha (YYYY-MM-DD) de una columna timestamp.
     *
     * @param  literal-string  $columna
     * @return literal-string
     */
    protected function sqlFecha(string $columna): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "date({$columna})",
            default => "CAST({$columna} AS DATE)",
        };
    }

    /**
     * Expresión SQL que obtiene la hora (0..23) de una columna timestamp.
     *
     * @param  literal-string  $columna
     * @return literal-string
     */
    protected function sqlHora(string $columna): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%H', {$columna}) AS INTEGER)",
            'mysql', 'mariadb' => "HOUR({$columna})",
            default => "CAST(EXTRACT(HOUR FROM {$columna}) AS INTEGER)",
        };
    }

    /** Normaliza una fecha devuelta por la base de datos a YYYY-MM-DD. */
    protected function claveFecha(mixed $fecha): string
    {
        return Carbon::parse((string) $fecha)->toDateString();
    }

    /**
     * Serie diaria continua entre dos fechas (los días sin datos valen 0).
     *
     * @param  iterable<string, int|string>  $totales  fecha => total
     * @return array<int, array{fecha: string, total: int}>
     */
    protected function serieDiaria(CarbonInterface $desde, CarbonInterface $hasta, iterable $totales): array
    {
        $porFecha = [];

        foreach ($totales as $fecha => $total) {
            $porFecha[$this->claveFecha($fecha)] = (int) $total;
        }

        $serie = [];

        foreach (CarbonPeriod::create($desde->toDateString(), $hasta->toDateString()) as $dia) {
            $serie[] = ['fecha' => $dia->toDateString(), 'total' => $porFecha[$dia->toDateString()] ?? 0];
        }

        return $serie;
    }

    /**
     * Convierte totales agrupados por clave en [{etiqueta, total}] usando las etiquetas de un catálogo.
     * Con $completar se incluyen también las claves del catálogo sin registros (total 0).
     *
     * @param  iterable<string|int|null, int|string>  $totales
     * @param  array<string, string>  $etiquetas
     * @return array<int, array{etiqueta: string, total: int}>
     */
    protected function conEtiquetas(iterable $totales, array $etiquetas, bool $completar = false, string $sinDato = 'Sin dato'): array
    {
        $resultado = $completar ? array_fill_keys(array_keys($etiquetas), 0) : [];

        foreach ($totales as $clave => $total) {
            $clave = ($clave === null || $clave === '') ? '' : (string) $clave;
            $resultado[$clave] = ($resultado[$clave] ?? 0) + (int) $total;
        }

        $filas = [];

        foreach ($resultado as $clave => $total) {
            $filas[] = ['etiqueta' => $clave === '' ? $sinDato : ($etiquetas[$clave] ?? (string) $clave), 'total' => $total];
        }

        return $filas;
    }

    /** Fecha y hora ISO 8601 (como las serializa Eloquent) de un valor devuelto por la base de datos. */
    protected function fechaIso(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return $valor instanceof CarbonInterface ? $valor->toJSON() : Carbon::parse((string) $valor)->toJSON();
    }
}

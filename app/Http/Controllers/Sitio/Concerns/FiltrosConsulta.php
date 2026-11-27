<?php

namespace App\Http\Controllers\Sitio\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Lectura segura de los filtros que llegan por la URL (?q=&categoria=&linea=...) y búsqueda de texto.
 *
 * - Evita errores de base de datos (p. ej. en PostgreSQL al comparar una columna numérica con texto)
 *   cuando el parámetro llega vacío, como arreglo o con un valor no numérico.
 * - En PostgreSQL la búsqueda ignora mayúsculas y acentos ("gestion" encuentra "Gestión"), como
 *   ocurría con la intercalación de MySQL. Solo debe usarse sobre columnas de texto.
 */
trait FiltrosConsulta
{
    private const CON_ACENTOS = 'áàâäãéèêëíìîïóòôöõúùûüñçÁÀÂÄÃÉÈÊËÍÌÎÏÓÒÔÖÕÚÙÛÜÑÇ';

    private const SIN_ACENTOS = 'aaaaaeeeeiiiiooooouuuuncaaaaaeeeeiiiiooooouuuunc';

    /** Texto de un parámetro de la consulta, recortado; null si no es un texto con contenido. */
    protected function texto(Request $request, string $clave, int $maximo = 100): ?string
    {
        $valor = $request->query($clave);

        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        return mb_substr(trim($valor), 0, $maximo);
    }

    /** Entero positivo de un parámetro de la consulta; null si no es un número válido. */
    protected function entero(Request $request, string $clave): ?int
    {
        $valor = filter_var($request->query($clave), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $valor === false ? null : $valor;
    }

    /**
     * Agrega "alguna de las columnas contiene el texto" a la consulta.
     *
     * @template TModelo of Model
     *
     * @param  Builder<TModelo>  $consulta
     * @param  array<int, literal-string>  $columnas  columnas de texto (nombres simples, sin comillas)
     * @return Builder<TModelo>
     */
    protected function contiene(Builder $consulta, array $columnas, string $texto): Builder
    {
        $postgres = $this->esPostgres($consulta);
        $patron = '%'.$this->textoNormalizado($consulta, $texto).'%';

        return $consulta->where(function (Builder $q) use ($columnas, $texto, $postgres, $patron) {
            foreach ($columnas as $columna) {
                if ($postgres) {
                    $q->orWhereRaw($this->columnaNormalizada($q, $columna).' like ?', [$patron]);
                } else {
                    $q->orWhereLike($columna, '%'.$texto.'%');
                }
            }
        });
    }

    /**
     * Expresión SQL de la columna en minúsculas (y sin acentos en PostgreSQL) para comparar con like.
     *
     * @template TModelo of Model
     *
     * @param  Builder<TModelo>  $consulta
     * @param  literal-string  $columna
     * @return literal-string
     */
    protected function columnaNormalizada(Builder $consulta, string $columna): string
    {
        return $this->esPostgres($consulta)
            ? 'translate(lower('.$columna."::text), '".self::CON_ACENTOS."', '".self::SIN_ACENTOS."')"
            : 'lower('.$columna.')';
    }

    /**
     * Texto buscado en el mismo formato que columnaNormalizada().
     *
     * @template TModelo of Model
     *
     * @param  Builder<TModelo>  $consulta
     */
    protected function textoNormalizado(Builder $consulta, string $texto): string
    {
        $texto = mb_strtolower($texto);

        return $this->esPostgres($consulta)
            ? strtr($texto, array_combine(mb_str_split(self::CON_ACENTOS), str_split(self::SIN_ACENTOS)))
            : $texto;
    }

    /**
     * @template TModelo of Model
     *
     * @param  Builder<TModelo>  $consulta
     */
    private function esPostgres(Builder $consulta): bool
    {
        return $consulta->getModel()->getConnection()->getDriverName() === 'pgsql';
    }
}

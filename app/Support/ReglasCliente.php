<?php

namespace App\Support;

use Illuminate\Contracts\Validation\ParameterizedRule;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\ImageFile;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\NotIn;
use Illuminate\Validation\Rules\NotRegex;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Regex;
use Illuminate\Validation\ValidationRuleParser;
use ReflectionProperty;
use Stringable;

/**
 * Traduce las reglas de validación de Laravel de cada campo a un objeto sencillo
 * ("ReglasCliente") para que la SPA valide en el navegador lo mismo que valida el servidor:
 *
 *   { requerido: bool, max?, min?, entre?: [min, max], email?, url?, numerico?, entero?,
 *     fecha?, hora?, imagen?, mimes?: string[], confirmado?, enLista?: string[] }
 *
 * max/min son caracteres en textos, valores en números, elementos en listas y KB en archivos
 * (igual que en Laravel). Las reglas que solo pueden comprobarse en el servidor
 * (unique, exists, closures, reglas personalizadas...) se omiten.
 */
final class ReglasCliente
{
    /**
     * Reglas de cliente de un campo.
     *
     * @param  mixed  $reglas  "required|max:200", ['required', Rule::in([...])], Password::min(8)...
     * @return array<string, mixed>
     */
    public static function desde(mixed $reglas): array
    {
        $cliente = ['requerido' => false];

        foreach (self::normalizar($reglas) as $regla) {
            if ($regla instanceof Password) {
                self::contrasena($cliente, $regla);
            } elseif ($regla instanceof File) {
                self::archivo($cliente, $regla);
            } else {
                self::aplicar($cliente, $regla[0], $regla[1]);
            }
        }

        return $cliente;
    }

    /**
     * Reglas de cliente de un conjunto de reglas: ['titulo' => [...], 'fotos.*' => [...]].
     *
     * @param  array<string, mixed>  $reglas
     * @return array<string, array<string, mixed>>
     */
    public static function todas(array $reglas): array
    {
        return array_map(fn ($regla) => self::desde($regla), $reglas);
    }

    /**
     * Campos de formulario listos para la SPA: agrega "reglas" (derivadas de las reglas del
     * servidor), marca "requerido" si la regla lo exige y quita las claves internas.
     *
     * @param  array<int, array<string, mixed>>  $campos
     * @param  array<string, mixed>  $reglas
     * @return array<int, array<string, mixed>>
     */
    public static function campos(array $campos, array $reglas): array
    {
        return array_values(array_map(function (array $campo) use ($reglas) {
            $cliente = self::desde($reglas[$campo['nombre']] ?? []);

            unset($campo['defecto'], $campo['virtual']);
            $campo['requerido'] = ! empty($campo['requerido']) || $cliente['requerido'];
            $campo['reglas'] = $cliente;

            return $campo;
        }, $campos));
    }

    /**
     * Nombres de atributo para los mensajes de validación: la etiqueta de cada campo,
     * sin la aclaración final entre paréntesis ("Imagen (1600x600 recomendado)" -> "Imagen"),
     * para obtener mensajes como "El campo Título es obligatorio.".
     *
     * @param  array<int, array<string, mixed>>  $campos
     * @return array<string, string>
     */
    public static function atributos(array $campos): array
    {
        $atributos = [];

        foreach ($campos as $campo) {
            $etiqueta = (string) ($campo['etiqueta'] ?? $campo['nombre']);
            $limpia = trim((string) preg_replace('/\s*\([^()]*\)\s*$/u', '', $etiqueta));
            $limpia = $limpia !== '' ? $limpia : $etiqueta;

            $atributos[$campo['nombre']] = $limpia;
            $atributos[$campo['nombre'].'.*'] = $limpia;
        }

        return $atributos;
    }

    /**
     * Convierte las reglas a una lista de [NombreRegla, parámetros] (o al objeto Password / File).
     *
     * @return list<Password|File|array{0: string, 1: list<mixed>}>
     */
    private static function normalizar(mixed $reglas): array
    {
        $lista = match (true) {
            $reglas === null => [],
            is_string($reglas) => explode('|', $reglas),
            is_array($reglas) => $reglas,
            default => [$reglas],
        };

        $resultado = [];

        foreach ($lista as $regla) {
            if ($regla instanceof Password || $regla instanceof File) {
                $resultado[] = $regla;
            } elseif ($regla instanceof Email) {
                $resultado[] = ['Email', []];
            } elseif ($regla instanceof ParameterizedRule) {
                // Rule::url(), Rule::confirmed()...
                $resultado[] = self::analizar($regla);
            } elseif ($regla instanceof In || $regla instanceof NotIn || $regla instanceof Enum || $regla instanceof Regex || $regla instanceof NotRegex) {
                // Una sola regla cuyos parámetros pueden contener "|".
                $resultado[] = self::analizar((string) $regla);
            } elseif ($regla instanceof Stringable) {
                // Rule::date(), Rule::numeric(), Rule::requiredIf(), Rule::unique()...: "regla|regla:parámetros".
                foreach (explode('|', (string) $regla) as $parte) {
                    $resultado[] = self::analizar($parte);
                }
            } elseif (is_string($regla) && $regla !== '') {
                $resultado[] = self::analizar($regla);
            }
            // Closures y reglas personalizadas: solo se validan en el servidor.
        }

        return $resultado;
    }

    /**
     * [NombreRegla, parámetros] de una regla en texto ("max:200" -> ["Max", ["200"]]) o parametrizada.
     *
     * @return array{0: string, 1: list<mixed>}
     */
    private static function analizar(string|ParameterizedRule $regla): array
    {
        [$nombre, $parametros] = ValidationRuleParser::parse($regla);

        return [is_string($nombre) ? $nombre : '', array_values((array) $parametros)];
    }

    /**
     * @param  array<string, mixed>  $cliente
     * @param  array<int, mixed>  $parametros
     */
    private static function aplicar(array &$cliente, string $regla, array $parametros): void
    {
        $numero = self::numero($parametros[0] ?? null);

        switch ($regla) {
            case 'Required':
            case 'Accepted':
                $cliente['requerido'] = true;
                break;
            case 'Max':
                if ($numero !== null) {
                    $cliente['max'] = $numero;
                }
                break;
            case 'Min':
                if ($numero !== null) {
                    $cliente['min'] = $numero;
                }
                break;
            case 'Size':
                if ($numero !== null) {
                    $cliente['min'] = $numero;
                    $cliente['max'] = $numero;
                }
                break;
            case 'Between':
                $hasta = self::numero($parametros[1] ?? null);
                if ($numero !== null && $hasta !== null) {
                    $cliente['entre'] = [$numero, $hasta];
                }
                break;
            case 'Email':
                $cliente['email'] = true;
                break;
            case 'Url':
            case 'ActiveUrl':
                $cliente['url'] = true;
                break;
            case 'Numeric':
            case 'Decimal':
                $cliente['numerico'] = true;
                break;
            case 'Integer':
                $cliente['numerico'] = true;
                $cliente['entero'] = true;
                break;
            case 'Date':
            case 'After':
            case 'AfterOrEqual':
            case 'Before':
            case 'BeforeOrEqual':
            case 'DateEquals':
                $cliente['fecha'] = true;
                break;
            case 'DateFormat':
                // date_format:H:i,H:i:s -> hora; date_format:Y-m-d -> fecha.
                $soloHora = $parametros !== [] && array_filter($parametros, fn ($f) => preg_match('/[dDjlNSwzWFmMntLoXxYy]/', (string) $f) === 1) === [];
                $cliente[$soloHora ? 'hora' : 'fecha'] = true;
                break;
            case 'Image':
                $cliente['imagen'] = true;
                break;
            case 'Mimes':
            case 'Extensions':
                self::agregarMimes($cliente, $parametros);
                break;
            case 'Confirmed':
                $cliente['confirmado'] = true;
                break;
            case 'In':
                $cliente['enLista'] = array_map(fn ($valor) => (string) $valor, array_values($parametros));
                break;
        }
    }

    /** @param  array<string, mixed>  $cliente */
    private static function contrasena(array &$cliente, Password $regla): void
    {
        $aplicadas = $regla->appliedRules();

        if ($aplicadas['min']) {
            $cliente['min'] = (int) $aplicadas['min'];
        }

        if ($aplicadas['max']) {
            $cliente['max'] = (int) $aplicadas['max'];
        }
    }

    /**
     * Rule::file() / Rule::imageFile(): tamaños en KB y tipos permitidos.
     *
     * @param  array<string, mixed>  $cliente
     */
    private static function archivo(array &$cliente, File $regla): void
    {
        if ($regla instanceof ImageFile) {
            $cliente['imagen'] = true;
        }

        $minimo = self::propiedad($regla, 'minimumFileSize');
        $maximo = self::propiedad($regla, 'maximumFileSize');

        if (is_numeric($minimo)) {
            $cliente['min'] = (int) $minimo;
        }

        if (is_numeric($maximo)) {
            $cliente['max'] = (int) $maximo;
        }

        $tipos = [...(array) self::propiedad($regla, 'allowedMimetypes'), ...(array) self::propiedad($regla, 'allowedExtensions')];
        self::agregarMimes($cliente, array_filter($tipos, fn ($tipo) => is_string($tipo) && ! str_contains($tipo, '/')));
    }

    /**
     * @param  array<string, mixed>  $cliente
     * @param  array<int, mixed>  $extensiones
     */
    private static function agregarMimes(array &$cliente, array $extensiones): void
    {
        $extensiones = array_map(fn ($e) => strtolower(trim((string) $e)), $extensiones);

        if ($extensiones !== []) {
            $cliente['mimes'] = array_values(array_unique([...($cliente['mimes'] ?? []), ...$extensiones]));
        }
    }

    private static function numero(mixed $valor): int|float|null
    {
        if (! is_numeric($valor)) {
            return null;
        }

        return str_contains((string) $valor, '.') ? (float) $valor : (int) $valor;
    }

    private static function propiedad(object $objeto, string $nombre): mixed
    {
        return property_exists($objeto, $nombre) ? (new ReflectionProperty($objeto, $nombre))->getValue($objeto) : null;
    }
}

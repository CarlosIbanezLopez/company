<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Archivo;
use App\Support\ReglasCliente;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Controlador base de la API del panel de administración: listar, formulario, registrar,
 * ver, actualizar y eliminar. Cada módulo define sus campos, columnas, reglas de validación
 * y filtros; los componentes genéricos de la SPA (listado y formulario) los dibujan a partir
 * del JSON. Rutas (routes/api/admin.php), protegidas por la matriz de acceso:
 *
 *   GET    /api/admin/{uri}             index    ({recurso}.ver)
 *   GET    /api/admin/{uri}/formulario  create   ({recurso}.crear)
 *   POST   /api/admin/{uri}             store    ({recurso}.crear)
 *   GET    /api/admin/{uri}/{id}        show     ({recurso}.ver)
 *   PUT    /api/admin/{uri}/{id}        update   ({recurso}.editar)
 *   DELETE /api/admin/{uri}/{id}        destroy  ({recurso}.eliminar)
 *
 * @template TModelo of Model = Model
 */
abstract class CrudController extends Controller
{
    /** Tipos de campo cuyo valor es una lista de ids (se sincronizan en despuesDeGuardar). */
    protected const TIPOS_LISTA = ['multiselect', 'checkboxes', 'matriz'];

    /** Tipos de campo que guardan un archivo en el disco público. */
    protected const TIPOS_ARCHIVO = ['image', 'file'];

    /** @var class-string<TModelo> */
    protected string $modelo;

    protected string $titulo;

    protected string $singular;

    /** Para concordar el género en los textos: "Nueva noticia", "registrada". */
    protected bool $femenino = false;

    /**
     * Recurso de la matriz de acceso: permisos "{recurso}.ver|crear|editar|eliminar" (p. ej. noticias).
     * Si un controlador no lo declara se toma del middleware "permiso:" de la ruta.
     */
    protected string $recurso;

    /**
     * Segmento de la URI en la API y en la SPA (p. ej. categorias-noticia -> /api/admin/categorias-noticia).
     * Si un controlador no lo declara se toma del nombre de la ruta (api.admin.{uri}.{accion}).
     */
    protected string $uri;

    protected ?string $descripcion = null;

    /** @var array<int, string> */
    protected array $buscarEn = [];

    /** @var array<int, string> */
    protected array $con = [];

    protected string $ordenarPor = 'id';

    protected string $direccion = 'desc';

    protected int $porPagina = 15;

    protected bool $puedeCrear = true;

    protected bool $puedeEditar = true;

    protected bool $puedeEliminar = true;

    /** Campo desde el que se genera el slug (si el modelo tiene columna slug). */
    protected ?string $slugDesde = null;

    /** Carpeta dentro de storage/app/public donde se guardan los archivos subidos. */
    protected string $carpeta = 'archivos';

    /** @var array<int, string|null> Archivos reemplazados o quitados: se borran cuando el registro se guardó. */
    private array $archivosReemplazados = [];

    /** @return array<int, array<string, mixed>> */
    abstract protected function campos(): array;

    /** @return array<int, array<string, mixed>> */
    abstract protected function columnas(): array;

    /**
     * @param  TModelo|null  $registro
     * @return array<string, mixed>
     */
    abstract protected function reglas(?Model $registro): array;

    /** @return array<int, array<string, mixed>> */
    protected function filtros(): array
    {
        return [];
    }

    /**
     * Botones adicionales en la cabecera del listado:
     * { etiqueta, url, icono, tipo: enlace (ruta de la SPA) | descarga (ruta de la API relativa a /api/) }.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function accionesExtra(Request $request): array
    {
        return [];
    }

    /**
     * Botones adicionales por fila (la URL usa __ID__ como comodín), mismo formato que accionesExtra().
     *
     * @return array<int, array<string, mixed>>
     */
    protected function accionesFila(): array
    {
        return [];
    }

    /** @return Builder<TModelo> */
    protected function consulta(): Builder
    {
        return $this->modelo::query()->with($this->con);
    }

    /** @param  Builder<TModelo>  $consulta */
    protected function aplicarFiltros(Builder $consulta, Request $request): void
    {
        foreach ($this->filtros() as $filtro) {
            $valor = $request->query($filtro['clave']);

            if (! is_scalar($valor) || $valor === '') {
                continue;
            }

            $valor = (string) $valor;
            $columna = $filtro['columna'] ?? $filtro['clave'];

            if (($filtro['tipo'] ?? 'select') === 'date') {
                // Una fecha inválida haría fallar la consulta en PostgreSQL: se ignora.
                if (self::esFecha($valor)) {
                    $consulta->whereDate($columna, $filtro['operador'] ?? '=', $valor);
                }

                continue;
            }

            // Solo se aceptan los valores ofrecidos por el filtro (evita errores de tipo en PostgreSQL,
            // p. ej. texto comparado con una columna numérica).
            if (isset($filtro['opciones']) && ! in_array($valor, array_map(fn ($o) => (string) $o['value'], $filtro['opciones']), true)) {
                continue;
            }

            // Filtros "1"/"0" sobre columnas booleanas.
            if ($consulta->getModel()->hasCast($columna, ['bool', 'boolean'])) {
                $consulta->where($columna, filter_var($valor, FILTER_VALIDATE_BOOLEAN));
            } else {
                $consulta->where($columna, $valor);
            }
        }
    }

    /**
     * Búsqueda de texto (sin distinguir mayúsculas). En PostgreSQL la gramática de Laravel compara
     * "columna::text ILIKE ?", por lo que también admite columnas numéricas.
     *
     * @param  Builder<TModelo>  $consulta
     */
    protected function aplicarBusqueda(Builder $consulta, string $texto): void
    {
        $consulta->where(function (Builder $q) use ($texto) {
            foreach ($this->buscarEn as $columna) {
                $q->orWhereLike($columna, '%'.$texto.'%');
            }
        });
    }

    /**
     * Datos de cada fila del listado.
     *
     * @param  TModelo  $registro
     * @return array<string, mixed>
     */
    protected function fila(Model $registro): array
    {
        return $registro->toArray();
    }

    /**
     * Valores iniciales del formulario de edición.
     *
     * @param  TModelo  $registro
     * @return array<string, mixed>
     */
    protected function valores(Model $registro): array
    {
        return $registro->toArray();
    }

    /**
     * Ajustes finales a los datos validados antes de guardar.
     *
     * @param  array<string, mixed>  $datos
     * @param  TModelo|null  $registro
     * @return array<string, mixed>
     */
    protected function prepararDatos(array $datos, Request $request, ?Model $registro): array
    {
        return $datos;
    }

    /** @param  TModelo  $registro */
    protected function despuesDeGuardar(Model $registro, Request $request): void
    {
        //
    }

    /**
     * Mensajes de validación personalizados (además de lang/es/validation.php).
     *
     * @return array<string, string>
     */
    protected function mensajes(): array
    {
        return [];
    }

    /**
     * Nombres de atributo de los mensajes de validación: la etiqueta de cada campo.
     *
     * @param  array<int, array<string, mixed>>  $campos
     * @return array<string, string>
     */
    protected function atributos(array $campos): array
    {
        return ReglasCliente::atributos($campos);
    }

    /**
     * GET /api/admin/{uri}?q=&page=&{filtro}= : configuración, columnas, filtros y registros paginados.
     */
    public function index(Request $request): JsonResponse
    {
        $consulta = $this->consulta();
        $texto = trim((string) $request->query('q', ''));

        if ($texto !== '' && $this->buscarEn) {
            $this->aplicarBusqueda($consulta, $texto);
        }

        $this->aplicarFiltros($consulta, $request);

        $direccion = strtolower($this->direccion) === 'asc' ? 'asc' : 'desc';
        $consulta->orderBy($this->ordenarPor, $direccion);

        // Desempate por la clave primaria: paginación estable (PostgreSQL no garantiza el orden de los empates).
        $clave = $consulta->getModel()->getKeyName();
        if ($this->ordenarPor !== $clave) {
            $consulta->orderBy($clave, $direccion);
        }

        $registros = $consulta
            ->paginate($this->porPagina)
            ->withQueryString()
            ->through(fn ($registro) => $this->fila($registro));

        $filtros = $this->filtros();

        return response()->json([
            'config' => $this->config($request),
            'columnas' => array_map(fn (array $columna) => $columna + ['tipo' => 'text'], $this->columnas()),
            'filtros' => array_map(fn (array $filtro) => self::filtroCliente($filtro), $filtros),
            'buscar' => count($this->buscarEn) > 0,
            'consulta' => $this->consultaActual($request, $filtros),
            'registros' => $registros,
        ]);
    }

    /**
     * GET /api/admin/{uri}/formulario : formulario de registro con los valores por defecto.
     * Los valores también pueden precargarse por query string (p. ej. ?revista_edicion_id=3).
     */
    public function create(Request $request): JsonResponse
    {
        abort_unless($this->puedeCrear, 404);

        $campos = $this->campos();
        $valores = [];

        foreach ($campos as $campo) {
            $valor = $campo['defecto'] ?? $request->query($campo['nombre']);

            $valores[$campo['nombre']] = match (true) {
                in_array($campo['tipo'], self::TIPOS_LISTA, true) => array_values((array) ($valor ?? [])),
                $campo['tipo'] === 'checkbox' => filter_var($valor, FILTER_VALIDATE_BOOLEAN),
                default => $valor,
            };
        }

        return response()->json([
            'config' => $this->config($request),
            'campos' => ReglasCliente::campos($campos, $this->reglas(null)),
            'valores' => $valores,
            'registroId' => null,
        ]);
    }

    /**
     * POST /api/admin/{uri} (JSON o multipart/form-data si hay archivos).
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless($this->puedeCrear, 404);

        $this->archivosReemplazados = [];

        $datos = $this->validar($request, null);
        $datos = $this->procesarArchivos($datos, $request, null);
        $datos = $this->quitarVirtuales($datos);
        $datos = $this->generarSlug($datos, null);
        $datos = $this->prepararDatos($datos, $request, null);

        $registro = DB::transaction(function () use ($datos, $request) {
            $registro = $this->modelo::query()->create($datos);
            $this->despuesDeGuardar($registro, $request);

            return $registro;
        });

        $this->borrarArchivosReemplazados();

        return response()->json([
            'message' => Str::ucfirst($this->singular).($this->femenino ? ' registrada' : ' registrado').' correctamente.',
            'registro' => $this->registroGuardado($registro),
        ], 201);
    }

    /**
     * GET /api/admin/{uri}/{id} : formulario de edición con los valores del registro
     * (los campos imagen/archivo traen además "{nombre}_url").
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $registro = $this->consulta()->findOrFail($id);
        $campos = $this->campos();
        $valores = $this->valores($registro);

        foreach ($campos as $campo) {
            if (in_array($campo['tipo'], self::TIPOS_ARCHIVO, true) && ! array_key_exists($campo['nombre'].'_url', $valores)) {
                $valores[$campo['nombre'].'_url'] = Archivo::url($registro->getAttribute($campo['nombre']));
            }
        }

        return response()->json([
            'config' => $this->config($request),
            'campos' => ReglasCliente::campos($campos, $this->reglas($registro)),
            'valores' => $valores,
            'registroId' => $registro->getKey(),
        ]);
    }

    /**
     * PUT /api/admin/{uri}/{id} (JSON; con archivos: POST multipart con _method=PUT).
     * Para quitar un archivo se envía "{nombre}_quitar=1".
     */
    public function update(Request $request, int $id): JsonResponse
    {
        abort_unless($this->puedeEditar, 404);

        $registro = $this->modelo::query()->findOrFail($id);
        $this->archivosReemplazados = [];

        $datos = $this->validar($request, $registro);
        $datos = $this->procesarArchivos($datos, $request, $registro);
        $datos = $this->quitarVirtuales($datos);
        $datos = $this->generarSlug($datos, $registro);
        $datos = $this->prepararDatos($datos, $request, $registro);

        DB::transaction(function () use ($registro, $datos, $request) {
            $registro->update($datos);
            $this->despuesDeGuardar($registro, $request);
        });

        $this->borrarArchivosReemplazados();

        return response()->json([
            'message' => Str::ucfirst($this->singular).($this->femenino ? ' actualizada' : ' actualizado').' correctamente.',
            'registro' => $this->registroGuardado($registro),
        ]);
    }

    /**
     * DELETE /api/admin/{uri}/{id} : 409 si otros registros dependen de él (clave foránea).
     */
    public function destroy(int $id): JsonResponse
    {
        abort_unless($this->puedeEliminar, 404);

        $registro = $this->modelo::query()->findOrFail($id);

        try {
            // Punto de guardado: en PostgreSQL un error dentro de una transacción la invalida.
            DB::transaction(fn () => $registro->delete());
        } catch (QueryException $e) {
            if (! self::esRestriccion($e)) {
                throw $e;
            }

            return response()->json(['message' => 'No se puede eliminar: tiene información relacionada.'], 409);
        }

        return response()->json([
            'message' => Str::ucfirst($this->singular).($this->femenino ? ' eliminada.' : ' eliminado.'),
        ]);
    }

    /**
     * Configuración que reciben los componentes genéricos de la SPA.
     *
     * @return array<string, mixed>
     */
    protected function config(Request $request): array
    {
        $usuario = $request->user();
        $recurso = $this->recurso();
        $puede = fn (string $accion) => (bool) $usuario?->tienePermiso($recurso.'.'.$accion);

        return [
            'uri' => $this->uri(),
            'recurso' => $recurso,
            'titulo' => $this->titulo,
            'singular' => $this->singular,
            'femenino' => $this->femenino,
            'descripcion' => $this->descripcion,
            'permisos' => [
                'crear' => $this->puedeCrear && $puede('crear'),
                'editar' => $this->puedeEditar && $puede('editar'),
                'eliminar' => $this->puedeEliminar && $puede('eliminar'),
            ],
            'acciones' => array_map(fn (array $accion) => self::accionCliente($accion), $this->accionesExtra($request)),
            'accionesFila' => array_map(fn (array $accion) => self::accionCliente($accion), $this->accionesFila()),
        ];
    }

    protected function recurso(): string
    {
        if (! isset($this->recurso)) {
            $permiso = collect(request()->route()?->gatherMiddleware() ?? [])
                ->first(fn ($middleware) => is_string($middleware) && str_starts_with($middleware, 'permiso:'));

            // "permiso:noticias.ver" (o "permiso:noticias.ver,eventos.ver") -> "noticias"
            $this->recurso = $permiso ? Str::beforeLast(Str::before(Str::after($permiso, 'permiso:'), ','), '.') : '';
        }

        return $this->recurso;
    }

    protected function uri(): string
    {
        if (! isset($this->uri)) {
            $nombre = (string) request()->route()?->getName();

            $this->uri = str_starts_with($nombre, 'api.admin.') ? Str::beforeLast(Str::after($nombre, 'api.admin.'), '.') : '';
        }

        return $this->uri;
    }

    /**
     * Valida la solicitud con los nombres de atributo en español (la etiqueta de cada campo).
     *
     * @param  TModelo|null  $registro
     * @return array<string, mixed>
     */
    protected function validar(Request $request, ?Model $registro): array
    {
        $campos = $this->campos();
        $this->normalizarEntrada($request, $campos);

        return $request->validate($this->reglas($registro), $this->mensajes(), $this->atributos($campos));
    }

    /**
     * Ajusta la entrada antes de validar:
     * - listas (multiselect/checkboxes/matriz) nulas, vacías o ausentes -> [] (multipart no puede enviar una lista vacía);
     * - casillas de verificación "true"/"false"/"on"/"off" -> booleano ("1"/"0" ya son válidos);
     * - archivos: solo cuentan los archivos subidos (se ignoran rutas o URL reenviadas por el cliente).
     *
     * @param  array<int, array<string, mixed>>  $campos
     */
    protected function normalizarEntrada(Request $request, array $campos): void
    {
        $cambios = [];

        foreach ($campos as $campo) {
            $nombre = $campo['nombre'];

            if (in_array($campo['tipo'], self::TIPOS_LISTA, true)) {
                $valor = $request->input($nombre);

                if ($valor === null || $valor === '') {
                    $cambios[$nombre] = [];
                }
            } elseif ($campo['tipo'] === 'checkbox' && $request->has($nombre)) {
                $valor = $request->input($nombre);
                $booleano = is_bool($valor) ? $valor : filter_var($valor ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($booleano !== null && $booleano !== $valor) {
                    $cambios[$nombre] = $booleano;
                }
            } elseif (in_array($campo['tipo'], self::TIPOS_ARCHIVO, true) && ! $request->hasFile($nombre) && $request->has($nombre)) {
                $request->offsetUnset($nombre);
            }
        }

        if ($cambios) {
            $request->merge($cambios);
        }
    }

    /**
     * Guarda los archivos nuevos, marca para borrar los reemplazados y conserva
     * el valor anterior cuando no se sube nada.
     *
     * @param  array<string, mixed>  $datos
     * @param  TModelo|null  $registro
     * @return array<string, mixed>
     */
    protected function procesarArchivos(array $datos, Request $request, ?Model $registro): array
    {
        foreach ($this->campos() as $campo) {
            if (! in_array($campo['tipo'], self::TIPOS_ARCHIVO, true)) {
                continue;
            }

            $nombre = $campo['nombre'];
            $anterior = $registro?->getAttribute($nombre);

            if ($request->hasFile($nombre)) {
                $datos[$nombre] = $request->file($nombre)->store($this->carpeta, 'public');
                $this->archivosReemplazados[] = $anterior;
            } elseif ($request->boolean($nombre.'_quitar')) {
                $datos[$nombre] = null;
                $this->archivosReemplazados[] = $anterior;
            } else {
                unset($datos[$nombre]);
            }
        }

        return $datos;
    }

    protected function borrarArchivo(?string $ruta): void
    {
        if ($ruta && ! str_starts_with($ruta, 'http')) {
            Storage::disk('public')->delete($ruta);
        }
    }

    private function borrarArchivosReemplazados(): void
    {
        foreach ($this->archivosReemplazados as $ruta) {
            $this->borrarArchivo($ruta);
        }

        $this->archivosReemplazados = [];
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    protected function quitarVirtuales(array $datos): array
    {
        foreach ($this->campos() as $campo) {
            if (! empty($campo['virtual']) || $campo['tipo'] === 'readonly') {
                unset($datos[$campo['nombre']]);
            }
        }

        return $datos;
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  TModelo|null  $registro
     * @return array<string, mixed>
     */
    protected function generarSlug(array $datos, ?Model $registro): array
    {
        if (! $this->slugDesde || empty($datos[$this->slugDesde])) {
            return $datos;
        }

        $cambio = ! $registro || $registro->getAttribute($this->slugDesde) !== $datos[$this->slugDesde];

        if (! empty($datos['slug']) && (! $registro || $datos['slug'] !== $registro->getAttribute('slug'))) {
            $base = Str::slug($datos['slug']);
        } elseif ($cambio || ! $registro->getAttribute('slug')) {
            $base = Str::slug($datos[$this->slugDesde]);
        } else {
            unset($datos['slug']);

            return $datos;
        }

        $base = $base !== '' ? Str::limit($base, 200, '') : 'registro';
        $slug = $base;
        $i = 2;

        while ($this->modelo::query()->where('slug', $slug)->when($registro, fn ($q) => $q->whereKeyNot($registro->getKey()))->exists()) {
            $slug = $base.'-'.$i++;
        }

        $datos['slug'] = $slug;

        return $datos;
    }

    /**
     * Registro guardado con el mismo formato que una fila del listado.
     *
     * @param  TModelo  $registro
     * @return array<string, mixed>
     */
    protected function registroGuardado(Model $registro): array
    {
        return $this->fila($this->consulta()->whereKey($registro->getKey())->first() ?? $registro);
    }

    /**
     * Opciones para un select a partir de un modelo.
     *
     * @param  class-string<Model>  $modelo
     * @return array<int, array{value: mixed, label: string}>
     */
    protected function opciones(string $modelo, string $etiqueta = 'nombre', string $orden = 'nombre'): array
    {
        return $modelo::query()->orderBy($orden)->get()
            ->map(fn (Model $m) => ['value' => $m->getKey(), 'label' => (string) $m->getAttribute($etiqueta)])
            ->all();
    }

    /**
     * Convierte un arreglo clave => etiqueta en opciones para select.
     *
     * @param  array<int|string, string>  $valores
     * @return array<int, array{value: int|string, label: string}>
     */
    protected function lista(array $valores): array
    {
        return collect($valores)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all();
    }

    /**
     * Valores actuales de la búsqueda y de cada filtro (cadena vacía si no se usan).
     *
     * @param  array<int, array<string, mixed>>  $filtros
     * @return array<string, string>
     */
    private function consultaActual(Request $request, array $filtros): array
    {
        $consulta = ['q' => trim((string) $request->query('q', ''))];

        foreach ($filtros as $filtro) {
            $valor = $request->query($filtro['clave']);
            $consulta[$filtro['clave']] = is_scalar($valor) ? (string) $valor : '';
        }

        return $consulta;
    }

    /**
     * @param  array<string, mixed>  $filtro
     * @return array{clave: string, etiqueta: string, tipo: string, opciones: array<int, array<string, mixed>>}
     */
    private static function filtroCliente(array $filtro): array
    {
        $tipo = $filtro['tipo'] ?? 'select';

        return [
            'clave' => $filtro['clave'],
            'etiqueta' => $filtro['etiqueta'],
            'tipo' => $tipo,
            // Los valores viajan como texto, igual que en la query string (?estado=publicado&destacado=1).
            'opciones' => $tipo === 'select'
                ? array_values(array_map(fn (array $o) => ['value' => (string) $o['value'], 'label' => $o['label']], $filtro['opciones'] ?? []))
                : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $accion
     * @return array{etiqueta: string, url: string, icono: string|null, tipo: string}
     */
    private static function accionCliente(array $accion): array
    {
        return [
            'etiqueta' => $accion['etiqueta'],
            'url' => $accion['url'],
            'icono' => $accion['icono'] ?? null,
            'tipo' => $accion['tipo'] ?? 'enlace',
        ];
    }

    /** Violación de una restricción de integridad (SQLSTATE 23xxx: clave foránea, etc.). */
    private static function esRestriccion(QueryException $e): bool
    {
        return str_starts_with((string) ($e->errorInfo[0] ?? $e->getCode()), '23');
    }

    private static function esFecha(string $valor): bool
    {
        $fecha = DateTime::createFromFormat('!Y-m-d', $valor);

        return $fecha !== false && $fecha->format('Y-m-d') === $valor;
    }
}

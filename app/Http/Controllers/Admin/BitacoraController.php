<?php

namespace App\Http\Controllers\Admin;

use App\Concerns\ConsultasAnaliticas;
use App\Models\Acceso;
use App\Models\Bitacora;
use App\Models\ContadorRecurso;
use App\Models\ContadorVisita;
use App\Models\InicioSesion;
use App\Models\User;
use App\Support\Catalogos;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CU2 - Bitácora de auditoría (RF-19):
 *  - index:    acciones de los usuarios del panel (listado CRUD de solo lectura con filtros).
 *  - sesiones: intentos de inicio de sesión exitosos y fallidos (tabla inicios_sesion).
 *  - trafico:  solicitudes a la API por IP, usuario y recurso (tablas accesos y contador_recursos).
 *
 * @extends CrudController<Bitacora>
 */
class BitacoraController extends CrudController
{
    use ConsultasAnaliticas;

    public const METODOS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    protected string $modelo = Bitacora::class;

    protected string $titulo = 'Bitácora de auditoría';

    protected string $singular = 'registro';

    protected string $recurso = 'bitacora';

    protected string $uri = 'bitacora';

    protected ?string $descripcion = 'Historial de operaciones realizadas por usuarios con permisos administrativos.';

    protected array $buscarEn = ['descripcion', 'modulo'];

    protected array $con = ['usuario:id,name'];

    protected bool $puedeCrear = false;

    protected bool $puedeEditar = false;

    protected bool $puedeEliminar = false;

    protected function campos(): array
    {
        return [];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'created_at', 'etiqueta' => 'Fecha y hora', 'tipo' => 'datetime'],
            ['clave' => 'usuario.name', 'etiqueta' => 'Usuario'],
            ['clave' => 'accion', 'etiqueta' => 'Acción', 'tipo' => 'badge'],
            ['clave' => 'modulo', 'etiqueta' => 'Módulo'],
            ['clave' => 'descripcion', 'etiqueta' => 'Contenido afectado'],
            ['clave' => 'ip', 'etiqueta' => 'IP'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'user_id', 'etiqueta' => 'Usuario', 'tipo' => 'select', 'opciones' => $this->opciones(User::class, 'name', 'name')],
            ['clave' => 'accion', 'etiqueta' => 'Acción', 'tipo' => 'select', 'opciones' => $this->lista(Catalogos::ACCIONES_BITACORA)],
            ['clave' => 'modulo', 'etiqueta' => 'Módulo', 'tipo' => 'select', 'opciones' => Bitacora::query()->distinct()->orderBy('modulo')->pluck('modulo')->map(fn ($m) => ['value' => $m, 'label' => $m])->all()],
            ['clave' => 'desde', 'etiqueta' => 'Desde', 'tipo' => 'date', 'columna' => 'created_at', 'operador' => '>='],
            ['clave' => 'hasta', 'etiqueta' => 'Hasta', 'tipo' => 'date', 'columna' => 'created_at', 'operador' => '<='],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [];
    }

    /**
     * Inicios de sesión: resumen, IPs con más intentos fallidos y listado paginado.
     */
    public function sesiones(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'exitoso' => ['nullable', Rule::in(['0', '1', 'true', 'false'])],
            'email' => ['nullable', 'string', 'max:150'],
            'ip' => ['nullable', 'string', 'max:45'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $exitoso = filled($filtros['exitoso'] ?? null) ? filter_var($filtros['exitoso'], FILTER_VALIDATE_BOOLEAN) : null;

        // Filtros comunes (el de resultado se aplica solo al listado: el resumen ya separa exitosos y fallidos).
        $base = fn (bool $conFechas = true): Builder => InicioSesion::query()
            ->when($filtros['email'] ?? null, fn (Builder $q, string $email) => $q->whereLike('email', '%'.$email.'%'))
            ->when($filtros['ip'] ?? null, fn (Builder $q, string $ip) => $q->where('ip', $ip))
            ->when($conFechas, fn (Builder $q) => $this->filtrarFechas($q, $filtros));

        $registros = $base()
            ->when($exitoso !== null, fn (Builder $q) => $q->where('exitoso', $exitoso))
            ->with('usuario:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (InicioSesion $fila) => [
                'id' => $fila->id,
                'email' => $fila->email,
                'exitoso' => (bool) $fila->exitoso,
                'motivo' => $fila->motivo,
                'ip' => $fila->ip,
                'agente' => $fila->agente,
                'created_at' => $this->fechaIso($fila->created_at),
                'usuario' => $fila->usuario?->only(['id', 'name']),
            ]);

        return response()->json([
            'resumen' => [
                'exitosas' => $base()->where('exitoso', true)->count(),
                'fallidas' => $base()->where('exitoso', false)->count(),
                'hoy' => $base(false)->where('created_at', '>=', CarbonImmutable::today())->count(),
                'ipsDistintas' => $base()->whereNotNull('ip')->distinct()->count('ip'),
            ],
            'ipsConFallos' => $base()
                ->where('exitoso', false)
                ->whereNotNull('ip')
                ->select('ip')
                ->selectRaw('count(*) as total')
                ->groupBy('ip')
                ->orderByDesc('total')
                ->orderBy('ip')
                ->limit(10)
                ->toBase()
                ->get()
                ->map(fn ($fila) => ['ip' => $fila->ip, 'total' => (int) $fila->total])
                ->values(),
            'registros' => $registros,
        ]);
    }

    /**
     * Tráfico de la API: resumen, recursos y páginas más visitados, IPs, distribución por hora y listado.
     * Sin filtros de fecha ni IP, los recursos y páginas salen de los contadores que mantienen los triggers.
     */
    public function trafico(Request $request): JsonResponse
    {
        if (is_string($request->query('metodo'))) {
            $request->merge(['metodo' => strtoupper($request->query('metodo'))]);
        }

        $filtros = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'ip' => ['nullable', 'string', 'max:45'],
            'metodo' => ['nullable', 'string', Rule::in(self::METODOS)],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $metodo = $filtros['metodo'] ?? null;
        $conFechas = filled($filtros['desde'] ?? null) || filled($filtros['hasta'] ?? null);
        $acotado = $conFechas || filled($filtros['ip'] ?? null);

        $base = fn (bool $fechas = true): Builder => Acceso::query()
            ->when($filtros['ip'] ?? null, fn (Builder $q, string $ip) => $q->where('ip', $ip))
            ->when($metodo, fn (Builder $q, string $m) => $q->where('metodo', $m))
            ->when($fechas, fn (Builder $q) => $this->filtrarFechas($q, $filtros));

        $registros = $base()
            ->with('usuario:id,name')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Acceso $fila) => [
                'id' => $fila->id,
                'metodo' => $fila->metodo,
                'ruta' => $fila->ruta,
                'recurso' => $fila->recurso,
                'estado' => (int) $fila->estado,
                'duracion_ms' => (int) $fila->duracion_ms,
                'ip' => $fila->ip,
                'created_at' => $this->fechaIso($fila->created_at),
                'usuario' => $fila->usuario?->only(['id', 'name']),
            ]);

        return response()->json([
            'resumen' => [
                'solicitudes' => $base()->count(),
                'hoy' => $base(false)->where('created_at', '>=', CarbonImmutable::today())->count(),
                'ipsDistintas' => $base()->whereNotNull('ip')->distinct()->count('ip'),
                'usuariosDistintos' => $base()->whereNotNull('user_id')->distinct()->count('user_id'),
            ],
            'recursos' => $acotado ? $this->recursosDeAccesos($base()) : $this->recursosDelContador($metodo),
            'ips' => $base()
                ->whereNotNull('ip')
                ->select('ip')
                ->selectRaw('count(*) as total, max(created_at) as ultimo')
                ->groupBy('ip')
                ->orderByDesc('total')
                ->orderBy('ip')
                ->limit(15)
                ->toBase()
                ->get()
                ->map(fn ($fila) => ['ip' => $fila->ip, 'total' => (int) $fila->total, 'ultimo' => $this->fechaIso($fila->ultimo)])
                ->values(),
            'paginas' => $this->paginas($filtros, $acotado),
            'porHora' => $this->porHora($base(false), $filtros, $conFechas),
            'registros' => $registros,
        ]);
    }

    /**
     * Desde/hasta inclusivos sobre created_at (sin funciones sobre la columna, para usar el índice).
     *
     * @template TModelo of Model
     *
     * @param  Builder<TModelo>  $consulta
     * @param  array<string, mixed>  $filtros
     */
    private function filtrarFechas(Builder $consulta, array $filtros): void
    {
        if (filled($filtros['desde'] ?? null)) {
            $consulta->where('created_at', '>=', CarbonImmutable::parse($filtros['desde'])->startOfDay());
        }

        if (filled($filtros['hasta'] ?? null)) {
            $consulta->where('created_at', '<', CarbonImmutable::parse($filtros['hasta'])->addDay()->startOfDay());
        }
    }

    /** @return array<int, array{recurso: string, metodo: string, ruta: string, total: int, ultimo_acceso: ?string}> */
    private function recursosDelContador(?string $metodo): array
    {
        return ContadorRecurso::query()
            ->when($metodo, fn (Builder $q, string $m) => $q->where('metodo', $m))
            ->orderByDesc('total')
            ->orderBy('recurso')
            ->limit(15)
            ->get()
            ->map(fn (ContadorRecurso $fila) => [
                'recurso' => $fila->recurso,
                'metodo' => $fila->metodo,
                'ruta' => $fila->ruta,
                'total' => (int) $fila->total,
                'ultimo_acceso' => $this->fechaIso($fila->ultimo_acceso),
            ])
            ->all();
    }

    /**
     * @param  Builder<Acceso>  $consulta
     * @return array<int, array{recurso: string, metodo: string, ruta: string, total: int, ultimo_acceso: ?string}>
     */
    private function recursosDeAccesos(Builder $consulta): array
    {
        return $consulta
            ->select('recurso', 'metodo', 'ruta')
            ->selectRaw('count(*) as total, max(created_at) as ultimo_acceso')
            ->groupBy('recurso', 'metodo', 'ruta')
            ->orderByDesc('total')
            ->orderBy('recurso')
            ->limit(15)
            ->toBase()
            ->get()
            ->map(fn ($fila) => [
                'recurso' => $fila->recurso,
                'metodo' => $fila->metodo,
                'ruta' => $fila->ruta,
                'total' => (int) $fila->total,
                'ultimo_acceso' => $this->fechaIso($fila->ultimo_acceso),
            ])
            ->all();
    }

    /**
     * Páginas de la SPA más visitadas: del contador histórico o, con filtros, de las visitas del período/IP.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<int, array{ruta: string, total: int}>
     */
    private function paginas(array $filtros, bool $acotado): array
    {
        if (! $acotado) {
            return ContadorVisita::query()->orderByDesc('total')->orderBy('ruta')->limit(15)->get(['ruta', 'total'])
                ->map(fn (ContadorVisita $fila) => ['ruta' => $fila->ruta, 'total' => (int) $fila->total])
                ->all();
        }

        $consulta = DB::table('visitas')->when($filtros['ip'] ?? null, fn ($q, string $ip) => $q->where('ip', $ip));

        if (filled($filtros['desde'] ?? null)) {
            $consulta->where('created_at', '>=', CarbonImmutable::parse($filtros['desde'])->startOfDay());
        }

        if (filled($filtros['hasta'] ?? null)) {
            $consulta->where('created_at', '<', CarbonImmutable::parse($filtros['hasta'])->addDay()->startOfDay());
        }

        return $consulta
            ->select('ruta')
            ->selectRaw('count(*) as total')
            ->groupBy('ruta')
            ->orderByDesc('total')
            ->orderBy('ruta')
            ->limit(15)
            ->get()
            ->map(fn ($fila) => ['ruta' => $fila->ruta, 'total' => (int) $fila->total])
            ->all();
    }

    /**
     * Solicitudes por hora del día (0..23) de los últimos 7 días o del período filtrado.
     *
     * @param  Builder<Acceso>  $consulta
     * @param  array<string, mixed>  $filtros
     * @return array<int, array{hora: int, total: int}>
     */
    private function porHora(Builder $consulta, array $filtros, bool $conFechas): array
    {
        if ($conFechas) {
            $this->filtrarFechas($consulta, $filtros);
        } else {
            $consulta->where('created_at', '>=', CarbonImmutable::today()->subDays(6));
        }

        $hora = $this->sqlHora('created_at');

        $totales = $consulta
            ->selectRaw("{$hora} as hora, count(*) as total")
            ->groupByRaw($hora)
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($fila) => [(int) $fila->hora => (int) $fila->total]);

        return array_map(fn (int $h) => ['hora' => $h, 'total' => $totales[$h] ?? 0], range(0, 23));
    }
}

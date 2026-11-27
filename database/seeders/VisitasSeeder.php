<?php

namespace Database\Seeders;

use App\Models\Acceso;
use App\Models\Bitacora;
use App\Models\Evento;
use App\Models\InicioSesion;
use App\Models\Noticia;
use App\Models\RevistaEdicion;
use App\Models\User;
use App\Models\Visita;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * CU8 - Tráfico de ejemplo para que el tablero, la bitácora y los reportes muestren datos:
 *  - visitas a las páginas públicas de la SPA (últimos 45 días),
 *  - intentos de inicio de sesión exitosos, fallidos y bloqueados (últimos 14 días),
 *  - solicitudes a la API derivadas de esas visitas y sesiones (últimos 14 días).
 *
 * Solo se insertan filas en las tablas de registro (visitas, inicios_sesion, accesos y bitacora):
 * contador_visitas, visitas_diarias, contador_recursos, users.ultimo_acceso y
 * users.intentos_fallidos los calculan los triggers de la base de datos.
 */
class VisitasSeeder extends Seeder
{
    private const DIAS_VISITAS = 45;

    private const DIAS_ACCESOS = 14;

    private const BLOQUEO = 'Bloqueo temporal por demasiados intentos';

    /** Página de la SPA => [visitas promedio por día, URI de la API que consume, nombre de la ruta]. */
    private const PAGINAS = [
        '/' => [30, 'api/inicio', 'api.sitio.inicio'],
        '/malla-curricular' => [12, 'api/malla-curricular', 'api.sitio.malla'],
        '/noticias' => [10, 'api/noticias', 'api.sitio.noticias'],
        '/biblioteca' => [8, 'api/biblioteca', 'api.sitio.documentos'],
        '/plantel-docente' => [6, 'api/plantel-docente', 'api.sitio.plantel'],
        '/academico/admision' => [5, 'api/academico/{slug}', 'api.sitio.academico'],
        '/eventos' => [5, 'api/eventos', 'api.sitio.eventos'],
        '/revista-innovar' => [4, 'api/revista-innovar', 'api.sitio.revista'],
        '/contacto' => [4, 'api/contacto', 'api.sitio.contacto'],
        '/galeria' => [4, 'api/galeria', 'api.sitio.galeria'],
        '/innova' => [3, 'api/innova', 'api.sitio.innova'],
        '/graduados' => [3, 'api/graduados', 'api.sitio.graduados'],
        '/institucional/mision' => [3, 'api/institucional/{slug}', 'api.sitio.institucional'],
        '/institucional/historia' => [2, 'api/institucional/{slug}', 'api.sitio.institucional'],
        '/calendario-academico' => [2, 'api/calendario-academico', 'api.sitio.calendario'],
        '/preguntas-frecuentes' => [2, 'api/preguntas-frecuentes', 'api.sitio.preguntas'],
    ];

    /** Cuenta de prueba => [probabilidad de ingresar en un día hábil (%), IP habitual, solicitudes frecuentes]. */
    private const SESIONES = [
        'admin@carrera.test' => [80, '200.87.193.10', [
            ['GET', 'api/admin/usuarios', 'api.admin.usuarios.index'],
            ['GET', 'api/admin/roles', 'api.admin.roles.index'],
            ['GET', 'api/admin/roles/matriz', 'api.admin.roles.matriz'],
            ['GET', 'api/admin/menu', 'api.admin.menu.index'],
            ['GET', 'api/admin/bitacora', 'api.admin.bitacora.index'],
            ['GET', 'api/admin/bitacora/sesiones', 'api.admin.bitacora.sesiones'],
            ['GET', 'api/admin/bitacora/trafico', 'api.admin.bitacora.trafico'],
            ['GET', 'api/admin/reportes', 'api.admin.reportes.index'],
            ['GET', 'api/admin/configuracion/{grupo}', 'api.admin.configuracion.show'],
        ]],
        'noticias@carrera.test' => [70, '200.87.193.14', [
            ['GET', 'api/admin/noticias', 'api.admin.noticias.index'],
            ['GET', 'api/admin/noticias/{id}', 'api.admin.noticias.show'],
            ['GET', 'api/admin/noticias/formulario', 'api.admin.noticias.create'],
            ['GET', 'api/admin/categorias-noticia', 'api.admin.categorias-noticia.index'],
            ['GET', 'api/admin/eventos', 'api.admin.eventos.index'],
            ['GET', 'api/admin/albumes', 'api.admin.albumes.index'],
            ['GET', 'api/admin/albumes/{id}/fotos', 'api.admin.albumes.fotos'],
            ['GET', 'api/admin/videos', 'api.admin.videos.index'],
            ['GET', 'api/admin/reportes', 'api.admin.reportes.index'],
        ]],
        'revista@carrera.test' => [45, '200.87.193.21', [
            ['GET', 'api/admin/ediciones', 'api.admin.ediciones.index'],
            ['GET', 'api/admin/ediciones/{id}', 'api.admin.ediciones.show'],
            ['GET', 'api/admin/articulos', 'api.admin.articulos.index'],
            ['GET', 'api/admin/lineas', 'api.admin.lineas.index'],
            ['GET', 'api/admin/proyectos', 'api.admin.proyectos.index'],
            ['GET', 'api/admin/documentos', 'api.admin.documentos.index'],
        ]],
        'graduados@carrera.test' => [50, '200.87.193.30', [
            ['GET', 'api/admin/graduados', 'api.admin.graduados.index'],
            ['GET', 'api/admin/graduados/{id}', 'api.admin.graduados.show'],
            ['GET', 'api/admin/convenios', 'api.admin.convenios.index'],
            ['GET', 'api/admin/innova-solicitudes', 'api.admin.innova-solicitudes.index'],
            ['GET', 'api/admin/innova-postulaciones', 'api.admin.innova-postulaciones.index'],
            ['GET', 'api/admin/innova-proyectos', 'api.admin.innova-proyectos.index'],
            ['GET', 'api/admin/reportes', 'api.admin.reportes.index'],
        ]],
        'secretaria@carrera.test' => [75, '200.87.193.12', [
            ['GET', 'api/admin/consultas', 'api.admin.consultas.index'],
            ['GET', 'api/admin/consultas/{id}', 'api.admin.consultas.show'],
            ['GET', 'api/admin/paginas', 'api.admin.paginas.index'],
            ['GET', 'api/admin/materias', 'api.admin.materias.index'],
            ['GET', 'api/admin/calendario', 'api.admin.calendario.index'],
            ['GET', 'api/admin/docentes', 'api.admin.docentes.index'],
            ['GET', 'api/admin/documentos', 'api.admin.documentos.index'],
        ]],
        'graduado@carrera.test' => [15, '181.188.42.17', [
            ['GET', 'api/graduados/mi-ficha', 'api.sitio.graduados.ficha'],
        ]],
    ];

    /** Intentos automatizados: [hace N días, hora, IP, agente, correo probado, intentos]. Desde el 6.º intento se bloquea. */
    private const ATAQUES = [
        [9, 3, '45.95.147.22', 'python-requests/2.32.3', 'admin@carrera.test', 8],
        [5, 2, '185.220.101.47', 'curl/8.5.0', 'administrador@carrera.test', 6],
        [5, 2, '185.220.101.47', 'curl/8.5.0', 'root@carrera.test', 3],
        [3, 4, '103.145.13.90', 'Mozilla/5.0 (compatible; Nmap Scripting Engine)', 'test@carrera.test', 2],
    ];

    private const AGENTES = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Linux; Android 14; SM-A546E) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 18_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.1 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0',
        'Mozilla/5.0 (Linux; Android 13; moto g54 5G) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36',
    ];

    /** Peso relativo de cada hora del día (0 a 23) para repartir el tráfico. */
    private const HORAS = [1, 1, 1, 1, 1, 1, 2, 4, 6, 8, 9, 9, 7, 6, 7, 8, 8, 7, 7, 8, 8, 6, 4, 2];

    private CarbonImmutable $ahora;

    /** @var array<string, int> */
    private array $usuarios = [];

    /** @var array<int, array{0: string, 1: string}> */
    private array $visitantes = [];

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $filas = ['visitas' => [], 'inicios' => [], 'accesos' => [], 'bitacora' => []];

    public function run(): void
    {
        // Semilla fija: los datos de ejemplo son los mismos en cada instalación (relativos a la fecha actual).
        mt_srand(2026);

        $this->ahora = CarbonImmutable::now()->startOfSecond();
        $this->usuarios = User::query()->pluck('id', 'email')->all();
        $this->visitantes = $this->generarVisitantes();

        $this->visitas();
        $this->sesiones();
        $this->ataques();
        $this->busquedas();

        $this->insertar(Visita::class, $this->filas['visitas']);
        $this->insertar(InicioSesion::class, $this->filas['inicios']);
        $this->insertar(Acceso::class, $this->filas['accesos']);
        $this->insertar(Bitacora::class, $this->filas['bitacora']);

        mt_srand();
    }

    /**
     * Visitas diarias a cada página pública; las de los últimos 14 días también generan
     * las solicitudes a la API que hace la SPA para mostrar la página.
     */
    private function visitas(): void
    {
        $paginas = self::PAGINAS + $this->paginasDeDetalle();

        for ($d = self::DIAS_VISITAS - 1; $d >= 0; $d--) {
            $dia = $this->ahora->subDays($d)->startOfDay();
            // Menos tráfico los fines de semana y un leve crecimiento en las últimas semanas.
            $factor = ($dia->isWeekend() ? 0.55 : 1.0) * (0.8 + 0.2 * (1 - $d / self::DIAS_VISITAS));

            foreach ($paginas as $ruta => $pagina) {
                [$promedio, $api, $recurso] = $pagina;

                if (isset($pagina[3]) && $dia->toDateString() < $pagina[3]) {
                    continue; // Noticia aún no publicada ese día.
                }

                $cantidad = (int) round($promedio * $factor * (0.6 + 0.8 * $this->azar()));

                for ($i = 0; $i < $cantidad; $i++) {
                    if (! $momento = $this->momento($dia)) {
                        continue;
                    }

                    [$ip, $agente] = $this->visitante();
                    $this->visita($momento, $ruta, null, $ip, $agente);

                    if ($d < self::DIAS_ACCESOS) {
                        // Al entrar al sitio, la SPA también pide los datos del encabezado y el pie de página.
                        if ($this->probabilidad(35)) {
                            $this->acceso($momento, 'GET', 'api/sitio', 'api.sitio.datos', 200, null, $ip, 15, 70);
                        }

                        $this->acceso($momento->addSecond(), 'GET', $api, $recurso, 200, null, $ip, 20, 220);
                    }
                }
            }
        }
    }

    /**
     * Noticias, eventos y la última edición de la revista también reciben visitas.
     * Formato: ruta => [promedio diario, URI de la API, nombre de la ruta, publicada desde (opcional)].
     *
     * @return array<string, array{0: int, 1: string, 2: string, 3?: string}>
     */
    private function paginasDeDetalle(): array
    {
        $paginas = [];

        foreach (Noticia::query()->where('estado', 'publicado')->orderByDesc('fecha_publicacion')->limit(4)->get(['slug', 'fecha_publicacion']) as $noticia) {
            $paginas["/noticias/{$noticia->slug}"] = [2, 'api/noticias/{slug}', 'api.sitio.noticias.show', substr((string) $noticia->fecha_publicacion, 0, 10)];
        }

        foreach (Evento::query()->where('publicado', true)->orderByDesc('fecha')->limit(2)->pluck('slug') as $slug) {
            $paginas["/eventos/{$slug}"] = [1, 'api/eventos/{slug}', 'api.sitio.eventos.show'];
        }

        if ($numero = RevistaEdicion::query()->max('numero')) {
            $paginas["/revista-innovar/{$numero}"] = [1, 'api/revista-innovar/{numero}', 'api.sitio.revista.show'];
        }

        return $paginas;
    }

    /**
     * Sesiones de las cuentas de prueba durante los últimos 14 días.
     * Ayer ingresan todas: así el último intento de cada cuenta es exitoso
     * (intentos_fallidos = 0 y ultimo_acceso con fecha reciente).
     */
    private function sesiones(): void
    {
        for ($d = self::DIAS_ACCESOS - 1; $d >= 0; $d--) {
            $dia = $this->ahora->subDays($d)->startOfDay();

            foreach (self::SESIONES as $correo => [$probabilidad, $ip, $solicitudes]) {
                $usuario = $this->usuarios[$correo] ?? null;
                $ingresa = $d === 1 || $this->probabilidad($dia->isWeekend() ? intdiv($probabilidad, 5) : $probabilidad);

                if (! $usuario || ! $ingresa || ! $inicio = $this->momento($dia, 8, 19)) {
                    continue;
                }

                // A veces ingresa desde el celular (otra IP).
                [$ipMovil, $agente] = $this->visitante();
                $this->sesion($correo, $usuario, $inicio, $this->probabilidad(80) ? $ip : $ipMovil, $agente, $solicitudes);
            }
        }
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string}>  $solicitudes
     */
    private function sesion(string $correo, int $usuario, CarbonImmutable $inicio, string $ip, string $agente, array $solicitudes): void
    {
        $panel = $correo !== 'graduado@carrera.test';

        // La SPA valida el token guardado: si expiró, responde 401 y muestra el formulario de ingreso.
        if ($this->probabilidad(30)) {
            $this->acceso($inicio->subMinutes(2), 'GET', 'api/auth/usuario', 'api.auth.usuario', 401, null, $ip, 10, 40);
        }

        // Contraseña mal escrita antes de ingresar.
        for ($i = $this->probabilidad(15) ? mt_rand(1, 2) : 0; $i >= 1; $i--) {
            $this->intento($inicio->subSeconds($i * mt_rand(20, 70)), $correo, $usuario, false, 'Credenciales incorrectas', $ip, $agente);
        }

        $this->intento($inicio, $correo, $usuario, true, null, $ip, $agente);
        $this->auditoria($inicio, $usuario, 'inicio_sesion', "Inició sesión: {$correo}", $ip, $agente);

        $t = $inicio->addSeconds(mt_rand(1, 3));

        if ($panel) {
            $this->acceso($t, 'GET', 'api/menu', 'api.menu', 200, $usuario, $ip, 20, 60);
            $this->acceso($t, 'GET', 'api/admin/dashboard', 'api.admin.dashboard', 200, $usuario, $ip, 60, 350);

            // Intento de abrir un módulo sin permiso: la matriz de acceso responde 403.
            if ($correo !== 'admin@carrera.test' && $this->probabilidad(10)) {
                $this->acceso($t->addSeconds(mt_rand(5, 30)), 'GET', 'api/admin/usuarios', 'api.admin.usuarios.index', 403, $usuario, $ip, 10, 30);
            }
        }

        for ($i = $panel ? mt_rand(2, 7) : 1; $i > 0; $i--) {
            $t = $t->addSeconds(mt_rand(15, 240));
            [$metodo, $ruta, $recurso] = $solicitudes[array_rand($solicitudes)];
            $this->acceso($t, $metodo, $ruta, $recurso, 200, $usuario, $ip, 25, 300);
        }

        if (! $panel) {
            // El graduado revisa su ficha en el sitio público.
            $this->visita($t, '/graduados/mi-ficha', $usuario, $ip, $agente);
        }

        $t = $t->addMinutes(mt_rand(1, 20));

        if ($this->probabilidad(55) && $t->lessThanOrEqualTo($this->ahora)) {
            $this->acceso($t, 'POST', 'api/auth/logout', 'api.auth.logout', 200, $usuario, $ip, 15, 60);
            $this->auditoria($t, $usuario, 'cierre_sesion', "Cerró sesión: {$correo}", $ip, $agente);
        }
    }

    /**
     * Ataques de fuerza bruta: tras 5 intentos fallidos el límite de intentos bloquea el correo + IP.
     */
    private function ataques(): void
    {
        foreach (self::ATAQUES as [$dias, $hora, $ip, $agente, $correo, $intentos]) {
            $t = $this->ahora->subDays($dias)->setTime($hora, mt_rand(0, 59), mt_rand(0, 59));

            for ($i = 1; $i <= $intentos; $i++) {
                $this->intento($t, $correo, $this->usuarios[$correo] ?? null, false, $i > 5 ? self::BLOQUEO : 'Credenciales incorrectas', $ip, $agente);
                $t = $t->addSeconds(mt_rand(4, 9));
            }
        }
    }

    /**
     * Búsqueda predictiva y completa, descargas de la biblioteca y enlaces antiguos (404).
     */
    private function busquedas(): void
    {
        for ($d = self::DIAS_ACCESOS - 1; $d >= 0; $d--) {
            $dia = $this->ahora->subDays($d)->startOfDay();

            for ($i = mt_rand(2, 5); $i > 0; $i--) {
                if (! $t = $this->momento($dia)) {
                    continue;
                }

                [$ip, $agente] = $this->visitante();

                for ($k = mt_rand(2, 4); $k > 0; $k--) {
                    $t = $t->addSeconds(mt_rand(1, 3));
                    $this->acceso($t, 'GET', 'api/buscar/sugerencias', 'api.sitio.buscar.sugerencias', 200, null, $ip, 8, 45);
                }

                if ($this->probabilidad(45) && $t->addSeconds(2)->lessThanOrEqualTo($this->ahora)) {
                    $this->visita($t->addSeconds(2), '/buscar', null, $ip, $agente);
                    $this->acceso($t->addSeconds(2), 'GET', 'api/buscar', 'api.sitio.buscar', 200, null, $ip, 40, 260);
                }
            }

            for ($i = mt_rand(1, 4); $i > 0; $i--) {
                if ($t = $this->momento($dia)) {
                    $this->acceso($t, 'GET', 'api/biblioteca/{id}/descargar', 'api.sitio.documentos.descargar', 200, null, $this->visitante()[0], 10, 90);
                }
            }

            if ($this->probabilidad(50) && $t = $this->momento($dia)) {
                $this->acceso($t, 'GET', 'api/noticias/{slug}', 'api.sitio.noticias.show', 404, null, $this->visitante()[0], 10, 40);
            }
        }
    }

    private function visita(CarbonImmutable $momento, string $ruta, ?int $usuario, string $ip, string $agente): void
    {
        $this->filas['visitas'][] = [
            'ruta' => $ruta,
            'user_id' => $usuario,
            'ip' => $ip,
            'agente' => $agente,
            'created_at' => $momento->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Intento de inicio de sesión y la solicitud POST /api/auth/login que lo produjo.
     */
    private function intento(CarbonImmutable $momento, string $correo, ?int $usuario, bool $exitoso, ?string $motivo, string $ip, string $agente): void
    {
        $this->filas['inicios'][] = [
            'user_id' => $usuario,
            'email' => $correo,
            'exitoso' => $exitoso,
            'motivo' => $motivo,
            'ip' => $ip,
            'agente' => $agente,
            'created_at' => $momento->format('Y-m-d H:i:s'),
        ];

        $estado = $exitoso ? 200 : ($motivo === self::BLOQUEO ? 429 : 422);
        $this->acceso($momento, 'POST', 'api/auth/login', 'api.auth.login', $estado, null, $ip, $estado === 429 ? 3 : 180, $estado === 429 ? 15 : 420);
    }

    private function acceso(CarbonImmutable $momento, string $metodo, string $ruta, string $recurso, int $estado, ?int $usuario, string $ip, int $minimo, int $maximo): void
    {
        if ($momento->greaterThan($this->ahora)) {
            return;
        }

        $this->filas['accesos'][] = [
            'user_id' => $usuario,
            'ip' => $ip,
            'metodo' => $metodo,
            'ruta' => $ruta,
            'recurso' => $recurso,
            'estado' => $estado,
            'duracion_ms' => mt_rand($minimo, $maximo),
            'created_at' => $momento->format('Y-m-d H:i:s'),
        ];
    }

    private function auditoria(CarbonImmutable $momento, int $usuario, string $accion, string $descripcion, string $ip, string $agente): void
    {
        $this->filas['bitacora'][] = [
            'user_id' => $usuario,
            'accion' => $accion,
            'modulo' => 'Autenticación',
            'descripcion' => $descripcion,
            'registro_id' => $usuario,
            'ip' => $ip,
            'agente' => $agente,
            'created_at' => $momento->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Inserta en orden cronológico (los triggers acumulan en ese orden) y por lotes.
     *
     * @param  class-string<Model>  $modelo
     * @param  array<int, array<string, mixed>>  $filas
     */
    private function insertar(string $modelo, array $filas): void
    {
        usort($filas, fn ($a, $b) => strcmp($a['created_at'], $b['created_at']));

        foreach (array_chunk($filas, 500) as $lote) {
            $modelo::query()->insert($lote);
        }
    }

    /**
     * Visitantes frecuentes del sitio: IP de proveedores locales y navegador.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function generarVisitantes(): array
    {
        $redes = ['200.87.', '190.129.', '200.105.', '181.188.', '181.114.', '177.222.', '186.121.', '200.58.'];
        $visitantes = [];

        for ($i = 0; $i < 60; $i++) {
            $visitantes[] = [$redes[$i % count($redes)].mt_rand(1, 254).'.'.mt_rand(1, 254), self::AGENTES[mt_rand(0, count(self::AGENTES) - 1)]];
        }

        return $visitantes;
    }

    /**
     * Visitante al azar; los primeros de la lista vuelven con más frecuencia.
     *
     * @return array{0: string, 1: string}
     */
    private function visitante(): array
    {
        return $this->visitantes[(int) floor($this->azar() ** 2 * count($this->visitantes))];
    }

    /**
     * Momento al azar del día (según el tráfico de cada hora) que no sea posterior a ahora.
     */
    private function momento(CarbonImmutable $dia, int $desde = 0, int $hasta = 23): ?CarbonImmutable
    {
        $pesos = array_slice(self::HORAS, $desde, $hasta - $desde + 1, true);
        $sorteo = $this->azar() * array_sum($pesos);
        $hora = $hasta;

        foreach ($pesos as $h => $peso) {
            if (($sorteo -= $peso) < 0) {
                $hora = $h;
                break;
            }
        }

        $momento = $dia->setTime($hora, mt_rand(0, 59), mt_rand(0, 59));

        return $momento->lessThanOrEqualTo($this->ahora) ? $momento : null;
    }

    private function probabilidad(int $porcentaje): bool
    {
        return mt_rand(1, 100) <= $porcentaje;
    }

    /** Número al azar en [0, 1). */
    private function azar(): float
    {
        return mt_rand() / (mt_getrandmax() + 1);
    }
}

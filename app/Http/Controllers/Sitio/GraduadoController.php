<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\Bitacora;
use App\Models\Graduado;
use App\Models\InicioSesion;
use App\Models\Pagina;
use App\Models\Rol;
use App\Models\User;
use App\Support\Antispam;
use App\Support\Catalogos;
use App\Support\Markdown;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

/**
 * Programa Alumni: información para graduados, registro y actualización de sus datos (RF-11).
 * El registro crea la cuenta (rol Graduado) con su ficha y entrega un token Bearer: queda con la sesión iniciada.
 */
class GraduadoController extends Controller
{
    private const FORMULARIO = 'graduados';

    public function index(Request $request): JsonResponse
    {
        $paginas = Pagina::query()->where('seccion', 'graduados')->where('publicado', true)->orderBy('orden')->orderBy('id')->get()
            ->map(fn (Pagina $p) => ['slug' => $p->slug, 'titulo' => $p->titulo, 'resumen' => $p->resumen, 'html' => Markdown::html($p->contenido)]);

        return response()->json([
            'paginas' => $paginas,
            'situaciones' => Catalogos::SITUACIONES_LABORALES,
            'modalidades' => Catalogos::MODALIDADES_TITULACION,
            'tieneFicha' => (bool) $request->user('sanctum')?->graduado,
            'antispam' => Antispam::generar(self::FORMULARIO, false),
            'total' => Graduado::query()->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Equivale al middleware "guest" de la versión anterior (403 con el mensaje en español).
        if ($request->user('sanctum') !== null) {
            throw new AccessDeniedHttpException('Ya tiene una sesión iniciada. Cierre la sesión para registrar una cuenta nueva.');
        }

        Antispam::validar($request, self::FORMULARIO, false);

        // El correo se guarda en minúsculas: en PostgreSQL la regla unique distingue mayúsculas.
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }

        $datos = $request->validate([
            ...$this->reglas(),
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $usuario = DB::transaction(function () use ($datos) {
            $usuario = User::query()->create([
                'name' => $datos['nombre'],
                'email' => $datos['email'],
                // La columna users.telefono admite 20 caracteres (la ficha guarda el número completo).
                'telefono' => isset($datos['telefono']) ? mb_substr($datos['telefono'], 0, 20) : null,
                'password' => $datos['password'],
                'rol_id' => Rol::query()->where('nombre', 'Graduado')->value('id'),
                'activo' => true,
                'email_verified_at' => now(),
            ]);

            $usuario->graduado()->create([
                ...Arr::only($datos, array_keys($this->reglas())),
                'correo' => $datos['email'],
            ]);

            return $usuario;
        });

        Antispam::consumir($request);

        // El registro deja la sesión iniciada: se emite el token Bearer y se audita como inicio de sesión.
        $token = $usuario->emitirToken(false, 'Registro de graduado');
        $this->registrarInicioSesion($request, $usuario);

        return response()->json([
            'message' => 'Registro completado. Puede actualizar sus datos cuando lo necesite.',
            'token' => $token,
            'usuario' => UsuarioResource::make($usuario->fresh())->resolve($request),
        ], 201);
    }

    public function ficha(Request $request): JsonResponse
    {
        return response()->json([
            'graduado' => $request->user()->graduado,
            'situaciones' => Catalogos::SITUACIONES_LABORALES,
            'modalidades' => Catalogos::MODALIDADES_TITULACION,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $datos = $request->validate([...$this->reglas(), 'correo' => ['required', 'email', 'max:150']]);

        $graduado = $request->user()->graduado()->updateOrCreate(['user_id' => $request->user()->id], $datos);

        return response()->json([
            'message' => 'Sus datos fueron actualizados.',
            'graduado' => $graduado->fresh(),
        ]);
    }

    /**
     * Bitácora de autenticación (tabla inicios_sesion; su trigger actualiza users.ultimo_acceso)
     * y bitácora de auditoría. Un fallo aquí no debe impedir el registro.
     */
    private function registrarInicioSesion(Request $request, User $usuario): void
    {
        try {
            InicioSesion::query()->create([
                'user_id' => $usuario->id,
                'email' => $usuario->email,
                'exitoso' => true,
                'motivo' => 'Registro de graduado',
                'ip' => $request->ip(),
                'agente' => Str::limit((string) $request->userAgent(), 255, ''),
            ]);

            Bitacora::registrar('inicio_sesion', 'Autenticación', 'Se registró como graduado e inició sesión: '.$usuario->email, $usuario->id, $usuario->id);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** @return array<string, array<int, mixed>> */
    private function reglas(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'ci' => ['required', 'string', 'max:20'],
            'registro' => ['nullable', 'string', 'max:20'],
            'anio_graduacion' => ['required', 'integer', 'between:1970,'.(date('Y') + 1)],
            'modalidad_titulacion' => ['nullable', Rule::in(array_keys(Catalogos::MODALIDADES_TITULACION))],
            'situacion_laboral' => ['required', Rule::in(array_keys(Catalogos::SITUACIONES_LABORALES))],
            'empresa' => ['nullable', 'string', 'max:150'],
            'cargo' => ['nullable', 'string', 'max:120'],
            'ciudad' => ['nullable', 'string', 'max:80'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'linkedin' => ['nullable', 'url', 'max:255'],
        ];
    }
}

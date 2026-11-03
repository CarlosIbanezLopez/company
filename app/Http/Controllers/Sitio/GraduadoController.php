<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Graduado;
use App\Models\Pagina;
use App\Models\Rol;
use App\Models\User;
use App\Support\Antispam;
use App\Support\Catalogos;
use App\Support\Markdown;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Programa Alumni: información para graduados, registro y actualización de sus datos (RF-11).
 */
class GraduadoController extends Controller
{
    public function index(Request $request): Response
    {
        $paginas = Pagina::query()->where('seccion', 'graduados')->where('publicado', true)->orderBy('orden')->get()
            ->map(fn (Pagina $p) => ['slug' => $p->slug, 'titulo' => $p->titulo, 'resumen' => $p->resumen, 'html' => Markdown::html($p->contenido)]);

        return Inertia::render('sitio/Graduados', [
            'paginas' => $paginas,
            'situaciones' => Catalogos::SITUACIONES_LABORALES,
            'modalidades' => Catalogos::MODALIDADES_TITULACION,
            'tieneFicha' => (bool) $request->user()?->graduado,
            'antispam' => Antispam::generar('graduados', false),
            'total' => Graduado::query()->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Antispam::validar($request, 'graduados', false);

        $datos = $request->validate([
            ...$this->reglas(),
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $usuario = DB::transaction(function () use ($datos) {
            $usuario = User::query()->create([
                'name' => $datos['nombre'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
                'password' => $datos['password'],
                'rol_id' => Rol::query()->where('nombre', 'Graduado')->value('id'),
                'activo' => true,
                'email_verified_at' => now(),
            ]);

            $usuario->graduado()->create([...collect($datos)->only(array_keys($this->reglas()))->all(), 'correo' => $datos['email']]);

            return $usuario;
        });

        Auth::login($usuario);
        $request->session()->regenerate();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Registro completado. Puede actualizar sus datos cuando lo necesite.']);

        return redirect()->route('graduados.ficha');
    }

    public function ficha(Request $request): Response
    {
        return Inertia::render('sitio/MiFicha', [
            'graduado' => $request->user()->graduado,
            'situaciones' => Catalogos::SITUACIONES_LABORALES,
            'modalidades' => Catalogos::MODALIDADES_TITULACION,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([...$this->reglas(), 'correo' => ['required', 'email', 'max:150']]);

        $request->user()->graduado()->updateOrCreate(['user_id' => $request->user()->id], $datos);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sus datos fueron actualizados.']);

        return back();
    }

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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador base para las pantallas de administración (listar, crear, editar, eliminar).
 * Cada módulo define sus campos, columnas, reglas de validación y filtros;
 * las vistas admin/crud/Index y admin/crud/Form se encargan de dibujarlos.
 */
abstract class CrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $modelo;

    protected string $titulo;

    protected string $singular;

    /** Para concordar el género en los textos: "Nueva noticia", "registrada". */
    protected bool $femenino = false;

    /** Prefijo del nombre de ruta, por ejemplo admin.noticias */
    protected string $ruta;

    protected ?string $descripcion = null;

    /** @var array<int, string> */
    protected array $buscarEn = [];

    /** @var array<int, string> */
    protected array $con = [];

    protected string $ordenarPor = 'id';

    protected string $direccion = 'desc';

    protected bool $puedeCrear = true;

    protected bool $puedeEditar = true;

    protected bool $puedeEliminar = true;

    /** Campo desde el que se genera el slug (si el modelo tiene columna slug). */
    protected ?string $slugDesde = null;

    /** Carpeta dentro de storage/app/public donde se guardan los archivos subidos. */
    protected string $carpeta = 'archivos';

    /** @return array<int, array<string, mixed>> */
    abstract protected function campos(): array;

    /** @return array<int, array<string, mixed>> */
    abstract protected function columnas(): array;

    /** @return array<string, mixed> */
    abstract protected function reglas(?Model $registro): array;

    /** @return array<int, array<string, mixed>> */
    protected function filtros(): array
    {
        return [];
    }

    /** Botones adicionales en la cabecera del listado. */
    protected function accionesExtra(Request $request): array
    {
        return [];
    }

    /** Botones adicionales por fila (la URL usa __ID__ como comodín). */
    protected function accionesFila(): array
    {
        return [];
    }

    protected function consulta(): Builder
    {
        return $this->modelo::query()->with($this->con);
    }

    protected function aplicarFiltros(Builder $consulta, Request $request): void
    {
        foreach ($this->filtros() as $filtro) {
            $valor = $request->query($filtro['clave']);

            if ($valor === null || $valor === '') {
                continue;
            }

            $columna = $filtro['columna'] ?? $filtro['clave'];

            if (($filtro['tipo'] ?? 'select') === 'date') {
                $consulta->whereDate($columna, $filtro['operador'] ?? '=', $valor);
            } else {
                $consulta->where($columna, $valor);
            }
        }
    }

    protected function aplicarBusqueda(Builder $consulta, string $texto): void
    {
        $consulta->where(function (Builder $q) use ($texto) {
            foreach ($this->buscarEn as $columna) {
                $q->orWhereLike($columna, '%'.$texto.'%');
            }
        });
    }

    /** Datos de cada fila del listado. */
    protected function fila(Model $registro): array
    {
        return $registro->toArray();
    }

    /** Valores iniciales del formulario de edición. */
    protected function valores(Model $registro): array
    {
        return $registro->toArray();
    }

    /** Ajustes finales a los datos validados antes de guardar. */
    protected function prepararDatos(array $datos, Request $request, ?Model $registro): array
    {
        return $datos;
    }

    protected function despuesDeGuardar(Model $registro, Request $request): void
    {
        //
    }

    public function index(Request $request): Response
    {
        $consulta = $this->consulta();
        $texto = trim((string) $request->query('q', ''));

        if ($texto !== '' && $this->buscarEn) {
            $this->aplicarBusqueda($consulta, $texto);
        }

        $this->aplicarFiltros($consulta, $request);

        $registros = $consulta
            ->orderBy($this->ordenarPor, $this->direccion)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Model $registro) => $this->fila($registro));

        return Inertia::render('admin/crud/Index', [
            'config' => $this->config($request),
            'columnas' => $this->columnas(),
            'filtros' => $this->filtros(),
            'registros' => $registros,
            'consulta' => $request->only(array_merge(['q'], array_column($this->filtros(), 'clave'))),
            'buscar' => count($this->buscarEn) > 0,
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($this->puedeCrear, 404);

        $valores = [];

        foreach ($this->campos() as $campo) {
            $valores[$campo['nombre']] = $campo['defecto'] ?? ($request->query($campo['nombre']) ?? null);
        }

        return Inertia::render('admin/crud/Form', [
            'config' => $this->config($request),
            'campos' => $this->campos(),
            'valores' => $valores,
            'registroId' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->puedeCrear, 404);

        $datos = $request->validate($this->reglas(null));
        $datos = $this->procesarArchivos($datos, $request, null);
        $datos = $this->quitarVirtuales($datos);
        $datos = $this->generarSlug($datos, null);
        $datos = $this->prepararDatos($datos, $request, null);

        $registro = $this->modelo::query()->create($datos);
        $this->despuesDeGuardar($registro, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => ucfirst($this->singular).($this->femenino ? ' registrada' : ' registrado').' correctamente.']);

        return redirect()->route($this->ruta.'.index');
    }

    public function edit(Request $request, int $id): Response
    {
        abort_unless($this->puedeEditar, 404);

        $registro = $this->consulta()->findOrFail($id);

        return Inertia::render('admin/crud/Form', [
            'config' => $this->config($request),
            'campos' => $this->campos(),
            'valores' => $this->valores($registro),
            'registroId' => $registro->getKey(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->puedeEditar, 404);

        $registro = $this->modelo::query()->findOrFail($id);

        $datos = $request->validate($this->reglas($registro));
        $datos = $this->procesarArchivos($datos, $request, $registro);
        $datos = $this->quitarVirtuales($datos);
        $datos = $this->generarSlug($datos, $registro);
        $datos = $this->prepararDatos($datos, $request, $registro);

        $registro->update($datos);
        $this->despuesDeGuardar($registro, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => ucfirst($this->singular).($this->femenino ? ' actualizada' : ' actualizado').' correctamente.']);

        return redirect()->route($this->ruta.'.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->puedeEliminar, 404);

        $registro = $this->modelo::query()->findOrFail($id);

        try {
            $registro->delete();
            Inertia::flash('toast', ['type' => 'success', 'message' => ucfirst($this->singular).($this->femenino ? ' eliminada.' : ' eliminado.')]);
        } catch (QueryException) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar: tiene información relacionada.']);
        }

        return back();
    }

    /** Configuración que reciben las vistas genéricas. */
    protected function config(Request $request): array
    {
        $ruta = fn (string $accion, bool $conId = false) => Route::has($this->ruta.'.'.$accion)
            ? route($this->ruta.'.'.$accion, $conId ? ['id' => '__ID__'] : [])
            : null;

        return [
            'titulo' => $this->titulo,
            'singular' => $this->singular,
            'femenino' => $this->femenino,
            'descripcion' => $this->descripcion,
            'urls' => [
                'index' => $ruta('index'),
                'crear' => $this->puedeCrear ? $ruta('create') : null,
                'guardar' => $this->puedeCrear ? $ruta('store') : null,
                'editar' => $this->puedeEditar ? $ruta('edit', true) : null,
                'actualizar' => $this->puedeEditar ? $ruta('update', true) : null,
                'eliminar' => $this->puedeEliminar ? $ruta('destroy', true) : null,
            ],
            'acciones' => $this->accionesExtra($request),
            'accionesFila' => $this->accionesFila(),
        ];
    }

    /**
     * Guarda los archivos nuevos, elimina los reemplazados y conserva
     * el valor anterior cuando no se sube nada.
     */
    protected function procesarArchivos(array $datos, Request $request, ?Model $registro): array
    {
        foreach ($this->campos() as $campo) {
            if (! in_array($campo['tipo'], ['image', 'file'], true)) {
                continue;
            }

            $nombre = $campo['nombre'];
            $anterior = $registro?->getAttribute($nombre);

            if ($request->hasFile($nombre)) {
                $datos[$nombre] = $request->file($nombre)->store($this->carpeta, 'public');
                $this->borrarArchivo($anterior);
            } elseif ($request->boolean($nombre.'_quitar')) {
                $datos[$nombre] = null;
                $this->borrarArchivo($anterior);
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

    protected function quitarVirtuales(array $datos): array
    {
        foreach ($this->campos() as $campo) {
            if (! empty($campo['virtual']) || $campo['tipo'] === 'readonly') {
                unset($datos[$campo['nombre']]);
            }
        }

        return $datos;
    }

    protected function generarSlug(array $datos, ?Model $registro): array
    {
        if (! $this->slugDesde || empty($datos[$this->slugDesde])) {
            return $datos;
        }

        $cambio = ! $registro || $registro->getAttribute($this->slugDesde) !== $datos[$this->slugDesde];

        if (! empty($datos['slug']) && (! $registro || $datos['slug'] !== $registro->getAttribute('slug'))) {
            $base = Str::slug($datos['slug']);
        } elseif ($cambio || ! $registro?->getAttribute('slug')) {
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
     * Opciones para un select a partir de un modelo.
     *
     * @param  class-string<Model>  $modelo
     */
    protected function opciones(string $modelo, string $etiqueta = 'nombre', string $orden = 'nombre'): array
    {
        return $modelo::query()->orderBy($orden)->get()
            ->map(fn (Model $m) => ['value' => $m->getKey(), 'label' => (string) $m->getAttribute($etiqueta)])
            ->all();
    }

    /** Convierte un arreglo clave => etiqueta en opciones para select. */
    protected function lista(array $valores): array
    {
        return collect($valores)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all();
    }
}

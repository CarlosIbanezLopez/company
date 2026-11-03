<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Materia;
use App\Models\Noticia;
use App\Models\Pagina;
use App\Models\ProyectoInvestigacion;
use App\Models\RevistaArticulo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Buscador interno: localiza información por palabras clave y agrupa los resultados por sección (RF-17).
 */
class BuscadorController extends Controller
{
    public function index(Request $request): Response
    {
        $texto = trim((string) $request->query('q', ''));
        $grupos = [];

        if (mb_strlen($texto) >= 3) {
            $grupos = array_values(array_filter([
                $this->grupo('Noticias', Noticia::query()->where('estado', 'publicado'), ['titulo', 'resumen', 'contenido'], $texto,
                    fn ($n) => ['titulo' => $n->titulo, 'extracto' => $n->resumen, 'url' => route('noticias.show', $n->slug), 'fecha' => $n->fecha_publicacion?->format('d/m/Y')]),
                $this->grupo('Eventos', Evento::query()->where('publicado', true), ['titulo', 'descripcion', 'lugar'], $texto,
                    fn ($e) => ['titulo' => $e->titulo, 'extracto' => Str::limit(strip_tags((string) $e->descripcion), 160), 'url' => route('eventos.show', $e->slug), 'fecha' => $e->fecha?->format('d/m/Y')]),
                $this->grupo('Documentos', Documento::query()->where('publicado', true), ['titulo', 'descripcion'], $texto,
                    fn ($d) => ['titulo' => $d->titulo, 'extracto' => $d->descripcion, 'url' => route('documentos', ['q' => $d->titulo]), 'fecha' => $d->fecha_publicacion?->format('d/m/Y')]),
                $this->grupo('Materias', Materia::query(), ['sigla', 'nombre', 'descripcion'], $texto,
                    fn ($m) => ['titulo' => $m->sigla.' - '.$m->nombre, 'extracto' => $m->semestre.'° semestre', 'url' => route('malla', ['materia' => $m->id]), 'fecha' => null]),
                $this->grupo('Proyectos de investigación', ProyectoInvestigacion::query()->where('publicado', true), ['titulo', 'descripcion', 'responsables'], $texto,
                    fn ($p) => ['titulo' => $p->titulo, 'extracto' => $p->responsables, 'url' => route('investigacion.show', $p->slug), 'fecha' => $p->fecha_inicio?->format('d/m/Y')]),
                $this->grupo('Artículos de la Revista INNOVAR', RevistaArticulo::query()->with('edicion')->whereHas('edicion', fn ($q) => $q->where('publicado', true)), ['titulo', 'autores', 'resumen', 'palabras_clave'], $texto,
                    fn ($a) => ['titulo' => $a->titulo, 'extracto' => $a->autores, 'url' => route('revista.show', $a->edicion->numero), 'fecha' => 'Edición N° '.$a->edicion->numero]),
                $this->grupo('Páginas del sitio', Pagina::query()->where('publicado', true)->whereIn('seccion', ['institucional', 'academico']), ['titulo', 'resumen', 'contenido'], $texto,
                    fn ($p) => ['titulo' => $p->titulo, 'extracto' => $p->resumen, 'url' => route($p->seccion, $p->slug), 'fecha' => null]),
            ]));
        }

        return Inertia::render('sitio/Buscar', [
            'q' => $texto,
            'grupos' => $grupos,
            'total' => array_sum(array_map(fn ($g) => count($g['resultados']), $grupos)),
        ]);
    }

    private function grupo(string $seccion, Builder $consulta, array $columnas, string $texto, callable $formato): ?array
    {
        $resultados = $consulta
            ->where(function (Builder $q) use ($columnas, $texto) {
                foreach ($columnas as $columna) {
                    $q->orWhereLike($columna, '%'.$texto.'%');
                }
            })
            ->limit(10)
            ->get()
            ->map($formato)
            ->all();

        return $resultados ? ['seccion' => $seccion, 'resultados' => $resultados] : null;
    }
}

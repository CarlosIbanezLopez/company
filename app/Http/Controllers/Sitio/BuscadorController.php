<?php

namespace App\Http\Controllers\Sitio;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sitio\Concerns\FiltrosConsulta;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Materia;
use App\Models\Noticia;
use App\Models\Pagina;
use App\Models\ProyectoInvestigacion;
use App\Models\RevistaArticulo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Buscador interno (RF-17): localiza información por palabras clave y agrupa los resultados por sección.
 * También ofrece sugerencias predictivas para el cuadro de búsqueda del encabezado.
 * Las URL de los resultados son rutas de la SPA (p. ej. /noticias/{slug}).
 */
class BuscadorController extends Controller
{
    use FiltrosConsulta;

    private const MINIMO_BUSQUEDA = 3;

    private const POR_SECCION = 10;

    private const MINIMO_SUGERENCIAS = 2;

    private const MAXIMO_SUGERENCIAS = 8;

    public function index(Request $request): JsonResponse
    {
        $texto = $this->texto($request, 'q') ?? '';
        $grupos = [];

        if (mb_strlen($texto) >= self::MINIMO_BUSQUEDA) {
            foreach ($this->secciones() as $seccion) {
                $consulta = ($seccion['consulta'])();
                $this->contiene($consulta, $seccion['columnas'], $texto);
                // Primero los resultados cuyo título contiene el texto buscado.
                $this->priorizar($consulta, $seccion['titulo'], ['%'.$this->textoNormalizado($consulta, $texto).'%']);
                ($seccion['orden'])($consulta);

                $resultados = $consulta->limit(self::POR_SECCION)->get()
                    ->map(fn ($modelo) => [...($seccion['enlace'])($modelo), ...($seccion['detalle'])($modelo)])
                    ->map(fn (array $r) => ['titulo' => $r['titulo'], 'extracto' => $r['extracto'], 'url' => $r['url'], 'fecha' => $r['fecha']])
                    ->all();

                if ($resultados) {
                    $grupos[] = ['seccion' => $seccion['nombre'], 'resultados' => $resultados];
                }
            }
        }

        return response()->json([
            'q' => $texto,
            'grupos' => $grupos,
            'total' => array_sum(array_map(fn (array $g) => count($g['resultados']), $grupos)),
        ]);
    }

    /**
     * Búsqueda predictiva: solo compara los títulos, trae las columnas mínimas y prioriza los títulos
     * que empiezan con el texto, luego los que tienen una palabra que empieza con él.
     */
    public function sugerencias(Request $request): JsonResponse
    {
        $texto = $this->texto($request, 'q', 80) ?? '';
        $sugerencias = [];

        if (mb_strlen($texto) >= self::MINIMO_SUGERENCIAS) {
            foreach ($this->secciones() as $seccion) {
                $consulta = ($seccion['consulta'])();
                $normalizado = $this->textoNormalizado($consulta, $texto);

                $this->contiene($consulta, $seccion['titulo'], $texto);
                $this->priorizar($consulta, $seccion['titulo'], [$normalizado.'%', '% '.$normalizado.'%']);
                ($seccion['orden'])($consulta);

                foreach ($consulta->limit(self::MAXIMO_SUGERENCIAS)->get($seccion['campos']) as $modelo) {
                    $enlace = ($seccion['enlace'])($modelo);
                    $sugerencias[] = [
                        'titulo' => $enlace['titulo'],
                        'seccion' => $seccion['nombre'],
                        'url' => $enlace['url'],
                        'rango' => $this->rango($enlace['titulo'], $texto),
                    ];
                }
            }

            // usort es estable: a igual rango se conserva el orden de las secciones y de cada consulta.
            usort($sugerencias, fn (array $a, array $b) => $a['rango'] <=> $b['rango']);

            $sugerencias = array_map(
                fn (array $s) => ['titulo' => $s['titulo'], 'seccion' => $s['seccion'], 'url' => $s['url']],
                array_slice($sugerencias, 0, self::MAXIMO_SUGERENCIAS),
            );
        }

        return response()->json([
            'q' => $texto,
            'sugerencias' => $sugerencias,
        ]);
    }

    /**
     * Secciones en las que se busca (solo se comparan columnas de texto):
     *  - columnas: dónde se busca en la búsqueda completa; titulo: columnas del título (prioridad y sugerencias).
     *  - campos: columnas mínimas que se leen para las sugerencias (las que usa "enlace").
     *  - enlace: {titulo, url} con la ruta de la SPA; detalle: {extracto, fecha} de la búsqueda completa.
     *
     * @return array<int, array<string, mixed>>
     */
    private function secciones(): array
    {
        return [
            [
                'nombre' => 'Noticias',
                'consulta' => fn () => Noticia::query()->where('estado', 'publicado'),
                'columnas' => ['titulo', 'resumen', 'contenido'],
                'titulo' => ['titulo'],
                'campos' => ['id', 'titulo', 'slug'],
                'orden' => fn (Builder $q) => $q->orderByDesc('fecha_publicacion')->orderByDesc('id'),
                'enlace' => fn (Noticia $n) => ['titulo' => $n->titulo, 'url' => '/noticias/'.$n->slug],
                'detalle' => fn (Noticia $n) => ['extracto' => $n->resumen, 'fecha' => $n->fecha_publicacion?->format('d/m/Y')],
            ],
            [
                'nombre' => 'Eventos',
                'consulta' => fn () => Evento::query()->where('publicado', true),
                'columnas' => ['titulo', 'descripcion', 'lugar'],
                'titulo' => ['titulo'],
                'campos' => ['id', 'titulo', 'slug'],
                'orden' => fn (Builder $q) => $q->orderByDesc('fecha')->orderByDesc('id'),
                'enlace' => fn (Evento $e) => ['titulo' => $e->titulo, 'url' => '/eventos/'.$e->slug],
                'detalle' => fn (Evento $e) => [
                    'extracto' => Str::limit(strip_tags((string) $e->descripcion), 160),
                    'fecha' => $e->fecha?->format('d/m/Y'),
                ],
            ],
            [
                'nombre' => 'Documentos',
                'consulta' => fn () => Documento::query()->where('publicado', true),
                'columnas' => ['titulo', 'descripcion'],
                'titulo' => ['titulo'],
                'campos' => ['id', 'titulo'],
                'orden' => fn (Builder $q) => $q->orderByRaw('fecha_publicacion is null')->orderByDesc('fecha_publicacion')->orderByDesc('id'),
                'enlace' => fn (Documento $d) => ['titulo' => $d->titulo, 'url' => '/biblioteca?q='.rawurlencode($d->titulo)],
                'detalle' => fn (Documento $d) => ['extracto' => $d->descripcion, 'fecha' => $d->fecha_publicacion?->format('d/m/Y')],
            ],
            [
                'nombre' => 'Materias',
                'consulta' => fn () => Materia::query(),
                'columnas' => ['sigla', 'nombre', 'descripcion'],
                'titulo' => ['sigla', 'nombre'],
                'campos' => ['id', 'sigla', 'nombre'],
                'orden' => fn (Builder $q) => $q->orderBy('semestre')->orderBy('sigla'),
                'enlace' => fn (Materia $m) => ['titulo' => $m->sigla.' - '.$m->nombre, 'url' => '/malla-curricular?materia='.$m->id],
                'detalle' => fn (Materia $m) => ['extracto' => $m->semestre.'° semestre', 'fecha' => null],
            ],
            [
                'nombre' => 'Proyectos de investigación',
                'consulta' => fn () => ProyectoInvestigacion::query()->where('publicado', true),
                'columnas' => ['titulo', 'descripcion', 'responsables'],
                'titulo' => ['titulo'],
                'campos' => ['id', 'titulo', 'slug'],
                'orden' => fn (Builder $q) => $q->orderByRaw('fecha_inicio is null')->orderByDesc('fecha_inicio')->orderByDesc('id'),
                'enlace' => fn (ProyectoInvestigacion $p) => ['titulo' => $p->titulo, 'url' => '/investigacion/'.$p->slug],
                'detalle' => fn (ProyectoInvestigacion $p) => ['extracto' => $p->responsables, 'fecha' => $p->fecha_inicio?->format('d/m/Y')],
            ],
            [
                'nombre' => 'Artículos de la Revista INNOVAR',
                'consulta' => fn () => RevistaArticulo::query()->with('edicion:id,numero')
                    ->whereHas('edicion', fn ($q) => $q->where('publicado', true)),
                'columnas' => ['titulo', 'autores', 'resumen', 'palabras_clave'],
                'titulo' => ['titulo'],
                'campos' => ['id', 'titulo', 'revista_edicion_id'],
                'orden' => fn (Builder $q) => $q->orderByDesc('revista_edicion_id')->orderBy('orden')->orderBy('id'),
                'enlace' => fn (RevistaArticulo $a) => ['titulo' => $a->titulo, 'url' => '/revista-innovar/'.$a->edicion->numero],
                'detalle' => fn (RevistaArticulo $a) => ['extracto' => $a->autores, 'fecha' => 'Edición N° '.$a->edicion->numero],
            ],
            [
                'nombre' => 'Páginas del sitio',
                'consulta' => fn () => Pagina::query()->where('publicado', true)->whereIn('seccion', ['institucional', 'academico']),
                'columnas' => ['titulo', 'resumen', 'contenido'],
                'titulo' => ['titulo'],
                'campos' => ['id', 'seccion', 'titulo', 'slug'],
                'orden' => fn (Builder $q) => $q->orderBy('orden')->orderBy('id'),
                'enlace' => fn (Pagina $p) => ['titulo' => $p->titulo, 'url' => '/'.$p->seccion.'/'.$p->slug],
                'detalle' => fn (Pagina $p) => ['extracto' => $p->resumen, 'fecha' => null],
            ],
        ];
    }

    /**
     * Ordena primero los registros cuyo título cumple el primer patrón (like), luego el segundo, etc.
     *
     * @template TModelo of Model
     *
     * @param  Builder<TModelo>  $consulta
     * @param  array<int, literal-string>  $columnas
     * @param  array<int, string>  $patrones  ya normalizados con textoNormalizado()
     */
    private function priorizar(Builder $consulta, array $columnas, array $patrones): void
    {
        $condiciones = array_map(fn (string $columna) => $this->columnaNormalizada($consulta, $columna).' like ?', $columnas);
        $condicion = implode(' or ', $condiciones);

        foreach ($patrones as $patron) {
            $consulta->orderByRaw('case when '.$condicion.' then 0 else 1 end', array_fill(0, count($columnas), $patron));
        }
    }

    /** 0: el título empieza con el texto; 1: una de sus palabras empieza con él; 2: lo contiene en otra posición. */
    private function rango(string $titulo, string $texto): int
    {
        $titulo = Str::lower(Str::ascii($titulo));
        $texto = Str::lower(Str::ascii($texto));

        if (str_starts_with($titulo, $texto)) {
            return 0;
        }

        return preg_match('/(^|[^a-z0-9])'.preg_quote($texto, '/').'/', $titulo) ? 1 : 2;
    }
}

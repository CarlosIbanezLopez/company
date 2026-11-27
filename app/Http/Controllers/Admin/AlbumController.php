<?php

namespace App\Http\Controllers\Admin;

use App\Models\Album;
use App\Models\Foto;
use App\Support\ReglasCliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * CU6 - Galería multimedia: álbumes de fotografías por actividad (RF-14).
 * Las fotografías de cada álbum se guardan en storage/app/public/galeria/{id}.
 *
 * @extends CrudController<Album>
 */
class AlbumController extends CrudController
{
    protected string $modelo = Album::class;

    protected string $titulo = 'Galería - Álbumes';

    protected string $singular = 'álbum';

    protected string $recurso = 'galeria';

    protected string $uri = 'albumes';

    protected array $buscarEn = ['titulo', 'descripcion'];

    protected string $ordenarPor = 'fecha';

    protected ?string $slugDesde = 'titulo';

    protected string $carpeta = 'galeria';

    /** @return Builder<Album> */
    protected function consulta(): Builder
    {
        return Album::query()->withCount(['fotos', 'videos']);
    }

    protected function accionesFila(): array
    {
        return [['etiqueta' => 'Fotos', 'url' => '/admin/albumes/__ID__/fotos', 'icono' => 'images', 'tipo' => 'enlace']];
    }

    protected function campos(): array
    {
        return [
            ['nombre' => 'titulo', 'etiqueta' => 'Título del álbum', 'tipo' => 'text', 'requerido' => true, 'ancho' => 'full'],
            ['nombre' => 'fecha', 'etiqueta' => 'Fecha de la actividad', 'tipo' => 'date'],
            ['nombre' => 'portada', 'etiqueta' => 'Imagen de portada', 'tipo' => 'image'],
            ['nombre' => 'descripcion', 'etiqueta' => 'Descripción', 'tipo' => 'textarea', 'ancho' => 'full'],
            ['nombre' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'portada_url', 'etiqueta' => 'Portada', 'tipo' => 'image'],
            ['clave' => 'titulo', 'etiqueta' => 'Álbum'],
            ['clave' => 'fecha', 'etiqueta' => 'Fecha', 'tipo' => 'date'],
            ['clave' => 'fotos_count', 'etiqueta' => 'Fotos'],
            ['clave' => 'videos_count', 'etiqueta' => 'Videos'],
            ['clave' => 'publicado', 'etiqueta' => 'Publicado', 'tipo' => 'bool'],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'fecha' => ['nullable', 'date'],
            'portada' => ['nullable', 'image', 'max:4096'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'publicado' => ['boolean'],
        ];
    }

    /**
     * La portada puede ser una de las fotografías del álbum: ese archivo no se borra al reemplazarla.
     */
    protected function borrarArchivo(?string $ruta): void
    {
        if ($ruta && Foto::query()->where('imagen', $ruta)->exists()) {
            return;
        }

        parent::borrarArchivo($ruta);
    }

    /**
     * Al eliminar el álbum la base de datos borra sus fotografías en cascada; aquí se borran sus archivos.
     */
    public function destroy(int $id): JsonResponse
    {
        $respuesta = parent::destroy($id);

        if ($respuesta->isSuccessful()) {
            Storage::disk('public')->deleteDirectory('galeria/'.$id);
        }

        return $respuesta;
    }

    /** @return array<string, array<int, string>> */
    private static function reglasFotos(): array
    {
        return [
            'fotos' => ['required', 'array', 'max:20'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * GET /api/admin/albumes/{id}/fotos : álbum con sus fotografías (antes la página admin/Fotos).
     * urls.subir / urls.eliminar son rutas de la API relativas a /api/ (__FOTO__ = id de la fotografía);
     * urls.volver es la ruta de la SPA del listado de álbumes.
     */
    public function fotos(Request $request, int $id): JsonResponse
    {
        return response()->json($this->datosFotos($request, $this->albumConFotos($id)));
    }

    /**
     * POST /api/admin/albumes/{id}/fotos (multipart: fotos[] y descripcion opcional).
     */
    public function subirFotos(Request $request, int $id): JsonResponse
    {
        $album = Album::query()->findOrFail($id);

        $request->validate(self::reglasFotos(), [], [
            'fotos' => 'Fotografías',
            'fotos.*' => 'Fotografía',
            'descripcion' => 'Descripción',
        ]);

        DB::transaction(function () use ($album, $request) {
            $orden = (int) $album->fotos()->max('orden');

            foreach ($request->file('fotos') as $archivo) {
                $album->fotos()->create([
                    'imagen' => $archivo->store('galeria/'.$album->id, 'public'),
                    'descripcion' => $request->input('descripcion'),
                    'orden' => ++$orden,
                ]);
            }

            if (! $album->portada) {
                $album->update(['portada' => $album->fotos()->orderBy('orden')->orderBy('id')->value('imagen')]);
            }
        });

        return response()->json([
            'message' => 'Fotografías agregadas al álbum.',
            ...$this->datosFotos($request, $this->albumConFotos($id)),
        ], 201);
    }

    /**
     * DELETE /api/admin/albumes/{id}/fotos/{foto}
     */
    public function eliminarFoto(int $id, int $foto): JsonResponse
    {
        $album = Album::query()->findOrFail($id);
        $fotografia = Foto::query()->where('album_id', $album->id)->findOrFail($foto);

        DB::transaction(function () use ($album, $fotografia) {
            $fotografia->delete();

            // Si era la portada del álbum, se usa la siguiente fotografía (o ninguna).
            if ($album->portada === $fotografia->imagen) {
                $album->update(['portada' => $album->fotos()->orderBy('orden')->orderBy('id')->value('imagen')]);
            }
        });

        return response()->json(['message' => 'Fotografía eliminada.']);
    }

    private function albumConFotos(int $id): Album
    {
        return Album::query()->with(['fotos' => fn ($q) => $q->orderBy('orden')->orderBy('id')])->findOrFail($id);
    }

    /** @return array<string, mixed> */
    private function datosFotos(Request $request, Album $album): array
    {
        $usuario = $request->user();

        return [
            'album' => $album,
            'urls' => [
                'subir' => "admin/albumes/{$album->id}/fotos",
                'eliminar' => "admin/albumes/{$album->id}/fotos/__FOTO__",
                'volver' => '/admin/albumes',
            ],
            'permisos' => [
                'subir' => (bool) $usuario?->tienePermiso('galeria.crear'),
                'eliminar' => (bool) $usuario?->tienePermiso('galeria.eliminar'),
            ],
            'reglas' => ReglasCliente::todas(self::reglasFotos()),
        ];
    }
}

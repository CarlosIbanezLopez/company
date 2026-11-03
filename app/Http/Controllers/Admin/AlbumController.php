<?php

namespace App\Http\Controllers\Admin;

use App\Models\Album;
use App\Models\Foto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CU6 - Galería multimedia: álbumes de fotografías por actividad (RF-14).
 */
class AlbumController extends CrudController
{
    protected string $modelo = Album::class;

    protected string $titulo = 'Galería - Álbumes';

    protected string $singular = 'álbum';

    protected string $ruta = 'admin.albumes';

    protected array $buscarEn = ['titulo', 'descripcion'];

    protected string $ordenarPor = 'fecha';

    protected ?string $slugDesde = 'titulo';

    protected string $carpeta = 'galeria';

    protected function consulta(): Builder
    {
        return Album::query()->withCount(['fotos', 'videos']);
    }

    protected function accionesFila(): array
    {
        return [['etiqueta' => 'Fotos', 'icono' => 'images', 'url' => route('admin.albumes.fotos', ['id' => '__ID__'])]];
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

    public function fotos(int $id): Response
    {
        $album = Album::query()->with(['fotos' => fn ($q) => $q->orderBy('orden')->orderBy('id')])->findOrFail($id);

        return Inertia::render('admin/Fotos', [
            'album' => $album,
            'urls' => [
                'subir' => route('admin.albumes.fotos.store', $album->id),
                'eliminar' => route('admin.albumes.fotos.destroy', ['id' => $album->id, 'foto' => '__FOTO__']),
                'volver' => route('admin.albumes.index'),
            ],
        ]);
    }

    public function subirFotos(Request $request, int $id): RedirectResponse
    {
        $album = Album::query()->findOrFail($id);

        $request->validate([
            'fotos' => ['required', 'array', 'max:20'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $orden = (int) $album->fotos()->max('orden');

        foreach ($request->file('fotos') as $archivo) {
            $album->fotos()->create([
                'imagen' => $archivo->store('galeria/'.$album->id, 'public'),
                'descripcion' => $request->input('descripcion'),
                'orden' => ++$orden,
            ]);
        }

        if (! $album->portada) {
            $album->update(['portada' => $album->fotos()->orderBy('orden')->value('imagen')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fotografías agregadas al álbum.']);

        return back();
    }

    public function eliminarFoto(int $id, int $foto): RedirectResponse
    {
        Foto::query()->where('album_id', $id)->findOrFail($foto)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Fotografía eliminada.']);

        return back();
    }
}

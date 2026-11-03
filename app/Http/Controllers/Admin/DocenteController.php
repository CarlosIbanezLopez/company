<?php

namespace App\Http\Controllers\Admin;

use App\Models\Docente;
use App\Support\Catalogos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * CU3 - Plantel docente y autoridades (RF-03), con control de la información publicable.
 */
class DocenteController extends CrudController
{
    protected string $modelo = Docente::class;

    protected string $titulo = 'Autoridades y docentes';

    protected string $singular = 'docente';

    protected string $ruta = 'admin.docentes';

    protected ?string $descripcion = 'Datos del plantel. Puede ocultar el correo o la formación si no está autorizada su publicación.';

    protected array $buscarEn = ['nombre', 'especialidad', 'materias', 'cargo'];

    protected string $ordenarPor = 'orden';

    protected string $direccion = 'asc';

    protected string $carpeta = 'docentes';

    protected function campos(): array
    {
        return [
            ['nombre' => 'nombre', 'etiqueta' => 'Nombre completo', 'tipo' => 'text', 'requerido' => true],
            ['nombre' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'select', 'requerido' => true, 'opciones' => $this->lista(Catalogos::TIPOS_DOCENTE), 'defecto' => 'docente'],
            ['nombre' => 'cargo', 'etiqueta' => 'Función o cargo', 'tipo' => 'text'],
            ['nombre' => 'formacion', 'etiqueta' => 'Formación académica', 'tipo' => 'text'],
            ['nombre' => 'especialidad', 'etiqueta' => 'Área de especialidad', 'tipo' => 'text'],
            ['nombre' => 'materias', 'etiqueta' => 'Materias que imparte', 'tipo' => 'text'],
            ['nombre' => 'correo', 'etiqueta' => 'Correo institucional', 'tipo' => 'email'],
            ['nombre' => 'orden', 'etiqueta' => 'Orden', 'tipo' => 'number', 'defecto' => 0],
            ['nombre' => 'foto', 'etiqueta' => 'Fotografía', 'tipo' => 'image'],
            ['nombre' => 'visible', 'etiqueta' => 'Mostrar en el sitio', 'tipo' => 'checkbox', 'defecto' => true],
            ['nombre' => 'mostrar_correo', 'etiqueta' => 'Autoriza publicar su correo', 'tipo' => 'checkbox', 'defecto' => false],
            ['nombre' => 'mostrar_formacion', 'etiqueta' => 'Autoriza publicar su formación', 'tipo' => 'checkbox', 'defecto' => true],
        ];
    }

    protected function columnas(): array
    {
        return [
            ['clave' => 'foto_url', 'etiqueta' => 'Foto', 'tipo' => 'image'],
            ['clave' => 'nombre', 'etiqueta' => 'Nombre'],
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'tipo' => 'badge'],
            ['clave' => 'cargo', 'etiqueta' => 'Cargo'],
            ['clave' => 'especialidad', 'etiqueta' => 'Especialidad'],
            ['clave' => 'visible', 'etiqueta' => 'Visible', 'tipo' => 'bool'],
        ];
    }

    protected function filtros(): array
    {
        return [
            ['clave' => 'tipo', 'etiqueta' => 'Tipo', 'opciones' => $this->lista(Catalogos::TIPOS_DOCENTE)],
            ['clave' => 'visible', 'etiqueta' => 'Visibilidad', 'opciones' => [['value' => '1', 'label' => 'Visibles'], ['value' => '0', 'label' => 'Ocultos']]],
        ];
    }

    protected function reglas(?Model $registro): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'tipo' => ['required', Rule::in(array_keys(Catalogos::TIPOS_DOCENTE))],
            'cargo' => ['nullable', 'string', 'max:120'],
            'formacion' => ['nullable', 'string', 'max:255'],
            'especialidad' => ['nullable', 'string', 'max:150'],
            'materias' => ['nullable', 'string', 'max:255'],
            'correo' => ['nullable', 'email', 'max:150'],
            'orden' => ['integer', 'min:0'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'visible' => ['boolean'],
            'mostrar_correo' => ['boolean'],
            'mostrar_formacion' => ['boolean'],
        ];
    }
}

<?php

namespace Tests\Feature\Api;

use App\Support\ReglasCliente;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

/**
 * App\Support\ReglasCliente: traducción de las reglas de validación de Laravel
 * al objeto ReglasCliente que la SPA usa para validar en el navegador.
 */
class AdminReglasClienteTest extends TestCase
{
    public function test_reglas_en_texto_y_en_arreglo(): void
    {
        $this->assertSame(['requerido' => true, 'max' => 200], ReglasCliente::desde(['required', 'string', 'max:200']));
        $this->assertSame(['requerido' => false, 'email' => true, 'max' => 150], ReglasCliente::desde('nullable|email|max:150'));
        $this->assertSame(['requerido' => false, 'url' => true, 'max' => 255], ReglasCliente::desde(['nullable', 'url', 'max:255']));
        $this->assertSame(['requerido' => true, 'numerico' => true, 'entero' => true, 'entre' => [1970, 2027]], ReglasCliente::desde(['required', 'integer', 'between:1970,2027']));
        $this->assertSame(['requerido' => false, 'numerico' => true, 'min' => 0], ReglasCliente::desde(['numeric', 'min:0']));
        $this->assertSame(['requerido' => true, 'fecha' => true], ReglasCliente::desde(['required', 'date']));
        $this->assertSame(['requerido' => false, 'fecha' => true], ReglasCliente::desde(['nullable', 'date', 'after_or_equal:fecha_inicio']));
        $this->assertSame(['requerido' => false, 'hora' => true], ReglasCliente::desde(['nullable', 'date_format:H:i,H:i:s']));
        $this->assertSame(['requerido' => false, 'fecha' => true], ReglasCliente::desde(['date_format:Y-m-d']));
        $this->assertSame(['requerido' => false, 'imagen' => true, 'max' => 4096], ReglasCliente::desde(['nullable', 'image', 'max:4096']));
        $this->assertSame(['requerido' => true, 'mimes' => ['pdf'], 'max' => 20480], ReglasCliente::desde(['required', 'file', 'mimes:pdf', 'max:20480']));
        $this->assertSame(['requerido' => true, 'confirmado' => true], ReglasCliente::desde(['required', 'confirmed']));
        $this->assertSame(['requerido' => false, 'enLista' => ['1', '0']], ReglasCliente::desde('in:1,0'));
        $this->assertSame(['requerido' => false], ReglasCliente::desde(['boolean']));
        $this->assertSame(['requerido' => false], ReglasCliente::desde(null));
    }

    public function test_objetos_de_regla(): void
    {
        $this->assertSame(['requerido' => true, 'enLista' => ['borrador', 'publicado', 'con "comillas" | barra']], ReglasCliente::desde(['required', Rule::in(['borrador', 'publicado', 'con "comillas" | barra'])]));
        $this->assertSame(['requerido' => true, 'max' => 20], ReglasCliente::desde(['required', 'max:20', Rule::unique('materias', 'sigla')->ignore(3)]));
        $this->assertSame(['requerido' => false], ReglasCliente::desde([Rule::exists('roles', 'id'), Rule::notIn([5])]));
        $this->assertSame(['requerido' => true, 'min' => 8], ReglasCliente::desde(['required', 'string', Password::min(8)]));
        $this->assertSame(['requerido' => true], ReglasCliente::desde([Rule::requiredIf(true)]));
        $this->assertSame(['requerido' => false], ReglasCliente::desde([Rule::requiredIf(fn () => false)]));
        $this->assertSame(['requerido' => false, 'fecha' => true], ReglasCliente::desde([Rule::date()->afterToday()]));
        $this->assertSame(['requerido' => false, 'numerico' => true, 'min' => 1, 'max' => 9.5], ReglasCliente::desde([Rule::numeric()->min(1)->max(9.5)]));
        $this->assertSame(['requerido' => false, 'imagen' => true, 'max' => 500], ReglasCliente::desde([File::image()->max(500)]));
        $this->assertSame(['requerido' => false, 'max' => 2048, 'mimes' => ['pdf', 'docx']], ReglasCliente::desde([File::types(['pdf', 'docx'])->max(2048)]));
        $this->assertSame(['requerido' => false, 'email' => true], ReglasCliente::desde([Rule::email()]));
        $this->assertSame(['requerido' => false], ReglasCliente::desde([fn ($atributo, $valor, $fallar) => null]));
    }

    public function test_campos_y_atributos(): void
    {
        $campos = ReglasCliente::campos([
            ['nombre' => 'titulo', 'etiqueta' => 'Título', 'tipo' => 'text', 'defecto' => 'x'],
            ['nombre' => 'archivo', 'etiqueta' => 'Archivo PDF', 'tipo' => 'file', 'virtual' => true],
            ['nombre' => 'nota', 'etiqueta' => 'Nota', 'tipo' => 'readonly'],
        ], ['titulo' => ['required', 'max:200'], 'archivo' => 'nullable|mimes:pdf']);

        $this->assertSame([
            ['nombre' => 'titulo', 'etiqueta' => 'Título', 'tipo' => 'text', 'requerido' => true, 'reglas' => ['requerido' => true, 'max' => 200]],
            ['nombre' => 'archivo', 'etiqueta' => 'Archivo PDF', 'tipo' => 'file', 'requerido' => false, 'reglas' => ['requerido' => false, 'mimes' => ['pdf']]],
            ['nombre' => 'nota', 'etiqueta' => 'Nota', 'tipo' => 'readonly', 'requerido' => false, 'reglas' => ['requerido' => false]],
        ], $campos);

        $this->assertSame(
            ['imagen' => 'Imagen', 'imagen.*' => 'Imagen', 'correo' => 'Correo institucional', 'correo.*' => 'Correo institucional'],
            ReglasCliente::atributos([
                ['nombre' => 'imagen', 'etiqueta' => 'Imagen (1600x600 recomendado)'],
                ['nombre' => 'correo', 'etiqueta' => 'Correo institucional'],
            ]),
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\Convenio;
use App\Models\Graduado;
use App\Models\InnovaIntegrante;
use App\Models\InnovaPostulacion;
use App\Models\InnovaProyecto;
use App\Models\InnovaServicio;
use App\Models\InnovaSolicitud;
use App\Models\Rol;
use App\Models\User;
use App\Support\Catalogos;
use Illuminate\Database\Seeder;

/**
 * CU5 - Convenios, Empresa Junior INNOVA y graduados (datos de ejemplo).
 */
class VinculacionSeeder extends Seeder
{
    public function run(): void
    {
        $convenios = [
            ['Cámara de Industria, Comercio, Servicios y Turismo (ejemplo)', 'convenio', 'vigente', '2025-01-01', '2027-12-31', 'Cooperación interinstitucional para prácticas y capacitación.'],
            ['Banco de ejemplo S.A.', 'practica', 'vigente', '2026-02-01', '2026-12-31', 'Prácticas empresariales para estudiantes de últimos semestres.'],
            ['Gobierno Autónomo Municipal (ejemplo)', 'proyecto_conjunto', 'en_tramite', null, null, 'Proyecto conjunto de mejora de procesos administrativos.'],
            ['Asociación de PyMEs (ejemplo)', 'consultoria', 'vigente', '2026-03-15', '2027-03-15', 'Consultorías realizadas por la Empresa Junior INNOVA.'],
            ['Fundación de desarrollo comunitario (ejemplo)', 'interaccion_social', 'finalizado', '2024-05-01', '2025-05-01', 'Capacitación en gestión a emprendedores de barrio.'],
        ];

        foreach ($convenios as $i => [$institucion, $tipo, $estado, $inicio, $fin, $finalidad]) {
            Convenio::query()->create([
                'institucion' => $institucion, 'tipo' => $tipo, 'estado' => $estado, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
                'finalidad' => $finalidad, 'logo' => Recursos::imagen('convenios', strtok($institucion, '('), 400, 400, $i, null, true), 'publicado' => true,
            ]);
        }

        $servicios = [
            ['Diagnóstico empresarial', 'Evaluación de la situación actual de la empresa y propuesta de mejoras.'],
            ['Plan de negocios', 'Elaboración de planes de negocio para emprendimientos y nuevas líneas de productos.'],
            ['Estudio de mercado', 'Investigación de mercado, encuestas y análisis de la competencia.'],
            ['Manual de funciones y procesos', 'Diseño de organigramas, manuales de funciones y procedimientos.'],
        ];

        $idsServicios = [];

        foreach ($servicios as $i => [$nombre, $descripcion]) {
            $idsServicios[] = InnovaServicio::query()->create(['nombre' => $nombre, 'descripcion' => $descripcion, 'orden' => $i + 1])->id;
        }

        foreach ([['Integrante 1 (ejemplo)', 'Gerente general', '9° semestre'], ['Integrante 2 (ejemplo)', 'Jefe de proyectos', '8° semestre'], ['Integrante 3 (ejemplo)', 'Responsable de marketing', '7° semestre'], ['Integrante 4 (ejemplo)', 'Responsable de finanzas', '8° semestre'], ['Integrante 5 (ejemplo)', 'Consultor junior', '6° semestre']] as $i => [$nombre, $cargo, $semestre]) {
            InnovaIntegrante::query()->create(['nombre' => $nombre, 'cargo' => $cargo, 'semestre' => $semestre, 'orden' => $i + 1]);
        }

        foreach ([['Plan de negocios para cafetería universitaria', 'Emprendimiento local'], ['Estudio de mercado para tienda de repuestos', 'PyME comercial'], ['Manual de funciones para cooperativa', 'Cooperativa de servicios']] as $i => [$titulo, $cliente]) {
            InnovaProyecto::query()->create(['titulo' => $titulo, 'cliente' => $cliente, 'descripcion' => 'Proyecto de consultoría realizado por estudiantes de INNOVA.', 'fecha' => now()->subMonths(2 + $i * 3)->toDateString(), 'imagen' => Recursos::imagen('innova', $titulo, 1200, 675, $i + 2)]);
        }

        InnovaSolicitud::query()->create(['innova_servicio_id' => $idsServicios[0], 'empresa' => 'Comercial Ejemplo SRL', 'contacto' => 'Juan Pérez', 'correo' => 'contacto@ejemplo.test', 'telefono' => '70000000', 'mensaje' => 'Necesitamos un diagnóstico de nuestra área de ventas.', 'estado' => 'pendiente']);
        InnovaSolicitud::query()->create(['innova_servicio_id' => $idsServicios[2], 'empresa' => 'Emprendimiento Ejemplo', 'contacto' => 'María López', 'correo' => 'maria@ejemplo.test', 'mensaje' => 'Queremos un estudio de mercado para un nuevo producto.', 'estado' => 'en_proceso']);
        InnovaPostulacion::query()->create(['nombre' => 'Estudiante de ejemplo', 'registro' => '220000001', 'correo' => 'estudiante@ejemplo.test', 'semestre' => '5° semestre', 'motivacion' => 'Deseo aplicar lo aprendido en proyectos reales de consultoría.', 'estado' => 'pendiente']);

        $this->graduados();
    }

    private function graduados(): void
    {
        $faker = fake('es_ES');
        $situaciones = array_keys(Catalogos::SITUACIONES_LABORALES);
        $modalidades = array_keys(Catalogos::MODALIDADES_TITULACION);

        // Graduado con cuenta para probar la actualización de datos desde el sitio.
        $usuario = User::query()->updateOrCreate(['email' => 'graduado@carrera.test'], [
            'name' => 'Graduado de Prueba',
            'password' => 'password',
            'rol_id' => Rol::query()->where('nombre', 'Graduado')->value('id'),
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        Graduado::query()->create([
            'user_id' => $usuario->id, 'nombre' => 'Graduado de Prueba', 'ci' => '1234567', 'registro' => '210000001', 'anio_graduacion' => 2023,
            'modalidad_titulacion' => 'proyecto_grado', 'situacion_laboral' => 'dependiente', 'empresa' => 'Empresa de ejemplo', 'cargo' => 'Analista administrativo',
            'ciudad' => 'Santa Cruz de la Sierra', 'correo' => 'graduado@carrera.test', 'telefono' => '70000001',
        ]);

        for ($i = 0; $i < 24; $i++) {
            $situacion = $faker->randomElement($situaciones);
            Graduado::query()->create([
                'nombre' => $faker->firstName().' '.$faker->lastName().' '.$faker->lastName(),
                'ci' => (string) $faker->numberBetween(3000000, 9999999),
                'registro' => (string) $faker->numberBetween(200000000, 219999999),
                'anio_graduacion' => $faker->numberBetween(2015, 2026),
                'modalidad_titulacion' => $faker->randomElement($modalidades),
                'situacion_laboral' => $situacion,
                'empresa' => in_array($situacion, ['dependiente', 'independiente']) ? $faker->company() : null,
                'cargo' => in_array($situacion, ['dependiente', 'independiente']) ? $faker->randomElement(['Jefe de ventas', 'Analista financiero', 'Gerente de agencia', 'Encargado de RR.HH.', 'Emprendedor', 'Asistente administrativo']) : null,
                'ciudad' => $faker->randomElement(['Santa Cruz de la Sierra', 'Montero', 'Warnes', 'Cochabamba', 'La Paz']),
                'correo' => $faker->unique()->safeEmail(),
                'telefono' => '7'.$faker->numerify('#######'),
            ]);
        }
    }
}

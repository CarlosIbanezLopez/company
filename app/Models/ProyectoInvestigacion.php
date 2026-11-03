<?php

namespace App\Models;

use App\Models\Concerns\RegistraBitacora;
use App\Models\Concerns\TieneArchivos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProyectoInvestigacion extends Model
{
    use RegistraBitacora, TieneArchivos;

    protected $table = 'proyectos_investigacion';

    protected string $moduloBitacora = 'Investigación';

    protected $fillable = [
        'linea_investigacion_id',
        'tipo',
        'titulo',
        'slug',
        'descripcion',
        'responsables',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'resultados',
        'enlace_publicacion',
        'enlace_repositorio',
        'imagen',
        'publicado',
    ];

    /** Campos que guardan rutas de archivos en el disco público. */
    protected array $archivos = ['imagen'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
            'publicado' => 'boolean',
        ];
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(LineaInvestigacion::class, 'linea_investigacion_id');
    }
}

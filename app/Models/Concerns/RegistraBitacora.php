<?php

namespace App\Models\Concerns;

use App\Models\Bitacora;

/**
 * Registra automáticamente en la bitácora las acciones de creación,
 * edición, publicación, archivado y eliminación hechas por usuarios autenticados (RF-19).
 */
trait RegistraBitacora
{
    public static function bootRegistraBitacora(): void
    {
        static::created(fn ($modelo) => Bitacora::registrar('crear', $modelo->moduloBitacora(), 'Creó: '.$modelo->etiquetaBitacora(), $modelo->getKey()));

        static::updated(function ($modelo) {
            $accion = 'editar';

            if ($modelo->wasChanged('publicado') && $modelo->publicado) {
                $accion = 'publicar';
            } elseif ($modelo->wasChanged('estado') && $modelo->estado === 'publicado') {
                $accion = 'publicar';
            } elseif ($modelo->wasChanged('estado') && $modelo->estado === 'archivado') {
                $accion = 'archivar';
            }

            $campos = array_diff(array_keys($modelo->getChanges()), ['updated_at', 'password', 'remember_token']);

            Bitacora::registrar(
                $accion,
                $modelo->moduloBitacora(),
                ucfirst($accion).': '.$modelo->etiquetaBitacora().($campos ? ' (campos: '.implode(', ', $campos).')' : ''),
                $modelo->getKey(),
            );
        });

        static::deleted(fn ($modelo) => Bitacora::registrar('eliminar', $modelo->moduloBitacora(), 'Eliminó: '.$modelo->etiquetaBitacora(), $modelo->getKey()));
    }

    public function moduloBitacora(): string
    {
        return property_exists($this, 'moduloBitacora') ? $this->moduloBitacora : class_basename($this);
    }

    public function etiquetaBitacora(): string
    {
        foreach (['titulo', 'nombre', 'name', 'institucion', 'pregunta', 'etiqueta', 'empresa', 'sigla'] as $campo) {
            if (! empty($this->getAttribute($campo))) {
                return (string) $this->getAttribute($campo).' (#'.$this->getKey().')';
            }
        }

        return class_basename($this).' #'.$this->getKey();
    }
}

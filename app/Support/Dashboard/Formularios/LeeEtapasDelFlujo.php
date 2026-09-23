<?php

namespace App\Support\Dashboard\Formularios;

use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use Illuminate\Support\Collection;

/**
 * Etapas del flujo configurado para un formulario, leídas de Configuración →
 * Flujos. Así el detalle del panel muestra las etapas reales de cada
 * formulario sin que nadie las escriba en código.
 */
trait LeeEtapasDelFlujo
{
    public function etapasConfiguradas(): Collection
    {
        $flujo = FlujoAprobacion::query()
            ->where('codigo_formulario', $this->codigo())
            ->where('activo', true)
            ->orderBy('id')
            ->first();

        if (! $flujo) {
            return collect();
        }

        return $flujo->etapas()
            ->with('rolRevisor')
            ->where('activo', true)
            ->orderBy('orden')
            ->get()
            ->map(fn (FlujoAprobacionEtapa $etapa): array => [
                'id' => (int) $etapa->id,
                'nombre' => (string) $etapa->nombre,
                'orden' => (int) $etapa->orden,
                'rol' => $etapa->rolRevisor?->name,
                'procesos' => array_keys(array_filter([
                    FormularioPanel::INSCRIPCION => (bool) $etapa->aplica_inscripcion,
                    FormularioPanel::INFORME_INTERMEDIO => (bool) $etapa->aplica_informe_intermedio,
                    FormularioPanel::CIERRE => (bool) $etapa->aplica_cierre_proyecto,
                ])),
            ])
            ->values();
    }
}

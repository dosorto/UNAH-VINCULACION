<?php

namespace App\Services\Proyecto;

use Illuminate\Support\Collection;

/** Reconcilia evidencia explícita; la posición de una etapa nunca prueba su aprobación. */
final class LegacyStageReconciler
{
    public function reconcile(Collection $stages, Collection $signatures, callable $matches): array
    {
        $evidence = $stages->mapWithKeys(fn ($stage) => [(int) $stage->id => collect()]);
        $conflicts = collect();
        foreach ($signatures as $signature) {
            if (! in_array($signature->estado_revision, ['Aprobado', 'Pendiente', 'Rechazado'], true)) {
                continue;
            }
            $candidates = $stages->filter(fn ($stage) => $matches($signature, $stage));
            if ($candidates->count() > 1) {
                $conflicts->push('La revisión legacy #'.$signature->id.' corresponde a varias etapas. Configure cargos y roles que permitan identificarla sin ambigüedad.');
            } elseif ($candidates->count() === 1) {
                $evidence->get((int) $candidates->first()->id)->push($signature);
            } elseif (in_array($signature->estado_revision, ['Pendiente', 'Rechazado'], true)) {
                $conflicts->push('La revisión legacy pendiente o rechazada #'.$signature->id.' no tiene una etapa equivalente en el flujo seleccionado.');
            }
        }
        $classification = $stages->mapWithKeys(function ($stage) use ($evidence): array {
            // Una solicitud posterior invalida una aprobación anterior del mismo cargo.
            $latest = $evidence->get((int) $stage->id)->sortByDesc('id')->first();

            return [(int) $stage->id => [
                'estado' => $latest?->estado_revision ?? 'Sin evidencia',
                'firma_legacy_id' => $latest?->id,
                'completada' => $latest?->estado_revision === 'Aprobado',
            ]];
        });

        return [
            'clasificacion' => $classification,
            'completadas' => $stages->filter(fn ($stage) => $classification->get((int) $stage->id)['completada'])->values(),
            'pendientes' => $stages->reject(fn ($stage) => $classification->get((int) $stage->id)['completada'])->values(),
            'bloqueos' => $conflicts->all(),
        ];
    }
}

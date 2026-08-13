<?php

namespace App\Support\Proyecto;

use Illuminate\Support\Collection;

final class ProyectoFlujoStepper
{
    public static function desdeFilas(Collection $filas): array
    {
        $flujoIniciado = $filas->contains(
            fn (array $fila): bool => ($fila['firma'] ?? null) !== null
                || (bool) ($fila['adoptada_antes'] ?? false)
        );

        if (! $flujoIniciado) {
            return [];
        }

        $actualMarcado = false;

        return $filas->map(function (array $fila) use (&$actualMarcado): array {
            $firma = $fila['firma'] ?? null;
            $estado = 'pendiente';

            if ($fila['adoptada_antes'] ?? false) {
                $estado = 'adoptado';
            } elseif ($firma?->estado_revision === 'Aprobado') {
                $estado = 'aprobado';
            } elseif ($firma?->estado_revision === 'Rechazado') {
                $estado = 'rechazado';
            } elseif ($firma?->estado_revision === 'Pendiente' && ! $actualMarcado) {
                $estado = 'actual';
                $actualMarcado = true;
            }

            return [
                'nombre' => $fila['etapa']->nombre,
                'estado' => $estado,
            ];
        })->all();
    }
}

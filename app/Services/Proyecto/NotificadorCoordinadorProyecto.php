<?php

namespace App\Services\Proyecto;

use App\Mail\ProyectoEstadoCambiado;
use App\Models\Proyecto\Proyecto;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Avisa al coordinador del proyecto de cada movimiento de su expediente
 * (inscripción o informe) en el flujo por etapas.
 *
 * El correo sale después del commit y un fallo al encolarlo nunca deshace la
 * acción que lo originó.
 */
class NotificadorCoordinadorProyecto
{
    public function notificar(
        Proyecto $proyecto,
        string $nuevoEstado,
        string $comentario,
        string $accion,
        ?string $actionUrl = null,
        ?string $tipoDocumento = null,
    ): void {
        $coordinador = $proyecto->coordinador_proyecto()->first()?->empleado?->user;

        if (! $coordinador || blank($coordinador->email)) {
            return;
        }

        try {
            Mail::to($coordinador->email)->queue(
                (new ProyectoEstadoCambiado($proyecto, $coordinador, $nuevoEstado, $comentario, $accion, $actionUrl, $tipoDocumento))->afterCommit()
            );
        } catch (\Throwable $exception) {
            Log::warning('No se pudo notificar al coordinador del proyecto: '.$exception->getMessage(), [
                'proyecto_id' => $proyecto->id,
            ]);
        }
    }
}

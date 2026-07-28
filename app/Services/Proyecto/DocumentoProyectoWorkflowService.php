<?php

namespace App\Services\Proyecto;

use App\Mail\EtapaFlujoPendiente;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class DocumentoProyectoWorkflowService
{
    public function iniciar(
        Proyecto $proyecto,
        string $tipoDocumento,
        string $path,
        User $emisor,
        array $usuariosElegidosPorEtapa = []
    ): DocumentoProyecto {
        $empleado = $emisor->empleado;

        if (! $empleado || $empleado->trashed()) {
            throw new \RuntimeException('El usuario emisor no tiene un empleado activo asociado.');
        }

        $documento = $proyecto->registrarDocumentoDesdeFlujo(
            $tipoDocumento,
            $path,
            $empleado,
            $usuariosElegidosPorEtapa
        );

        $this->notificarFirmaActual($proyecto, $documento);

        return $documento;
    }

    public function reenviarDesdeSubsanacion(
        Proyecto $proyecto,
        DocumentoProyecto $documento,
        string $path,
        User $emisor
    ): DocumentoProyecto {
        $empleado = $emisor->empleado;

        if (! $empleado || $empleado->trashed()) {
            throw new \RuntimeException('El usuario emisor no tiene un empleado activo asociado.');
        }

        $ultimoCiclo = (int) $documento->firma_documento()
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->max('revision_ciclo');
        $firmas = $documento->firma_documento()
            ->where('revision_ciclo', $ultimoCiclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->orderBy('orden_revision')
            ->get();
        $rechazadas = $firmas->where('estado_revision', 'Rechazado');

        if ($rechazadas->count() !== 1) {
            throw new \RuntimeException('No se pudo identificar una única etapa en subsanación para reenviar el informe.');
        }

        $firmaRechazada = $rechazadas->first();
        $empleadosPorEtapa = $firmas
            ->filter(fn (FirmaProyecto $firma): bool => $firma->estado_revision !== 'Anulado'
                && (int) $firma->orden_revision >= (int) $firmaRechazada->orden_revision)
            ->mapWithKeys(fn (FirmaProyecto $firma): array => [
                (int) $firma->flujo_aprobacion_etapa_id => (int) $firma->empleado_id,
            ])
            ->all();
        $firmasNuevas = $proyecto->crearNuevoCicloDesdeFirmaRechazada($firmaRechazada, $empleadosPorEtapa);
        $primera = $firmasNuevas->first();
        $tipoEstadoId = $primera?->cargo_firma()->value('tipo_estado_id');

        if (! $primera || ! $tipoEstadoId) {
            throw new \RuntimeException('No se pudo reanudar el flujo desde la etapa que solicitó la subsanación.');
        }

        $documento->update(['documento_url' => $path]);
        $documento->estado_documento()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'comentario' => sprintf(
                '%s reenviado a la etapa "%s" después de subsanación.',
                $documento->tipo_documento,
                $primera->etapa_nombre
            ),
        ]);

        $this->notificarFirmaActual($proyecto, $documento);

        return $documento->fresh();
    }

    public function notificarFirmaActual(Proyecto $proyecto, DocumentoProyecto $documento): void
    {
        $flujoId = $proyecto->resolveFlujoAprobacion()?->id;
        $ciclo = $flujoId
            ? (int) $documento->firma_documento()->where('flujo_aprobacion_id', $flujoId)->max('revision_ciclo')
            : 0;
        $firma = $flujoId && $ciclo > 0
            ? $proyecto->firmaActualDeEtapasDelFlujo($flujoId, $ciclo, $documento)
            : null;
        $usuario = $firma?->responsableUsuario ?: $firma?->empleado?->user;
        $etapa = $firma?->flujoEtapa;

        if ($usuario?->email && $etapa) {
            Mail::to($usuario->email)->send(
                new EtapaFlujoPendiente($proyecto, $usuario, $etapa, $documento->tipo_documento)
            );
        }
    }
}

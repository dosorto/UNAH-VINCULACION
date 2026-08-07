<?php

namespace App\Services\Proyecto;

use App\Mail\EtapaFlujoPendiente;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use App\Services\Workflow\WorkflowResumptionPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        User $emisor,
        array $usuariosReemplazo = []
    ): DocumentoProyecto {
        return DB::transaction(function () use ($proyecto, $documento, $path, $emisor, $usuariosReemplazo): DocumentoProyecto {
            Proyecto::query()->whereKey($proyecto->id)->lockForUpdate()->firstOrFail();
            $documento = DocumentoProyecto::query()->whereKey($documento->id)->lockForUpdate()->firstOrFail();

            return $this->procesarReenvioDesdeSubsanacion($proyecto->fresh(), $documento, $path, $emisor, $usuariosReemplazo);
        });
    }

    private function procesarReenvioDesdeSubsanacion(
        Proyecto $proyecto,
        DocumentoProyecto $documento,
        string $path,
        User $emisor,
        array $usuariosReemplazo
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
            ->mapWithKeys(function (FirmaProyecto $firma) use ($usuariosReemplazo): array {
                $etapaId = (int) $firma->flujo_aprobacion_etapa_id;
                $firma->loadMissing('empleado.user');
                $usuario = app(WorkflowResumptionPolicy::class)->eligibleRecipient(
                    $firma->empleado?->user,
                    $firma->rol_requerido,
                    true
                );

                if (! $usuario) {
                    $reemplazoId = $usuariosReemplazo[$etapaId] ?? null;
                    $reemplazo = $reemplazoId ? User::with('empleado')->find((int) $reemplazoId) : null;
                    $usuario = app(WorkflowResumptionPolicy::class)->eligibleRecipient(
                        $reemplazo,
                        $firma->rol_requerido,
                        true
                    );
                }

                if (! $usuario) {
                    throw new \RuntimeException(sprintf(
                        'El revisor anterior de la etapa "%s" ya no es elegible; seleccione un reemplazo válido.',
                        $firma->etapa_nombre ?: $firma->etapa_codigo
                    ));
                }

                return [$etapaId => (int) $usuario->empleado->id];
            })
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

        Log::info('Documento de proyecto reenviado desde subsanación', [
            'proceso' => Proyecto::procesoFlujoParaDocumento($documento->tipo_documento),
            'registro_id' => $documento->id,
            'flujo_id' => $firmaRechazada->flujo_aprobacion_id,
            'ciclo_anterior' => $firmaRechazada->revision_ciclo,
            'ciclo_nuevo' => $primera->revision_ciclo,
            'etapa_retorno_id' => $primera->flujo_aprobacion_etapa_id,
            'revisor_usuario_id' => $primera->responsable_usuario_id ?: $primera->empleado?->user_id,
        ]);

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

        if (! $usuario || blank($usuario->email) || ! filter_var($usuario->email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('La etapa actual no tiene un revisor asignado con correo válido.');
        }

        if (! $etapa) {
            throw new \RuntimeException('La etapa histórica actual ya no existe y no puede notificarse.');
        }

        Mail::to($usuario->email)->queue(
            (new EtapaFlujoPendiente($proyecto, $usuario, $etapa, $documento->tipo_documento))->afterCommit()
        );
    }
}

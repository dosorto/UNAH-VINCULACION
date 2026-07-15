<?php

namespace App\Concerns;

use App\Http\Controllers\Docente\VerificarConstancia;
use App\Mail\EtapaFlujoPendiente;
use App\Mail\ProyectoEstadoCambiado;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Motor de aprobar/rechazar una FirmaProyecto que usa flujo por etapas
 * (Proyecto o DocumentoProyecto), agnóstico de a cuál de los dos pertenece.
 *
 * Requiere que la clase que lo usa también use ResolvesFirmasPendientes
 * (provee canActOnWorkflowStageFirma, proyectoDeFirmaPorEtapa, documentoDeFirmaPorEtapa).
 */
trait ResuelveFirmaPorEtapa
{
    protected function aprobarFirmaPorEtapa(FirmaProyecto $firma, User $user): FirmaProyecto
    {
        return DB::transaction(function () use ($firma, $user): FirmaProyecto {
            $firmaBloqueada = FirmaProyecto::query()
                ->whereKey($firma->id)
                ->lockForUpdate()
                ->first();

            if (! $firmaBloqueada || ! $this->canActOnWorkflowStageFirma($firmaBloqueada, $user)) {
                throw new \RuntimeException('La firma ya no se encuentra disponible para aprobación.');
            }

            $proyecto = $this->proyectoDeFirmaPorEtapa($firmaBloqueada);

            if (! $proyecto) {
                throw new \RuntimeException('La firma no pertenece a un proyecto válido.');
            }

            $documento = $this->documentoDeFirmaPorEtapa($firmaBloqueada);
            $empleado = $user->empleado()->with(['firma', 'sello'])->first();

            if (! $empleado) {
                throw new \RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            $firmaBloqueada->update([
                'estado_revision' => 'Aprobado',
                'firma_id' => $empleado->firma?->id,
                'sello_id' => $empleado->sello?->id,
                'fecha_firma' => now(),
            ]);

            $firmaAprobada = $firmaBloqueada->fresh();

            $proyecto->anularFirmasPendientesDuplicadasDeEtapa(
                (int) $firmaAprobada->flujo_aprobacion_etapa_id,
                (int) $firmaAprobada->revision_ciclo,
                $firmaAprobada->id,
                $documento
            );

            $siguienteFirma = $proyecto->siguienteFirmaDeEtapa($firmaAprobada);

            if ($siguienteFirma) {
                $this->registrarEstadoSiguienteDeFirmaPorEtapa($firmaAprobada, $siguienteFirma, $user);
            } else {
                $this->finalizarFlujoDeFirmaPorEtapa($firmaAprobada, $user);
            }

            return $firmaAprobada->fresh();
        });
    }

    protected function registrarEstadoSiguienteDeFirmaPorEtapa(
        FirmaProyecto $firmaAprobada,
        FirmaProyecto $siguienteFirma,
        User $user
    ): void {
        $proyecto = $this->proyectoDeFirmaPorEtapa($firmaAprobada);
        $documento = $this->documentoDeFirmaPorEtapa($firmaAprobada);

        if (! $proyecto || $siguienteFirma->estado_revision !== 'Pendiente' || ! $proyecto->firmaEsActualEnFlujoPorEtapa($siguienteFirma)) {
            throw new \RuntimeException('No se pudo determinar de forma segura la siguiente etapa del flujo.');
        }

        $tipoEstadoId = $siguienteFirma->cargo_firma()->value('tipo_estado_id');

        if (! $tipoEstadoId) {
            throw new \RuntimeException('No se pudo determinar de forma segura la siguiente etapa del flujo.');
        }

        $empleadoId = $user->empleado?->id;

        if (! $empleadoId) {
            throw new \RuntimeException('El usuario no tiene un empleado activo asociado.');
        }

        $payload = [
            'empleado_id' => $empleadoId,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'comentario' => $documento
                ? 'Firma aprobada y documento avanzado a la siguiente etapa del flujo.'
                : 'Firma aprobada y proyecto avanzado a la siguiente etapa del flujo.',
        ];

        if ($documento) {
            $documento->estado_documento()->create($payload);
        } else {
            $proyecto->estado_proyecto()->create($payload);
        }

        $this->notificarSiguienteFirmaPorEtapa($proyecto, $siguienteFirma);
        $this->notificarCoordinadorProyecto(
            $proyecto,
            sprintf('Etapa "%s" aprobada', $firmaAprobada->etapa_nombre ?: $firmaAprobada->etapa_codigo),
            sprintf(
                'La etapa "%s" fue aprobada y %s avanzó a la etapa "%s".',
                $firmaAprobada->etapa_nombre ?: $firmaAprobada->etapa_codigo,
                $documento ? 'el documento' : 'su proyecto',
                $siguienteFirma->etapa_nombre ?: $siguienteFirma->etapa_codigo
            ),
            'avance de etapa'
        );
    }

    protected function finalizarFlujoDeFirmaPorEtapa(FirmaProyecto $firmaAprobada, User $user): void
    {
        $proyecto = $this->proyectoDeFirmaPorEtapa($firmaAprobada);
        $documento = $this->documentoDeFirmaPorEtapa($firmaAprobada);

        if (! $proyecto || ! $firmaAprobada->flujo_aprobacion_id || ! $firmaAprobada->revision_ciclo) {
            throw new \RuntimeException('El recorrido de firmas no está completo o contiene una etapa bloqueada.');
        }

        if (! $proyecto->firmasDeEtapasCompletadas(
            (int) $firmaAprobada->flujo_aprobacion_id,
            (int) $firmaAprobada->revision_ciclo,
            $documento
        )) {
            throw new \RuntimeException('El recorrido de firmas no está completo o contiene una etapa bloqueada.');
        }

        if ($documento) {
            $proceso = Proyecto::procesoFlujoParaDocumento($documento->tipo_documento);

            if (! $proceso) {
                throw new \RuntimeException('No se puede determinar el proceso del documento.');
            }

            $this->marcarDocumentoAprobado($documento);

            $this->notificarCoordinadorProyecto(
                $proyecto,
                sprintf('%s aprobado', $documento->tipo_documento),
                sprintf('Su %s completó todas las etapas de revisión y fue aprobado.', $documento->tipo_documento),
                'aprobación de informe'
            );

            return;
        }

        $estadoFinalId = $proyecto->estadoFinalProcesoId(Proyecto::FLUJO_INSCRIPCION);

        if (! $estadoFinalId) {
            throw new \RuntimeException('No existe un estado final configurado para el flujo de inscripción.');
        }

        $empleadoId = $user->empleado?->id;

        if (! $empleadoId) {
            throw new \RuntimeException('El usuario no tiene un empleado activo asociado.');
        }

        $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleadoId,
            'tipo_estado_id' => $estadoFinalId,
            'fecha' => now(),
            'comentario' => 'Todas las etapas del flujo de inscripción fueron aprobadas.',
        ]);

        $this->notificarCoordinadorProyecto(
            $proyecto,
            'Flujo de inscripción aprobado',
            'Su proyecto completó todas las etapas de firma del flujo de inscripción.',
            'aprobación de proyecto'
        );
    }

    protected function rechazarFirmaPorEtapa(FirmaProyecto $firma, User $user, string $comentario): FirmaProyecto
    {
        $comentario = trim($comentario);

        if ($comentario === '') {
            throw new \RuntimeException('Debe indicar el motivo de la subsanación.');
        }

        return DB::transaction(function () use ($firma, $user, $comentario): FirmaProyecto {
            $firmaBloqueada = FirmaProyecto::query()
                ->whereKey($firma->id)
                ->lockForUpdate()
                ->first();

            if (! $firmaBloqueada || ! $this->canActOnWorkflowStageFirma($firmaBloqueada, $user)) {
                throw new \RuntimeException('La firma ya no se encuentra disponible para rechazo.');
            }

            $proyecto = $this->proyectoDeFirmaPorEtapa($firmaBloqueada);

            if (! $proyecto) {
                throw new \RuntimeException('La firma no pertenece a un proyecto válido.');
            }

            $documento = $this->documentoDeFirmaPorEtapa($firmaBloqueada);
            $empleadoId = $user->empleado?->id;

            if (! $empleadoId) {
                throw new \RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            $firmaBloqueada->update([
                'estado_revision' => 'Rechazado',
                'firma_id' => null,
                'sello_id' => null,
                'fecha_firma' => now(),
            ]);

            $firmaRechazada = $firmaBloqueada->fresh();

            $proyecto->anularFirmasPendientesDuplicadasDeEtapa(
                (int) $firmaRechazada->flujo_aprobacion_etapa_id,
                (int) $firmaRechazada->revision_ciclo,
                $firmaRechazada->id,
                $documento
            );

            $this->registrarSubsanacionPorRechazoDeEtapa($proyecto, $documento, $empleadoId, $comentario);
            $this->verificarRecorridoBloqueadoPorRechazo($firmaRechazada->fresh(), $proyecto, $documento);

            return $firmaRechazada->fresh();
        });
    }

    protected function verificarRecorridoBloqueadoPorRechazo(
        FirmaProyecto $firmaRechazada,
        Proyecto $proyecto,
        ?DocumentoProyecto $documento = null
    ): void {
        $firmaRechazada = $firmaRechazada->fresh();

        if ($firmaRechazada->estado_revision !== 'Rechazado') {
            throw new \RuntimeException('No se pudo bloquear de forma segura el recorrido después del rechazo.');
        }

        if ($proyecto->firmaActualDeEtapasDelFlujo(
            (int) $firmaRechazada->flujo_aprobacion_id,
            (int) $firmaRechazada->revision_ciclo,
            $documento
        )) {
            throw new \RuntimeException('No se pudo bloquear de forma segura el recorrido después del rechazo.');
        }

        if ($proyecto->firmasDeEtapasCompletadas(
            (int) $firmaRechazada->flujo_aprobacion_id,
            (int) $firmaRechazada->revision_ciclo,
            $documento
        )) {
            throw new \RuntimeException('No se pudo bloquear de forma segura el recorrido después del rechazo.');
        }

        $firmasPosteriores = $proyecto->firmasDeEtapasDelFlujo(
            (int) $firmaRechazada->flujo_aprobacion_id,
            (int) $firmaRechazada->revision_ciclo,
            $documento
        )->filter(fn (FirmaProyecto $firma): bool => (int) $firma->orden_revision > (int) $firmaRechazada->orden_revision);

        foreach ($firmasPosteriores as $firmaPosterior) {
            if ($firmaPosterior->estado_revision !== 'Pendiente' || $proyecto->firmaEsActualEnFlujoPorEtapa($firmaPosterior)) {
                throw new \RuntimeException('No se pudo bloquear de forma segura el recorrido después del rechazo.');
            }
        }
    }

    protected function registrarSubsanacionPorRechazoDeEtapa(
        Proyecto $proyecto,
        ?DocumentoProyecto $documento,
        int $empleadoId,
        string $comentario
    ): void {
        $subsanacionId = TipoEstado::query()
            ->where('nombre', 'Subsanacion')
            ->value('id');

        if (! $subsanacionId) {
            throw new \RuntimeException('No existe un estado de subsanación configurado.');
        }

        $payload = [
            'empleado_id' => $empleadoId,
            'tipo_estado_id' => $subsanacionId,
            'fecha' => now(),
            'comentario' => $comentario,
        ];

        if ($documento) {
            $documento->estado_documento()->create($payload);
            return;
        }

        $proyecto->estado_proyecto()->create($payload);
    }

    protected function marcarDocumentoAprobado(DocumentoProyecto $documento): void
    {
        if ($documento->tipo_documento === 'Informe Final') {
            $proyecto = $documento->proyecto;

            $proyecto->estado_proyecto()->create([
                'empleado_id' => auth()->user()->empleado->id,
                'tipo_estado_id' => TipoEstado::where('nombre', 'Finalizado')->first()->id,
                'fecha' => now(),
                'comentario' => 'El informe ha sido aprobado correctamente',
            ]);

            VerificarConstancia::makeConstanciasProyecto($proyecto);
        }

        $documento->estado_documento()->create([
            'empleado_id' => auth()->user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Aprobado')->first()->id,
            'fecha' => now(),
            'comentario' => 'El informe ha sido aprobado correctamente',
        ]);
    }

    /**
     * Avisa al siguiente firmante que tiene una etapa pendiente por revisar.
     */
    private function notificarSiguienteFirmaPorEtapa(Proyecto $proyecto, FirmaProyecto $siguienteFirma): void
    {
        $revisorUser = null;

        if ($siguienteFirma->responsable_usuario_id) {
            $revisorUser = User::find($siguienteFirma->responsable_usuario_id);
        } elseif ($siguienteFirma->empleado_id) {
            $revisorUser = $siguienteFirma->empleado?->user;
        }

        if (! $revisorUser || ! $revisorUser->email) {
            return;
        }

        $etapa = FlujoAprobacionEtapa::find($siguienteFirma->flujo_aprobacion_etapa_id);

        if (! $etapa) {
            return;
        }

        try {
            Mail::to($revisorUser->email)->send(new EtapaFlujoPendiente($proyecto, $revisorUser, $etapa));
        } catch (\Throwable $exception) {
            Log::warning('No se pudo notificar al siguiente revisor de etapa: ' . $exception->getMessage(), [
                'firma_id' => $siguienteFirma->id,
                'proyecto_id' => $proyecto->id,
            ]);
        }
    }

    /**
     * Avisa al coordinador/docente dueño del proyecto que su registro avanzó.
     */
    private function notificarCoordinadorProyecto(
        Proyecto $proyecto,
        string $nuevoEstado,
        string $comentario,
        string $accion
    ): void {
        $coordinador = $proyecto->coordinador_proyecto()->first()?->empleado?->user;

        if (! $coordinador || ! $coordinador->email) {
            return;
        }

        try {
            Mail::to($coordinador->email)->send(
                new ProyectoEstadoCambiado($proyecto, $coordinador, $nuevoEstado, $comentario, $accion)
            );
        } catch (\Throwable $exception) {
            Log::warning('No se pudo notificar al coordinador del proyecto: ' . $exception->getMessage(), [
                'proyecto_id' => $proyecto->id,
            ]);
        }
    }
}

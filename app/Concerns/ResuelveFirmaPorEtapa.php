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
use App\Models\InformeFinal\InformeFinalProyecto;
use App\Models\InformeIntermedio\InformeIntermedioProyecto;
use App\Services\Constancias\EmitirConstanciaFinalizacionProyecto;
use App\Services\Constancias\EmitirConstanciaRegistroProyecto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Motor de aprobar/rechazar una FirmaProyecto que usa flujo por etapas
 * (Proyecto o DocumentoProyecto), agnóstico de a cuál de los dos pertenece.
 *
 * Requiere que la clase que lo usa también use ResolvesFirmasPendientes
 * (provee canActOnWorkflowStageFirma, proyectoDeFirmaPorEtapa, documentoDeFirmaPorEtapa).
 */
trait ResuelveFirmaPorEtapa
{
    protected function aprobarFirmaPorEtapa(FirmaProyecto $firma, User $user, ?\Closure $despuesDeAprobar = null, ?string $comentario = null): FirmaProyecto
    {
        return DB::transaction(function () use ($firma, $user, $despuesDeAprobar, $comentario): FirmaProyecto {
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

            if (! $empleado || $empleado->trashed()) {
                throw new \RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            if (! $firmaBloqueada->cargo_firma()->exists() || ! $firmaBloqueada->flujoEtapa()->exists()) {
                throw new \RuntimeException('La etapa no tiene un cargo de firma válido configurado.');
            }

            $firmaEmpleado = $empleado->firma;

            $firmaBloqueada->update([
                'estado_revision' => 'Aprobado',
                'empleado_id' => $empleado->id,
                'firma_id' => $firmaEmpleado?->id,
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
                $this->registrarEstadoSiguienteDeFirmaPorEtapa($firmaAprobada, $siguienteFirma, $user, $comentario);
            } else {
                $this->finalizarFlujoDeFirmaPorEtapa($firmaAprobada, $user, $comentario);
            }

            if ($despuesDeAprobar) {
                $despuesDeAprobar($firmaAprobada->fresh());
            }

            return $firmaAprobada->fresh();
        });
    }

    protected function registrarEstadoSiguienteDeFirmaPorEtapa(
        FirmaProyecto $firmaAprobada,
        FirmaProyecto $siguienteFirma,
        User $user,
        ?string $comentario = null
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
                ? ($documento->tipo_documento === 'Informe Final'
                    ? sprintf('[Cierre INF-001] Etapa "%s" aprobada; avanzó a "%s".', $firmaAprobada->etapa_nombre, $siguienteFirma->etapa_nombre)
                    : 'Firma aprobada y documento avanzado a la siguiente etapa del flujo.')
                : 'Firma aprobada y proyecto avanzado a la siguiente etapa del flujo.',
        ];

        if (filled($comentario)) {
            $payload['comentario'] .= ' '.$comentario;
        }

        if ($documento) {
            $documento->estado_documento()->create($payload);

            if ($documento->tipo_documento === 'Informe Intermedio') {
                InformeIntermedioProyecto::query()
                    ->where('documento_proyecto_id', $documento->id)
                    ->update([
                        'estado' => InformeIntermedioProyecto::ESTADO_EN_REVISION,
                        'etapa_actual_id' => $siguienteFirma->flujo_aprobacion_etapa_id,
                        'etapa_retorno_id' => null,
                    ]);
            }
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

    protected function finalizarFlujoDeFirmaPorEtapa(FirmaProyecto $firmaAprobada, User $user, ?string $comentario = null): void
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

            $informeIntermedioFueAprobado = $this->marcarDocumentoAprobado($documento, $user, $comentario);

            if ($documento->tipo_documento === 'Informe Intermedio' && $informeIntermedioFueAprobado) {
                $this->notificarCoordinadorProyecto(
                    $proyecto,
                    'Informe intermedio aprobado',
                    sprintf(
                        'El informe intermedio del proyecto "%s" fue aprobado. Ya puede completar y enviar el Informe Final.',
                        $proyecto->nombre_proyecto
                    ),
                    'aprobación de informe',
                    route('proyectos.informe-final', $proyecto)
                );
            } elseif ($documento->tipo_documento === 'Informe Final') {
                $this->notificarCoordinadorProyecto(
                    $proyecto,
                    'Informe Final aprobado',
                    'El Informe Final INF-001 fue aprobado y el proyecto fue cerrado.',
                    'aprobación de informe'
                );
            }

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
            'comentario' => filled($comentario)
                ? $comentario
                : 'Todas las etapas del flujo de inscripción fueron aprobadas.',
        ]);

        DB::afterCommit(function () use ($proyecto, $user): void {
            if (! Schema::hasTable('constancias_registro_proyecto')) {
                Log::warning('La constancia de registro no se emitió porque la migración aún no está disponible.', ['proyecto_id' => $proyecto->id]);
                return;
            }
            try {
                app(EmitirConstanciaRegistroProyecto::class)->emitir($proyecto, $user->id);
            } catch (\Throwable $exception) {
                Log::error('No se pudo emitir la constancia de registro después de completar la inscripción.', [
                    'proyecto_id' => $proyecto->id,
                    'exception' => $exception,
                ]);
            }
        });

        $this->notificarCoordinadorProyecto(
            $proyecto,
            'Flujo de inscripción aprobado',
            'Su proyecto completó todas las etapas de Inscripción. El formulario INF-001'
                .($proyecto->tieneFlujoCierreProyecto() ? ' ya está disponible.' : ' no tiene etapas de cierre configuradas.')
                .($proyecto->tieneFlujoInformeIntermedio() ? ' El Informe Intermedio también está disponible para cargar y enviar.' : ''),
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
            $this->notificarCoordinadorProyecto(
                $proyecto,
                $documento ? $documento->tipo_documento.' en subsanación' : 'Proyecto en subsanación',
                $comentario,
                'solicitud de subsanación'
            );

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
            'comentario' => $documento?->tipo_documento === 'Informe Final'
                ? '[Cierre INF-001] Rechazado para subsanación: '.$comentario
                : $comentario,
        ];

        if ($documento) {
            $documento->estado_documento()->create($payload);

            if ($documento->tipo_documento === 'Informe Intermedio') {
                InformeIntermedioProyecto::query()
                    ->where('documento_proyecto_id', $documento->id)
                    ->update([
                        'estado' => InformeIntermedioProyecto::ESTADO_SUBSANACION,
                        'etapa_actual_id' => null,
                        'etapa_retorno_id' => $documento->firma_documento()
                            ->where('estado_revision', 'Rechazado')
                            ->latest('id')
                            ->value('flujo_aprobacion_etapa_id'),
                        'observaciones' => $comentario,
                    ]);
            }
            return;
        }

        $proyecto->estado_proyecto()->create($payload);
    }

    protected function marcarDocumentoAprobado(DocumentoProyecto $documento, User $user, ?string $comentario = null): bool
    {
        $empleadoId = $user->empleado?->id;
        $aprobadoId = TipoEstado::where('nombre', 'Aprobado')->value('id');

        if (! $empleadoId || ! $aprobadoId) {
            throw new \RuntimeException('No se pudo registrar la aprobación final del documento.');
        }

        if ($documento->tipo_documento === 'Informe Final') {
            $proyecto = $documento->proyecto;
            $finalizadoId = TipoEstado::where('nombre', 'Finalizado')->value('id');

            if (! $finalizadoId) {
                throw new \RuntimeException('No existe el estado Finalizado requerido para cerrar el proyecto.');
            }
            $proyecto->estado_proyecto()->create([
                'empleado_id' => $empleadoId,
                'tipo_estado_id' => $finalizadoId,
                'fecha' => now(),
                'comentario' => filled($comentario)
                    ? '[Cierre INF-001] Informe final aprobado; proyecto finalizado. '.$comentario
                    : '[Cierre INF-001] Informe final aprobado; proyecto finalizado.',
            ]);
            InformeFinalProyecto::query()
                ->where('proyecto_id', $proyecto->id)
                ->update(['fecha_cierre' => now()->toDateString()]);

            VerificarConstancia::makeConstanciasProyecto($proyecto);
        }

        $informeIntermedioFueAprobado = false;

        if ($documento->tipo_documento === 'Informe Intermedio') {
            $informeIntermedioFueAprobado = InformeIntermedioProyecto::query()
                ->where('documento_proyecto_id', $documento->id)
                ->where('estado', '!=', InformeIntermedioProyecto::ESTADO_APROBADO)
                ->update([
                    'estado' => InformeIntermedioProyecto::ESTADO_APROBADO,
                    'etapa_actual_id' => null,
                    'etapa_retorno_id' => null,
                    'aprobado_por' => $user->id,
                    'fecha_aprobacion' => now(),
                    'observaciones' => null,
                ]);
        }

        $documento->estado_documento()->create([
            'empleado_id' => $empleadoId,
            'tipo_estado_id' => $aprobadoId,
            'fecha' => now(),
            'comentario' => $documento->tipo_documento === 'Informe Final'
                ? (filled($comentario) ? '[Cierre INF-001] Todas las etapas de cierre fueron aprobadas. '.$comentario : '[Cierre INF-001] Todas las etapas de cierre fueron aprobadas.')
                : (filled($comentario) ? 'El informe ha sido aprobado correctamente. '.$comentario : 'El informe ha sido aprobado correctamente'),
        ]);

        if ($documento->tipo_documento === 'Informe Final') {
            $proyecto = $documento->proyecto;
            $informe = InformeFinalProyecto::query()->where('proyecto_id', $proyecto->id)->first();

            if ($informe) {
                // La constancia nueva es independiente del mecanismo legacy y se emite solo después del commit del cierre.
                DB::afterCommit(function () use ($proyecto, $informe, $documento, $user): void {
                    if (! Schema::hasTable('constancias_finalizacion_proyecto')) {
                        Log::warning('La constancia de finalización no se emitió porque la migración aún no está disponible.', ['proyecto_id' => $proyecto->id]);

                        return;
                    }

                    try {
                        app(EmitirConstanciaFinalizacionProyecto::class)->emitir($proyecto, $informe, $documento, $user->id);
                    } catch (\Throwable $exception) {
                        Log::error('No se pudo emitir la constancia de finalización después del cierre.', [
                            'proyecto_id' => $proyecto->id,
                            'informe_final_proyecto_id' => $informe->id,
                            'exception' => $exception,
                        ]);
                    }
                });
            }
        }

        return (bool) $informeIntermedioFueAprobado;
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
            $tipoRegistro = $siguienteFirma->firmable_type === DocumentoProyecto::class
                ? DocumentoProyecto::find($siguienteFirma->firmable_id)?->tipo_documento
                : null;
            Mail::to($revisorUser->email)->send(
                new EtapaFlujoPendiente($proyecto, $revisorUser, $etapa, $tipoRegistro)
            );
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
        string $accion,
        ?string $actionUrl = null,
    ): void {
        $coordinador = $proyecto->coordinador_proyecto()->first()?->empleado?->user;

        if (! $coordinador || ! $coordinador->email) {
            return;
        }

        try {
            Mail::to($coordinador->email)->send(
                new ProyectoEstadoCambiado($proyecto, $coordinador, $nuevoEstado, $comentario, $accion, $actionUrl)
            );
        } catch (\Throwable $exception) {
            Log::warning('No se pudo notificar al coordinador del proyecto: ' . $exception->getMessage(), [
                'proyecto_id' => $proyecto->id,
            ]);
        }
    }
}

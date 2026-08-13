<?php

namespace App\Concerns;

use App\Mail\EtapaFlujoPendiente;
use App\Models\Estado\EstadoProyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use App\Services\Workflow\WorkflowResumptionPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Reenvía un proyecto (o documento) devuelto a "Subsanacion" al mismo firmante
 * pendiente del ciclo anterior, sin volver a pedir destinatario.
 *
 * Compartido entre HistorialProyecto (revisor que rechaza) y
 * CreateProyectoVinculacion (docente que corrige y reenvía).
 */
trait ReenviaDesdeSubsanacionPorEtapa
{
    protected function firmaRechazadaActualPorEtapa(Proyecto $proyecto): ?FirmaProyecto
    {
        $firmasRechazadas = $proyecto->firma_proyecto()
            ->whereNull('deleted_at')
            ->where('estado_revision', 'Rechazado')
            ->whereNotNull('flujo_aprobacion_id')
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNotNull('revision_ciclo')
            ->whereNotNull('orden_revision')
            ->orderBy('orden_revision')
            ->orderBy('id')
            ->get()
            ->filter(function (FirmaProyecto $firma): bool {
                $ultimoCiclo = FirmaProyecto::query()
                    ->where('firmable_type', $firma->firmable_type)
                    ->where('firmable_id', $firma->firmable_id)
                    ->where('flujo_aprobacion_id', $firma->flujo_aprobacion_id)
                    ->whereNotNull('flujo_aprobacion_etapa_id')
                    ->whereNull('deleted_at')
                    ->max('revision_ciclo');

                return $firma->usaFlujoPorEtapa()
                    && $ultimoCiclo !== null
                    && (int) $firma->revision_ciclo === (int) $ultimoCiclo;
            })
            ->values();

        if ($firmasRechazadas->count() > 1) {
            throw new \RuntimeException('El último ciclo contiene más de una etapa rechazada y no puede reenviarse automáticamente.');
        }

        return $firmasRechazadas->first();
    }

    protected function empleadosPorEtapaParaReenvio(FirmaProyecto $firmaRechazada, array $usuariosReemplazo = []): array
    {
        $firmas = FirmaProyecto::query()
            ->with('empleado')
            ->where('firmable_type', $firmaRechazada->firmable_type)
            ->where('firmable_id', $firmaRechazada->firmable_id)
            ->where('flujo_aprobacion_id', $firmaRechazada->flujo_aprobacion_id)
            ->where('revision_ciclo', $firmaRechazada->revision_ciclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNotNull('orden_revision')
            ->whereNull('deleted_at')
            ->orderBy('orden_revision')
            ->orderBy('id')
            ->get()
            ->reject(fn (FirmaProyecto $firma): bool => $firma->estado_revision === 'Anulado')
            ->filter(fn (FirmaProyecto $firma): bool => (int) $firma->orden_revision >= (int) $firmaRechazada->orden_revision)
            ->values();

        $empleadosPorEtapa = [];

        foreach ($firmas as $firma) {
            $etapaId = (int) $firma->flujo_aprobacion_etapa_id;
            $nombreEtapa = $firma->etapa_nombre ?: $firma->flujoEtapa?->nombre ?: 'sin nombre';

            if (array_key_exists($etapaId, $empleadosPorEtapa)) {
                // Etapa enviada a todos los usuarios de un rol: puede haber
                // varias firmas Pendiente candidatas para la misma etapa; al
                // reanudar el ciclo se colapsa a la primera candidata elegible
                // ya encontrada, en vez de volver a fanear a todo el rol.
                continue;
            }

            $empleado = $firma->empleado;
            $usuario = $empleado?->user;
            $usuarioElegible = app(WorkflowResumptionPolicy::class)->eligibleRecipient(
                $usuario,
                $firma->rol_requerido,
                true
            );

            if (! $usuarioElegible) {
                $reemplazoId = $usuariosReemplazo[$etapaId] ?? null;
                $reemplazo = $reemplazoId ? User::with('empleado')->find((int) $reemplazoId) : null;
                $usuarioElegible = app(WorkflowResumptionPolicy::class)->eligibleRecipient(
                    $reemplazo,
                    $firma->rol_requerido,
                    true
                );
                $empleado = $usuarioElegible?->empleado;
            }

            if (! $empleado || $empleado->trashed() || ! $usuarioElegible) {
                throw new \RuntimeException(sprintf(
                    'El revisor anterior de la etapa "%s" ya no es elegible; seleccione un reemplazo válido.',
                    $nombreEtapa
                ));
            }

            $empleadosPorEtapa[$etapaId] = (int) $empleado->id;
        }

        return $empleadosPorEtapa;
    }

    protected function etapasQueRequierenReemplazoParaReenvio(FirmaProyecto $firmaRechazada): Collection
    {
        $firmas = FirmaProyecto::query()
            ->with('empleado.user')
            ->where('firmable_type', $firmaRechazada->firmable_type)
            ->where('firmable_id', $firmaRechazada->firmable_id)
            ->where('flujo_aprobacion_id', $firmaRechazada->flujo_aprobacion_id)
            ->where('revision_ciclo', $firmaRechazada->revision_ciclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->orderBy('orden_revision')
            ->get()
            ->reject(fn (FirmaProyecto $firma): bool => $firma->estado_revision === 'Anulado')
            ->filter(fn (FirmaProyecto $firma): bool => (int) $firma->orden_revision >= (int) $firmaRechazada->orden_revision)
            ->values();

        return $firmas
            ->filter(fn (FirmaProyecto $firma): bool => ! app(WorkflowResumptionPolicy::class)->eligibleRecipient(
                $firma->empleado?->user,
                $firma->rol_requerido,
                true
            ))
            // Etapas compartidas por rol pueden tener varias firmas Pendiente
            // candidatas: se listan una sola vez.
            ->unique(fn (FirmaProyecto $firma): int => (int) $firma->flujo_aprobacion_etapa_id)
            ->map(fn (FirmaProyecto $firma): array => [
                'id' => (int) $firma->flujo_aprobacion_etapa_id,
                'nombre' => $firma->etapa_nombre ?: $firma->etapa_codigo,
                'codigo' => $firma->etapa_codigo,
                'orden' => (int) $firma->orden_revision,
                'rol_nombre' => $firma->rol_requerido,
            ])
            ->values();
    }

    protected function reenviarDesdeSubsanacionPorEtapa(
        FirmaProyecto $firmaRechazada,
        User $user,
        array $empleadosPorEtapa
    ): Collection {
        return DB::transaction(function () use ($firmaRechazada, $user, $empleadosPorEtapa): Collection {
            $firmaBloqueada = FirmaProyecto::query()
                ->whereKey($firmaRechazada->id)
                ->lockForUpdate()
                ->first();

            if (! $firmaBloqueada) {
                throw new \RuntimeException('La firma indicada no corresponde a una etapa rechazada.');
            }

            [$proyecto, $documento] = $this->resolverFirmableParaReenvioPorEtapa($firmaBloqueada);

            Proyecto::query()->whereKey($proyecto->id)->lockForUpdate()->firstOrFail();

            if ($documento) {
                DocumentoProyecto::query()->whereKey($documento->id)->lockForUpdate()->firstOrFail();
            }

            $this->bloquearFirmasDelCicloRechazadoParaReenvio($proyecto, $firmaBloqueada, $documento);
            $firmaBloqueada = $firmaBloqueada->fresh();

            if ($firmaBloqueada->estado_revision !== 'Rechazado') {
                throw new \RuntimeException('La firma indicada no corresponde a una etapa rechazada.');
            }

            if (! $this->usuarioPuedeReenviarDesdeSubsanacion($proyecto, $user)) {
                throw new \RuntimeException('No tiene autorización para reenviar este registro desde subsanación.');
            }

            $this->validarRegistroEnSubsanacionParaReenvio($proyecto, $documento, $user);

            $firmasNuevoCiclo = $proyecto->crearNuevoCicloDesdeFirmaRechazada($firmaBloqueada, $empleadosPorEtapa);

            if ($firmasNuevoCiclo->isEmpty()) {
                throw new \RuntimeException('No se pudo crear el nuevo ciclo de revisión.');
            }

            $primeraFirma = $firmasNuevoCiclo->first()->fresh();
            $this->validarPrimeraFirmaDeReenvioPorEtapa($proyecto, $firmaBloqueada, $primeraFirma, $documento);

            $tipoEstadoId = $primeraFirma->cargo_firma()->value('tipo_estado_id');
            $empleadoId = $user->empleado?->id;

            if (! $tipoEstadoId || ! $empleadoId) {
                throw new \RuntimeException('No se pudo determinar de forma segura la primera etapa del nuevo ciclo.');
            }

            $this->registrarEstadoDeReenvioPorEtapa($proyecto, $documento, (int) $tipoEstadoId, (int) $empleadoId, $primeraFirma);
            $this->validarReenvioPorEtapaCompletado($proyecto, $documento, $firmaBloqueada, $primeraFirma);
            $this->notificarPrimeraEtapaReanudada($proyecto, $documento, $primeraFirma);

            Log::info('Registro de proyecto reenviado desde subsanación', [
                'proceso' => $documento
                    ? Proyecto::procesoFlujoParaDocumento($documento->tipo_documento)
                    : Proyecto::FLUJO_INSCRIPCION,
                'registro_id' => $documento?->id ?: $proyecto->id,
                'flujo_id' => $firmaBloqueada->flujo_aprobacion_id,
                'ciclo_anterior' => $firmaBloqueada->revision_ciclo,
                'ciclo_nuevo' => $primeraFirma->revision_ciclo,
                'etapa_retorno_id' => $primeraFirma->flujo_aprobacion_etapa_id,
                'revisor_usuario_id' => $primeraFirma->responsable_usuario_id ?: $primeraFirma->empleado?->user_id,
            ]);

            return $firmasNuevoCiclo->map(fn (FirmaProyecto $firma): FirmaProyecto => $firma->fresh())->values();
        });
    }

    private function notificarPrimeraEtapaReanudada(
        Proyecto $proyecto,
        ?DocumentoProyecto $documento,
        FirmaProyecto $primeraFirma
    ): void {
        $primeraFirma->loadMissing('empleado.user');
        $usuario = $primeraFirma->responsable_usuario_id
            ? User::find($primeraFirma->responsable_usuario_id)
            : $primeraFirma->empleado?->user;
        $etapa = FlujoAprobacionEtapa::find($primeraFirma->flujo_aprobacion_etapa_id);

        if (! $usuario || blank($usuario->email) || ! filter_var($usuario->email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('La etapa retomada no tiene un revisor asignado con correo válido.');
        }

        if (! $etapa) {
            throw new \RuntimeException('La etapa histórica retomada ya no existe y no puede notificarse.');
        }

        Mail::to($usuario->email)->queue(
            (new EtapaFlujoPendiente($proyecto, $usuario, $etapa, $documento?->tipo_documento))->afterCommit()
        );
    }

    protected function resolverFirmableParaReenvioPorEtapa(FirmaProyecto $firma): array
    {
        if ($firma->firmable_type === Proyecto::class) {
            $proyecto = Proyecto::query()->whereKey($firma->firmable_id)->first();

            if (! $proyecto || $proyecto->trashed()) {
                throw new \RuntimeException('La firma no pertenece al proyecto indicado.');
            }

            $this->validarProyectoEsperadoParaReenvio($proyecto);

            return [$proyecto, null];
        }

        if ($firma->firmable_type === DocumentoProyecto::class) {
            $documento = DocumentoProyecto::query()->whereKey($firma->firmable_id)->first();
            $proyecto = $documento?->proyecto()->first();

            if (! $documento || ! $proyecto || $proyecto->trashed()) {
                throw new \RuntimeException('La firma no pertenece al proyecto indicado.');
            }

            $this->validarProyectoEsperadoParaReenvio($proyecto);

            return [$proyecto, $documento];
        }

        throw new \RuntimeException('El tipo de registro no admite reenvío mediante flujo por etapa.');
    }

    /**
     * Id del proyecto sobre el que se espera operar, si el componente lo conoce
     * de antemano (p.ej. el proyecto cargado en el formulario). Null si no aplica.
     */
    protected function proyectoEsperadoIdParaReenvio(): ?int
    {
        return null;
    }

    protected function validarProyectoEsperadoParaReenvio(Proyecto $proyecto): void
    {
        $esperadoId = $this->proyectoEsperadoIdParaReenvio();

        if ($esperadoId !== null && (int) $esperadoId !== (int) $proyecto->id) {
            throw new \RuntimeException('La firma no pertenece al proyecto indicado.');
        }
    }

    protected function bloquearFirmasDelCicloRechazadoParaReenvio(
        Proyecto $proyecto,
        FirmaProyecto $firma,
        ?DocumentoProyecto $documento = null
    ): void {
        $relation = $documento
            ? $documento->firma_documento()
            : $proyecto->firma_proyecto();

        $relation
            ->where('flujo_aprobacion_id', $firma->flujo_aprobacion_id)
            ->where('revision_ciclo', $firma->revision_ciclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->get();
    }

    protected function usuarioPuedeReenviarDesdeSubsanacion(Proyecto $proyecto, User $user): bool
    {
        $empleado = $user->empleado;

        if (! $empleado || $empleado->trashed() || ! $user->can('docente.crear-proyecto')) {
            return false;
        }

        return $proyecto->coordinador_proyecto()
            ->where('empleado_id', $empleado->id)
            ->exists();
    }

    protected function validarRegistroEnSubsanacionParaReenvio(
        Proyecto $proyecto,
        ?DocumentoProyecto $documento = null,
        ?User $actor = null
    ): void {
        $estado = $documento
            ? $documento->estado
            : $proyecto->estado;

        if ($estado?->tipoestado?->nombre === 'Subsanacion') {
            if (! $documento && trim((string) $estado->comentario) === '') {
                throw new \RuntimeException(
                    'El estado indica Subsanación, pero no existe un motivo de rechazo en el historial.'
                );
            }

            return;
        }

        if (! $documento && $actor && $proyecto->puedeRepararSubsanacionDegradada()) {
            $proyecto->restaurarSubsanacionDegradada($actor);

            return;
        }

        throw new \RuntimeException('El registro no se encuentra en estado de Subsanación.');
    }

    protected function validarPrimeraFirmaDeReenvioPorEtapa(
        Proyecto $proyecto,
        FirmaProyecto $firmaRechazada,
        FirmaProyecto $primeraFirma,
        ?DocumentoProyecto $documento = null
    ): void {
        if ($primeraFirma->estado_revision !== 'Pendiente'
            || (int) $primeraFirma->revision_ciclo !== (int) $firmaRechazada->revision_ciclo + 1
            || (int) $primeraFirma->flujo_aprobacion_id !== (int) $firmaRechazada->flujo_aprobacion_id
            || $primeraFirma->firmable_type !== $firmaRechazada->firmable_type
            || (int) $primeraFirma->firmable_id !== (int) $firmaRechazada->firmable_id
            || ! $primeraFirma->cargo_firma_id
            || ! $primeraFirma->cargo_firma()->exists()
            || ! $primeraFirma->cargo_firma()->value('tipo_estado_id')
            || ! $proyecto->firmaEsActualEnFlujoPorEtapa($primeraFirma)
        ) {
            throw new \RuntimeException('No se pudo determinar de forma segura la primera etapa del nuevo ciclo.');
        }
    }

    protected function registrarEstadoDeReenvioPorEtapa(
        Proyecto $proyecto,
        ?DocumentoProyecto $documento,
        int $tipoEstadoId,
        int $empleadoId,
        FirmaProyecto $primeraFirma
    ): void {
        $relation = $documento
            ? $documento->estado_documento()
            : $proyecto->estado_proyecto();

        $relation->update(['es_actual' => false]);

        EstadoProyecto::withoutEvents(function () use ($relation, $tipoEstadoId, $empleadoId, $primeraFirma): void {
            $relation->create([
                'empleado_id' => $empleadoId,
                'tipo_estado_id' => $tipoEstadoId,
                'fecha' => now(),
                'comentario' => sprintf(
                    'Registro reenviado después de subsanación a la etapa "%s".',
                    $primeraFirma->etapa_nombre ?: $primeraFirma->etapa_codigo
                ),
                'es_actual' => true,
            ]);
        });
    }

    protected function validarReenvioPorEtapaCompletado(
        Proyecto $proyecto,
        ?DocumentoProyecto $documento,
        FirmaProyecto $firmaRechazada,
        FirmaProyecto $primeraFirma
    ): void {
        $estado = $documento
            ? $documento->estado
            : $proyecto->estado;

        $tipoEstadoId = $primeraFirma->cargo_firma()->value('tipo_estado_id');

        if ($primeraFirma->fresh()->estado_revision !== 'Pendiente'
            || ! $estado
            || (int) $estado->tipo_estado_id !== (int) $tipoEstadoId
            || ! $proyecto->firmaEsActualEnFlujoPorEtapa($primeraFirma->fresh())
            || $proyecto->firmasDeEtapasCompletadas((int) $firmaRechazada->flujo_aprobacion_id, (int) $primeraFirma->revision_ciclo, $documento)
        ) {
            throw new \RuntimeException('No se pudo determinar de forma segura la primera etapa del nuevo ciclo.');
        }

        $firmasPosteriores = $proyecto->firmasDeEtapasDelFlujo(
            (int) $firmaRechazada->flujo_aprobacion_id,
            (int) $primeraFirma->revision_ciclo,
            $documento
        )->filter(fn (FirmaProyecto $firma): bool => (int) $firma->id !== (int) $primeraFirma->id);

        foreach ($firmasPosteriores as $firmaPosterior) {
            if ($proyecto->firmaEsActualEnFlujoPorEtapa($firmaPosterior)) {
                throw new \RuntimeException('No se pudo determinar de forma segura la primera etapa del nuevo ciclo.');
            }
        }
    }
}

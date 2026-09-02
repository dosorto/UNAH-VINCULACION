<?php

namespace App\Services\PpsServicioSocial;

use App\Mail\EtapaFlujoPendiente;
use App\Models\Estado\TipoEstado;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use App\Services\Workflow\WorkflowResumptionPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Motor de aprobación de PPS/Servicio Social sobre las mismas tablas
 * polimórficas que usa Proyecto (firma_proyecto/estado_proyecto), vía el
 * trait App\Concerns\TieneFlujoPorEtapas que adopta PpsServicioSocial.
 * El atributo `estado` se obtiene mediante un accessor que lee desde
 * TipoEstado a través de la relación polimórfica EstadoProyecto.
 */
class PpsServicioSocialWorkflowService
{
    public function obtenerFlujoActivo(): ?FlujoAprobacion
    {
        return $this->flujoPpsQuery()
            ->where('activo', true)
            ->with(['etapas' => fn ($query) => $query->where('activo', true)->orderBy('orden')])
            ->orderBy('id')
            ->first();
    }

    public function tieneFlujoActivo(): bool
    {
        return $this->flujoPpsQuery()->where('activo', true)->exists();
    }

    public function enviarARevision(PpsServicioSocial $registro, ?int $userId, array $reemplazosHistoricos = []): PpsServicioSocial
    {
        return DB::transaction(function () use ($registro, $userId, $reemplazosHistoricos): PpsServicioSocial {
            $registro = PpsServicioSocial::query()->lockForUpdate()->findOrFail($registro->id);

            if ($registro->estado !== 'borrador') {
                throw new RuntimeException('Solo los registros en estado borrador pueden enviarse a revision.');
            }

            if (! $registro->perteneceAlUsuario($userId)) {
                throw new RuntimeException('Solo el usuario creador puede enviar este registro a revision.');
            }

            $esReenvio = $this->esReenvioDespuesDeRechazo($registro);

            if ($esReenvio) {
                try {
                    [$flujo, $ciclo, $firmasCreadas] = $this->crearCicloDeReanudacion($registro, $reemplazosHistoricos);
                } catch (\Throwable $e) {
                    Log::error('Error creando ciclo PPS/SS desde subsanacion', [
                        'registro_id' => $registro->id,
                        'estado' => $registro->estado,
                        'flujo_id' => $registro->flujo_aprobacion_id,
                        'etapa_actual_id' => $registro->etapa_actual_id,
                        'error' => $e->getMessage(),
                        'exception' => $e::class,
                    ]);

                    throw $e;
                }
            } else {
                $flujo = $this->resolverFlujoActivoParaEnvio($registro);
                $etapas = $registro->etapasActivasDelFlujo($flujo);

                if ($etapas->isEmpty()) {
                    throw new RuntimeException('El flujo PPS/SS activo no tiene etapas activas configuradas.');
                }

                if (! $registro->flujo_aprobacion_id) {
                    $registro->forceFill(['flujo_aprobacion_id' => $flujo->id])->saveQuietly();
                }

                $empleadosPorEtapa = $this->resolverEmpleadosPorEtapa($registro, $etapas);
                $ciclo = $this->obtenerCicloParaEnvio($registro);
                $firmasCreadas = $registro->sincronizarFirmasDeEtapasDelFlujo($empleadosPorEtapa, $flujo, $ciclo);
            }

            $primeraFirma = $registro->firmaActualDeEtapasDelFlujo((int) $flujo->id, $ciclo);

            if (! $primeraFirma) {
                throw new RuntimeException('No se pudo iniciar el flujo de revision de PPS/SS.');
            }

            $empleadoActor = $userId ? User::find($userId)?->empleado : null;

            if (! $empleadoActor) {
                throw new RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            $tipoEstadoId = $primeraFirma->cargo_firma()->value('tipo_estado_id');

            if (! $tipoEstadoId) {
                throw new RuntimeException('No se pudo determinar el estado inicial del flujo PPS/SS.');
            }

            $registro->agregarEstado(
                $empleadoActor,
                (int) $tipoEstadoId,
                $esReenvio
                    ? 'Reenvío posterior a subsanación.'
                    : 'Registro enviado a revisión mediante flujo configurable PPS/SS.'
            );
            $registro->forceFill([
                'etapa_actual_id' => $primeraFirma->flujo_aprobacion_etapa_id,
                'fecha_envio' => now(),
                'enviado_por' => $userId,
                'updated_by' => $userId,
                // La observación original permanece en estado_proyecto como
                // movimiento histórico; el resumen superior solo representa
                // una subsanación pendiente y se limpia al reenviar.
                'motivo_rechazo' => $esReenvio ? null : $registro->motivo_rechazo,
            ])->saveQuietly();

            $this->validarDestinatarioDeFirma($primeraFirma);
            $this->notificarRevisionPendiente($registro, $primeraFirma, $esReenvio ? 'reenvio_subsanacion' : 'envio_revision');

            Log::info('Ciclo PPS/SS preparado', [
                'proceso' => PpsServicioSocial::PROCESO_FLUJO,
                'registro_id' => $registro->id,
                'flujo_id' => $flujo->id,
                'ciclo_anterior' => $esReenvio ? $ciclo - 1 : null,
                'ciclo_nuevo' => $ciclo,
                'etapa_retorno_id' => $primeraFirma->flujo_aprobacion_etapa_id,
                'revisor_usuario_id' => $primeraFirma->empleado?->user_id,
                'etapas_creadas' => $firmasCreadas->pluck('flujo_aprobacion_etapa_id')->all(),
            ]);

            return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
        });
    }

    public function aprobarEtapa(PpsServicioSocial $registro, ?int $userId, ?object $user = null): PpsServicioSocial
    {
        return DB::transaction(function () use ($registro, $userId, $user): PpsServicioSocial {
            $registro = PpsServicioSocial::query()->lockForUpdate()->findOrFail($registro->id);
            $this->validarUsuarioRevisor($registro, $userId, $user);

            $firmaActual = $this->firmaActualODie($registro);

            $empleadoActor = $userId ? User::find($userId)?->empleado : null;

            if (! $empleadoActor) {
                throw new RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            $firmaActual->update([
                'estado_revision' => 'Aprobado',
                'fecha_firma' => now(),
            ]);

            $firmaAprobada = $firmaActual->fresh();
            $registro->anularFirmasPendientesDuplicadasDeEtapa(
                (int) $firmaAprobada->flujo_aprobacion_etapa_id,
                (int) $firmaAprobada->revision_ciclo,
                $firmaAprobada->id
            );

            $siguienteFirma = $registro->siguienteFirmaDeEtapa($firmaAprobada);

            if ($siguienteFirma) {
                $tipoEstadoId = $siguienteFirma->cargo_firma()->value('tipo_estado_id');

                if (! $tipoEstadoId) {
                    throw new RuntimeException('No se pudo determinar la siguiente etapa del flujo PPS/SS.');
                }

                $registro->agregarEstado($empleadoActor, (int) $tipoEstadoId, 'Registro avanzado a la siguiente etapa mediante flujo configurable PPS/SS.');
                $registro->forceFill([
                    'etapa_actual_id' => $siguienteFirma->flujo_aprobacion_etapa_id,
                    'updated_by' => $userId,
                ])->saveQuietly();

                $this->validarDestinatarioDeFirma($siguienteFirma);
                $this->notificarRevisionPendiente($registro, $siguienteFirma, 'avance_etapa');

                return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
            }

            if (! $registro->firmasDeEtapasCompletadas((int) $firmaAprobada->flujo_aprobacion_id, (int) $firmaAprobada->revision_ciclo)) {
                throw new RuntimeException('El recorrido de firmas no esta completo o contiene una etapa bloqueada.');
            }

            $estadoAprobadoId = TipoEstado::where('nombre', 'Aprobado')->value('id');

            if (! $estadoAprobadoId) {
                throw new RuntimeException('No existe un estado "Aprobado" configurado.');
            }

            $registro->agregarEstado($empleadoActor, (int) $estadoAprobadoId, 'Registro aprobado en etapa final mediante flujo configurable PPS/SS.');
            $registro->forceFill([
                'fecha_revision' => now(),
                'revisado_por' => $userId,
                'motivo_rechazo' => null,
                'updated_by' => $userId,
            ])->saveQuietly();

            return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
        });
    }

    public function rechazar(PpsServicioSocial $registro, string $motivo, ?int $userId, ?object $user = null): PpsServicioSocial
    {
        return DB::transaction(function () use ($registro, $motivo, $userId, $user): PpsServicioSocial {
            $registro = PpsServicioSocial::query()->lockForUpdate()->findOrFail($registro->id);
            $this->validarUsuarioRevisor($registro, $userId, $user);

            $motivo = trim($motivo);

            if ($motivo === '') {
                throw new RuntimeException('El motivo de rechazo es obligatorio.');
            }

            $firmaActual = $this->firmaActualODie($registro);

            $estadoRechazadoId = TipoEstado::where('nombre', 'Rechazado')->value('id');

            if (! $estadoRechazadoId) {
                throw new RuntimeException('No existe un estado "Rechazado" configurado.');
            }

            $empleadoActor = $userId ? User::find($userId)?->empleado : null;

            if (! $empleadoActor) {
                throw new RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            $firmaActual->update([
                'estado_revision' => 'Rechazado',
                'fecha_firma' => now(),
            ]);

            $registro->anularFirmasPendientesDuplicadasDeEtapa(
                (int) $firmaActual->flujo_aprobacion_etapa_id,
                (int) $firmaActual->revision_ciclo,
                $firmaActual->id
            );

            $registro->agregarEstado($empleadoActor, (int) $estadoRechazadoId, $motivo);
            $registro->forceFill([
                'fecha_revision' => now(),
                'revisado_por' => $userId,
                'motivo_rechazo' => $motivo,
                'updated_by' => $userId,
            ])->saveQuietly();

            return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
        });
    }

    public function iniciarSubsanacion(PpsServicioSocial $registro, ?int $userId): PpsServicioSocial
    {
        return DB::transaction(function () use ($registro, $userId): PpsServicioSocial {
            $registro = PpsServicioSocial::query()->lockForUpdate()->findOrFail($registro->id);
            $this->validarPuedeSubsanar($registro, $userId);

            $empleadoActor = $userId ? User::find($userId)?->empleado : null;

            if (! $empleadoActor) {
                throw new RuntimeException('El usuario no tiene un empleado activo asociado.');
            }

            $estadoBorradorId = TipoEstado::where('nombre', 'Borrador')->value('id');

            if (! $estadoBorradorId) {
                throw new RuntimeException('No existe un estado "Borrador" configurado.');
            }

            $registro->agregarEstado($empleadoActor, (int) $estadoBorradorId, 'Inicio de subsanación.');
            $registro->forceFill(['updated_by' => $userId])->saveQuietly();

            return $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
        });
    }

    public function validarPuedeSubsanar(PpsServicioSocial $registro, ?int $userId): void
    {
        if ($registro->estado !== 'rechazado') {
            throw new RuntimeException('Solo los registros rechazados pueden pasar a subsanacion.');
        }

        if (! $registro->perteneceAlUsuario($userId)) {
            throw new RuntimeException('Solo el usuario creador puede iniciar la subsanacion del registro.');
        }
    }

    /**
     * Conservado por compatibilidad con PpsServicioSocial::puedeSubsanarse().
     * PPS ya no distingue una "etapa editable": la subsanación siempre
     * devuelve el registro a Borrador (ver iniciarSubsanacion()).
     */
    public function obtenerEtapaEditable(PpsServicioSocial $registro): ?FlujoAprobacionEtapa
    {
        return null;
    }

    public function validarEtapaActualDelFlujo(PpsServicioSocial $registro): FlujoAprobacionEtapa
    {
        $firma = $this->firmaActualODie($registro);

        return $firma->flujoEtapa;
    }

    public function puedeRechazarEtapaActual(PpsServicioSocial $registro): bool
    {
        try {
            $this->firmaActualODie($registro);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    public function esEstadoFinalAprobado(PpsServicioSocial $registro): bool
    {
        return $registro->estado === 'aprobado';
    }

    private function firmaActualODie(PpsServicioSocial $registro): FirmaProyecto
    {
        if (! $registro->flujo_aprobacion_id) {
            throw new RuntimeException('El registro no tiene un flujo asignado.');
        }

        $firma = $registro->firmaActualDeEtapasDelFlujo(
            (int) $registro->flujo_aprobacion_id,
            $this->obtenerCicloVigente($registro)
        );

        if (! $firma) {
            throw new RuntimeException('El registro no esta en una etapa revisable del flujo PPS/SS.');
        }

        return $firma;
    }

    public function obtenerUltimoCiclo(PpsServicioSocial $registro): int
    {
        return max(0, (int) $registro->firmasDeEtapa()
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->max('revision_ciclo'));
    }

    public function obtenerCicloVigente(PpsServicioSocial $registro): int
    {
        return max(1, $this->obtenerUltimoCiclo($registro));
    }

    public function esReenvioDespuesDeRechazo(PpsServicioSocial $registro): bool
    {
        $ciclo = $this->obtenerUltimoCiclo($registro);

        return $ciclo > 0 && $registro->firmasDeEtapa()
            ->where('revision_ciclo', $ciclo)
            ->where('estado_revision', 'Rechazado')
            ->exists();
    }

    public function obtenerCicloParaEnvio(PpsServicioSocial $registro): int
    {
        $ultimoCiclo = $this->obtenerUltimoCiclo($registro);

        return $this->esReenvioDespuesDeRechazo($registro)
            ? $ultimoCiclo + 1
            : max(1, $ultimoCiclo ?: 1);
    }

    public function etapasQueRequierenDestinatario(PpsServicioSocial $registro): Collection
    {
        if (! $this->esReenvioDespuesDeRechazo($registro)) {
            $flujo = $this->resolverFlujoActivoParaEnvio($registro);

            return $registro->etapasActivasDelFlujo($flujo)
                ->filter(fn (FlujoAprobacionEtapa $etapa): bool => (bool) $etapa->emisor_define_destinatario)
                ->map(fn (FlujoAprobacionEtapa $etapa): array => [
                    'id' => (int) $etapa->id,
                    'orden' => (int) $etapa->orden,
                    'nombre' => $etapa->nombre,
                    'rol_requerido' => $etapa->rolRevisor?->name,
                ])
                ->values();
        }

        $plan = $this->planDeUltimoCiclo($registro);
        /** @var FirmaProyecto $firmaRechazada */
        $firmaRechazada = $plan->rejectedStage['source'];

        return $plan->stages
            ->filter(function (array $snapshot) use ($registro, $firmaRechazada): bool {
                /** @var FirmaProyecto $firma */
                $firma = $snapshot['source'];

                return ! $this->usuarioHistoricoElegible($registro, $firma, $firmaRechazada);
            })
            ->map(function (array $snapshot): array {
                /** @var FirmaProyecto $firma */
                $firma = $snapshot['source'];

                return [
                    'id' => (int) $firma->flujo_aprobacion_etapa_id,
                    'orden' => (int) $firma->orden_revision,
                    'nombre' => $firma->etapa_nombre ?: $firma->etapa_codigo,
                    'rol_requerido' => $firma->rol_requerido,
                ];
            })
            ->values();
    }

    private function flujoPpsQuery(): Builder
    {
        return FlujoAprobacion::query()->where('proceso', PpsServicioSocial::PROCESO_FLUJO);
    }

    private function resolverFlujoActivoParaEnvio(PpsServicioSocial $registro): FlujoAprobacion
    {
        $flujoActivo = $this->obtenerFlujoActivo();

        if (! $flujoActivo) {
            throw new RuntimeException('No existe un flujo activo configurado para PPS/Servicio Social.');
        }

        if (! $registro->flujo_aprobacion_id) {
            return $flujoActivo;
        }

        $flujoAsignado = $this->flujoPpsQuery()
            ->whereKey($registro->flujo_aprobacion_id)
            ->where('activo', true)
            ->first();

        if (! $flujoAsignado) {
            throw new RuntimeException('El flujo asignado al registro no esta activo o no pertenece al proceso PPS/Servicio Social.');
        }

        return $flujoAsignado;
    }

    private function resolverEmpleadosPorEtapa(PpsServicioSocial $registro, Collection $etapas): array
    {
        $destinatariosEmisor = $registro->destinatarios_emisor ?? [];
        $empleadosPorEtapa = [];

        foreach ($etapas as $etapa) {
            $etapaId = (int) $etapa->id;

            if (! $etapa->cargo_firma_id) {
                throw new RuntimeException(sprintf('La etapa "%s" no tiene cargo de firma configurado.', $etapa->nombre));
            }

            $usuario = $this->resolverUsuarioEtapa($etapa, $destinatariosEmisor);

            if (! $usuario) {
                throw new RuntimeException(sprintf(
                    'No existe un responsable válido para la etapa "%s".',
                    $etapa->nombre
                ));
            }

            $empleado = $usuario->empleado;

            if (! $empleado || $empleado->trashed()) {
                throw new RuntimeException(sprintf(
                    'El responsable de la etapa "%s" no tiene un empleado activo vinculado.',
                    $etapa->nombre
                ));
            }

            $empleadosPorEtapa[$etapaId] = $empleado->id;
        }

        return $empleadosPorEtapa;
    }

    /**
     * @return array{0: FlujoAprobacion, 1: int, 2: Collection<int, FirmaProyecto>}
     */
    private function crearCicloDeReanudacion(PpsServicioSocial $registro, array $reemplazosHistoricos): array
    {
        $ultimoCiclo = $this->obtenerUltimoCiclo($registro);
        $plan = $this->planDeUltimoCiclo($registro);

        /** @var FirmaProyecto $firmaRechazada */
        $firmaRechazada = $plan->rejectedStage['source'];
        $empleadosPorEtapa = $this->resolverEmpleadosHistoricosParaReanudacion(
            $registro,
            $plan->stages,
            $firmaRechazada,
            $reemplazosHistoricos
        );
        $flujo = FlujoAprobacion::query()->find($firmaRechazada->flujo_aprobacion_id);

        if (! $flujo || $flujo->proceso !== PpsServicioSocial::PROCESO_FLUJO) {
            throw new RuntimeException('El historial no contiene un flujo PPS/SS válido para reanudar.');
        }

        $firmasCreadas = $registro->crearNuevoCicloDesdeFirmaRechazada($firmaRechazada, $empleadosPorEtapa);

        return [$flujo, $ultimoCiclo + 1, $firmasCreadas];
    }

    private function planDeUltimoCiclo(PpsServicioSocial $registro): \App\Services\Workflow\WorkflowResumptionPlan
    {
        $ultimoCiclo = $this->obtenerUltimoCiclo($registro);
        $firmasCiclo = $registro->firmasDeEtapa()
            ->where('flujo_aprobacion_id', $registro->flujo_aprobacion_id)
            ->where('revision_ciclo', $ultimoCiclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->orderBy('orden_revision')
            ->orderBy('id')
            ->get();

        return app(WorkflowResumptionPolicy::class)->plan(
            $firmasCiclo
                ->reject(fn (FirmaProyecto $firma): bool => $firma->estado_revision === 'Anulado')
                ->map(fn (FirmaProyecto $firma): array => [
                    'stage_id' => (int) $firma->flujo_aprobacion_etapa_id,
                    'order' => (int) $firma->orden_revision,
                    'status' => match ($firma->estado_revision) {
                        'Aprobado' => 'APPROVED',
                        'Rechazado' => 'REJECTED',
                        'Pendiente' => 'PENDING',
                        default => 'INVALID',
                    },
                    'source' => $firma,
                ])
                ->values()
        );
    }

    private function resolverEmpleadosHistoricosParaReanudacion(
        PpsServicioSocial $registro,
        Collection $etapasHistoricas,
        FirmaProyecto $firmaRechazada,
        array $reemplazosHistoricos
    ): array {
        $empleadosPorEtapa = [];

        foreach ($etapasHistoricas as $snapshot) {
            /** @var FirmaProyecto $firma */
            $firma = $snapshot['source'];
            $usuario = $this->usuarioHistoricoElegible($registro, $firma, $firmaRechazada);

            if (! $usuario) {
                $reemplazoId = $reemplazosHistoricos[(int) $firma->flujo_aprobacion_etapa_id] ?? null;
                $reemplazo = $reemplazoId
                    ? User::with('empleado')->find((int) $reemplazoId)
                    : null;
                $usuario = app(WorkflowResumptionPolicy::class)->eligibleRecipient(
                    $reemplazo,
                    $firma->rol_requerido,
                    true
                );
            }

            if (! $usuario) {
                throw new RuntimeException(sprintf(
                    'El revisor anterior de la etapa "%s" ya no es elegible; seleccione un reemplazo válido.',
                    $firma->etapa_nombre ?: $firma->etapa_codigo
                ));
            }

            $empleadosPorEtapa[(int) $firma->flujo_aprobacion_etapa_id] = (int) $usuario->empleado->id;
        }

        return $empleadosPorEtapa;
    }

    private function usuarioHistoricoElegible(
        PpsServicioSocial $registro,
        FirmaProyecto $firma,
        FirmaProyecto $firmaRechazada
    ): ?User {
        $firma->loadMissing('empleado.user');
        $esEtapaRechazada = (int) $firma->id === (int) $firmaRechazada->id;
        $usuarioAnterior = $esEtapaRechazada
            ? User::withTrashed()->with('empleado')->find($registro->revisado_por)
            : $firma->empleado?->user;

        return app(WorkflowResumptionPolicy::class)->eligibleRecipient(
            $usuarioAnterior,
            $firma->rol_requerido,
            true
        );
    }

    private function resolverUsuarioEtapa(FlujoAprobacionEtapa $etapa, array $usuariosElegidosPorEtapa): ?User
    {
        $etapa->loadMissing(['usuarioResponsable.empleado', 'rolRevisor']);

        if ($etapa->emisor_define_destinatario) {
            $usuarioId = $usuariosElegidosPorEtapa[$etapa->id] ?? null;

            if (! $usuarioId) {
                throw new RuntimeException(sprintf(
                    'Debe seleccionar un destinatario para la etapa "%s".',
                    $etapa->nombre
                ));
            }

            $usuario = User::with('empleado')->find((int) $usuarioId);
            $rol = $etapa->rolRevisor?->name;

            if (! $usuario || ! $rol || ! $usuario->hasRole($rol)) {
                throw new RuntimeException(sprintf(
                    'El destinatario seleccionado para la etapa "%s" no pertenece al rol requerido.',
                    $etapa->nombre
                ));
            }

            return $usuario;
        }

        if ($etapa->usuarioResponsable) {
            return $etapa->usuarioResponsable;
        }

        if ($etapa->requiere_asignacion) {
            throw new RuntimeException(sprintf(
                'La etapa "%s" requiere un responsable fijo válido antes de enviar.',
                $etapa->nombre
            ));
        }

        $rol = $etapa->rolRevisor?->name;

        return $rol
            ? User::role($rol)->whereHas('empleado')->with('empleado')->orderBy('name')->first()
            : null;
    }

    private function validarUsuarioRevisor(PpsServicioSocial $registro, ?int $userId, ?object $user = null): void
    {
        $user ??= auth()->user();

        if (! $user && $userId) {
            $user = User::find($userId);
        }

        if (! $registro->usuarioPuedeRevisar($user)) {
            throw new RuntimeException('El usuario no tiene permisos para revisar registros PPS/SS.');
        }
    }

    private function validarDestinatarioDeFirma(FirmaProyecto $firma): User
    {
        $firma->loadMissing(['empleado.user', 'flujoEtapa']);
        $destinatario = $firma->empleado?->user;

        if (! $destinatario || blank($destinatario->email) || ! filter_var($destinatario->email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(sprintf(
                'La etapa "%s" no tiene un revisor asignado con correo válido.',
                $firma->etapa_nombre ?: $firma->etapa_codigo
            ));
        }

        return $destinatario;
    }

    private function notificarRevisionPendiente(PpsServicioSocial $registro, FirmaProyecto $firma, string $evento): void
    {
        $destinatario = $this->validarDestinatarioDeFirma($firma);
        $etapa = $firma->flujoEtapa;

        if (! $etapa) {
            throw new RuntimeException('La etapa histórica ya no existe y no puede notificarse.');
        }

        $registroParaCorreo = $registro->fresh(['flujoAprobacion', 'etapaActual']) ?? $registro;
        try {
            Mail::to($destinatario->email)->queue(
                (new EtapaFlujoPendiente($registroParaCorreo, $destinatario, $etapa, 'pps-servicio-social'))->afterCommit()
            );
        } catch (\Throwable $e) {
            Log::error('No se pudo encolar notificacion PPS/SS', [
                'registro_id' => $registro->id,
                'etapa_id' => $etapa->id,
                'destinatario_id' => $destinatario->id,
                'evento' => $evento,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);
        }

        Log::info('Notificacion PPS/SS de revision pendiente enviada', [
            'registro_id' => $registro->id,
            'codigo_registro' => $registro->codigo_registro,
            'evento' => $evento,
            'etapa_id' => $etapa->id,
            'etapa_nombre' => $etapa->nombre,
            'rol_revisor_id' => $etapa->rol_revisor_id,
            'requiere_asignacion' => (bool) $etapa->requiere_asignacion,
            'usuario_responsable_id' => $etapa->usuario_responsable_id,
            'destinatarios' => [['id' => $destinatario->id, 'name' => $destinatario->name, 'email' => $destinatario->email]],
        ]);
    }
}

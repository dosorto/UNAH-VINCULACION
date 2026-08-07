<?php

namespace App\Services\DAFT;

use App\Mail\ProgramaRevisionAsignada;
use App\Models\DAFT\ProgramaCertificacion;
use App\Models\DAFT\ProgramaRevision;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use App\Services\Workflow\WorkflowResumptionPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProgramaWorkflowService
{
    public function enviarARevision(ProgramaCertificacion $programa, User $actor, array $destinatariosEmisor = []): void
    {
        DB::transaction(function () use ($programa, $actor, $destinatariosEmisor): void {
            $programa = ProgramaCertificacion::query()->lockForUpdate()->findOrFail($programa->id);

            if (! $programa->estaEditable()) {
                throw new \DomainException('Solo los programas en elaboración o subsanación pueden enviarse a revisión.');
            }

            $flujo = $this->resolverFlujo($programa);

            if (! $flujo || (! $programa->tieneSubsanacionPendiente() && $flujo->etapas->isEmpty())) {
                throw new \DomainException('No hay un flujo activo configurado para este tipo de programa.');
            }

            $ultimoCicloHistorico = (int) $programa->revisiones()->max('revision_ciclo');

            if ($programa->tieneSubsanacionPendiente()
                && $ultimoCicloHistorico !== (int) $programa->revision_ciclo) {
                throw new \DomainException('El ciclo vigente del programa no coincide con su último ciclo histórico.');
            }

            $cicloAnterior = max((int) $programa->revision_ciclo, $ultimoCicloHistorico);
            $nextCycle = $cicloAnterior + 1;
            $stages = $this->etapasParaNuevoCiclo($programa, $flujo);

            foreach ($stages as $stage) {
                $flowStage = $stage instanceof ProgramaRevision ? $stage->flujoEtapa : $stage;
                $flowStageId = $stage instanceof ProgramaRevision
                    ? $stage->flujo_aprobacion_etapa_id
                    : $flowStage?->id;
                $emisorDefineDestinatario = (bool) ($flowStage?->emisor_define_destinatario ?? false);
                $requiresAssignment = ! ($stage instanceof ProgramaRevision)
                    && ! $emisorDefineDestinatario
                    && (bool) ($flowStage?->requiere_asignacion ?? false);

                if ($stage instanceof ProgramaRevision) {
                    $assignedReviewer = $this->responsableAnteriorElegible($stage)
                        ?? $this->resolverReemplazoHistorico($stage, $destinatariosEmisor);
                } else {
                    $reviewerSelectedBySender = $emisorDefineDestinatario
                        ? $this->resolverDestinatarioDelEmisor($flowStage, $stage, $destinatariosEmisor)
                        : null;
                    $defaultReviewer = $reviewerSelectedBySender
                        ?? ($flowStage instanceof FlujoAprobacionEtapa ? $this->resolverRevisorPredeterminado($flowStage) : null);
                    $assignedReviewer = $requiresAssignment ? null : $defaultReviewer;
                }

                ProgramaRevision::create([
                    'programa_certificacion_id' => $programa->id,
                    'flujo_aprobacion_etapa_id' => $flowStageId,
                    'revision_ciclo' => $nextCycle,
                    'orden' => $stage->orden,
                    'etapa_codigo' => $stage instanceof ProgramaRevision ? $stage->etapa_codigo : $stage->codigo,
                    'etapa_nombre' => $stage instanceof ProgramaRevision ? $stage->etapa_nombre : $stage->nombre,
                    'rol_requerido' => $stage instanceof ProgramaRevision ? $stage->rol_requerido : $flowStage?->rolRevisor?->name,
                    'responsable_usuario_id' => $stage instanceof ProgramaRevision
                        ? $assignedReviewer?->id
                        : $flowStage?->usuario_responsable_id,
                    'asignado_usuario_id' => $assignedReviewer?->id,
                    'estado' => $assignedReviewer ? 'ASIGNADO' : ($requiresAssignment ? 'PENDIENTE_ASIGNACION' : 'PENDIENTE'),
                ]);
            }

            $programa->update([
                'estado_flujo' => 'EN_REVISION',
                'revision_ciclo' => $nextCycle,
                'enviado_revision_en' => now(),
                'observaciones_revision' => null,
                'subsanacion_revision_id' => null,
                'subsanacion_etapa_orden' => null,
                'subsanacion_etapa_nombre' => null,
                'subsanacion_devuelto_en' => null,
                'flujo_aprobacion_id' => $flujo->id,
                'modificado_por_usuario_id' => $actor->id,
            ]);

            $this->sincronizarVersion($programa, 'EN_REVISION', $actor);

            $primeraRevision = ProgramaRevision::query()
                ->with(['programa', 'asignadoUsuario'])
                ->where('programa_certificacion_id', $programa->id)
                ->where('revision_ciclo', $nextCycle)
                ->orderBy('orden')
                ->first();

            if ($primeraRevision) {
                $this->notificarRevisionAsignada($primeraRevision);
            }

            Log::info('Ciclo DAFT preparado', [
                'proceso' => 'PROGRAMA',
                'registro_id' => $programa->id,
                'flujo_id' => $flujo->id,
                'ciclo_anterior' => $cicloAnterior ?: null,
                'ciclo_nuevo' => $nextCycle,
                'etapa_retorno_id' => $stages->first() instanceof ProgramaRevision
                    ? $stages->first()->flujo_aprobacion_etapa_id
                    : $stages->first()?->id,
                'revisor_usuario_id' => ProgramaRevision::query()
                    ->where('programa_certificacion_id', $programa->id)
                    ->where('revision_ciclo', $nextCycle)
                    ->orderBy('orden')
                    ->value('asignado_usuario_id'),
            ]);
        });
    }

    public function asignarAlUsuario(ProgramaRevision $revision, User $actor): void
    {
        $this->asignarAUsuario($revision, $actor, $actor);
    }

    public function asignarAUsuario(ProgramaRevision $revision, User $actor, User $destinatario): void
    {
        DB::transaction(function () use ($revision, $actor, $destinatario): void {
            $revisionActual = ProgramaRevision::query()
                ->with(['programa', 'flujoEtapa.rolRevisor'])
                ->lockForUpdate()
                ->findOrFail($revision->id);

            if ($revisionActual->estado !== 'PENDIENTE_ASIGNACION') {
                throw new \DomainException('La revisión ya fue tomada o resuelta. La bandeja fue actualizada.');
            }
            if (! $revisionActual->programa || $revisionActual->programa->etapaActual()?->id !== $revisionActual->id) {
                throw new \DomainException('La revisión ya no es la etapa actual del programa. La bandeja fue actualizada.');
            }
            abort_if(! $this->usuarioPuedeAsignar($revisionActual, $actor), 403);

            if (! $this->usuarioEsElegibleParaRevision($revisionActual, $destinatario)) {
                throw new \DomainException('El usuario seleccionado no está activo o no pertenece al rol revisor de esta etapa.');
            }

            $revisionActual->update(['asignado_usuario_id' => $destinatario->id, 'estado' => 'ASIGNADO']);
            $this->notificarRevisionAsignada($revisionActual->fresh(['programa', 'asignadoUsuario']));
        });
    }

    public function aprobar(ProgramaRevision $revision, User $actor, ?string $observaciones = null): void
    {
        $revision->loadMissing(['programa', 'flujoEtapa.rolRevisor', 'flujoEtapa.usuarioResponsable']);
        $programa = $revision->programa;
        if (! $programa || $programa->etapaActual()?->id !== $revision->id) {
            throw new \DomainException('La revisión ya no es la etapa actual del programa. La bandeja fue actualizada.');
        }
        abort_if(! $this->usuarioPuedeActuar($revision, $actor), 403);

        DB::transaction(function () use ($programa, $revision, $actor, $observaciones): void {
            $revision->update([
                'estado' => 'APROBADO',
                'decidido_por_usuario_id' => $actor->id,
                'observaciones' => $observaciones,
                'firma_nombre' => $actor->name,
                'firmado_en' => now(),
            ]);

            $next = $programa->revisiones()
                ->with(['flujoEtapa.rolRevisor', 'flujoEtapa.usuarioResponsable'])
                ->where('revision_ciclo', $programa->revision_ciclo)
                ->where('orden', '>', $revision->orden)
                ->orderBy('orden')
                ->first();

            if (! $next) {
                $programa->update([
                    'estado' => 1,
                    'estado_flujo' => 'APROBADO',
                    'observaciones_revision' => null,
                    'subsanacion_revision_id' => null,
                    'subsanacion_etapa_orden' => null,
                    'subsanacion_etapa_nombre' => null,
                    'subsanacion_devuelto_en' => null,
                    'modificado_por_usuario_id' => $actor->id,
                ]);
                $this->sincronizarVersion($programa, 'APROBADO', $actor);

                return;
            }

            if (in_array($next->estado, ['PENDIENTE', 'PENDIENTE_ASIGNACION'], true)) {
                $defaultReviewer = $next->flujoEtapa
                    ? $this->resolverRevisorPredeterminado($next->flujoEtapa)
                    : ($next->rol_requerido ? User::role($next->rol_requerido)->orderBy('name')->first() : null);
                $requiresAssignment = (bool) $next->flujoEtapa?->requiere_asignacion;
                $next->update([
                    'estado' => $requiresAssignment ? 'PENDIENTE_ASIGNACION' : ($defaultReviewer ? 'ASIGNADO' : 'PENDIENTE'),
                    'asignado_usuario_id' => $requiresAssignment ? null : $defaultReviewer?->id,
                ]);
            }

            $this->notificarRevisionAsignada($next->fresh(['programa', 'asignadoUsuario']));
        });
    }

    public function rechazar(ProgramaRevision $revision, User $actor, string $observaciones): void
    {
        $observaciones = trim($observaciones);
        if ($observaciones === '') {
            throw new \DomainException('Debe indicar las observaciones para subsanación.');
        }

        $revision->loadMissing(['programa', 'flujoEtapa.rolRevisor']);
        $programa = $revision->programa;
        if (! $programa || $programa->etapaActual()?->id !== $revision->id) {
            throw new \DomainException('La revisión ya no es la etapa actual del programa. La bandeja fue actualizada.');
        }
        abort_if(! $this->usuarioPuedeActuar($revision, $actor), 403);

        DB::transaction(function () use ($programa, $revision, $actor, $observaciones): void {
            $revision->update([
                'estado' => 'RECHAZADO',
                'decidido_por_usuario_id' => $actor->id,
                'observaciones' => $observaciones,
                'firma_nombre' => $actor->name,
                'firmado_en' => now(),
            ]);
            $programa->update([
                'estado_flujo' => 'SUBSANACION',
                'observaciones_revision' => $observaciones,
                'subsanacion_revision_id' => $revision->id,
                'subsanacion_etapa_orden' => $revision->orden,
                'subsanacion_etapa_nombre' => $revision->etapa_nombre,
                'subsanacion_devuelto_en' => now(),
                'modificado_por_usuario_id' => $actor->id,
            ]);
            $this->sincronizarVersion($programa, 'SUBSANACION', $actor, $observaciones);
        });
    }

    public function resolverFlujo(ProgramaCertificacion $programa): ?FlujoAprobacion
    {
        if ($programa->flujoAprobacion?->exists) {
            return $programa->flujoAprobacion->load([
                'etapas' => fn ($query) => $query->where('activo', true)->orderBy('orden'),
                'etapas.rolRevisor', 'etapas.usuarioResponsable',
            ]);
        }

        return FlujoAprobacion::query()
            ->with(['etapas' => fn ($query) => $query->where('activo', true)->orderBy('orden'), 'etapas.rolRevisor', 'etapas.usuarioResponsable'])
            ->where('proceso', 'PROGRAMA')
            ->where('tipo_programa_id', $programa->tipo_programa_id)
            ->where('activo', true)
            ->first();
    }

    public function resolverRevisorPredeterminado(FlujoAprobacionEtapa $stage): ?User
    {
        if ($stage->usuario_responsable_id && ! $stage->requiere_asignacion) {
            return $stage->usuarioResponsable;
        }
        if (! $stage->rolRevisor?->name) {
            return null;
        }

        return User::role($stage->rolRevisor->name)
            ->orderByRaw('CASE WHEN active_role_id = ? THEN 0 ELSE 1 END', [$stage->rol_revisor_id])
            ->orderBy('name')
            ->first();
    }

    public function etapasConDestinatarioDefinidoPorEmisor(ProgramaCertificacion $programa): Collection
    {
        $flujo = $this->resolverFlujo($programa);

        if (! $flujo) {
            return collect();
        }

        return $this->etapasParaNuevoCiclo($programa, $flujo)
            ->filter(function ($stage): bool {
                if ($stage instanceof ProgramaRevision) {
                    return ! $this->responsableAnteriorElegible($stage);
                }

                return (bool) ($stage->activo && $stage->emisor_define_destinatario);
            })
            ->map(function (FlujoAprobacionEtapa|ProgramaRevision $stage): array {
                if ($stage instanceof ProgramaRevision) {
                    return [
                        'id' => (int) $stage->flujo_aprobacion_etapa_id,
                        'orden' => (int) $stage->orden,
                        'nombre' => $stage->etapa_nombre,
                        'rol_requerido' => $stage->rol_requerido,
                    ];
                }

                return [
                    'id' => (int) $stage->id,
                    'orden' => (int) $stage->orden,
                    'nombre' => $stage->nombre,
                    'rol_requerido' => $stage->rolRevisor?->name,
                ];
            })
            ->unique('id')
            ->values();
    }

    public function usuarioTieneRolDeEtapa(ProgramaRevision $revision, User $actor): bool
    {
        $roleName = $revision->flujoEtapa?->rolRevisor?->name ?: $revision->rol_requerido;

        return ! $roleName || $actor->activeRole?->name === $roleName;
    }

    public function usuarioPuedeAsignar(ProgramaRevision $revision, User $actor): bool
    {
        return $revision->estado === 'PENDIENTE_ASIGNACION'
            && ($this->esAdministrador($actor) || $this->usuarioTieneRolDeEtapa($revision, $actor));
    }

    public function usuariosElegiblesParaRevision(ProgramaRevision $revision): Collection
    {
        $roleName = $revision->flujoEtapa?->rolRevisor?->name ?: $revision->rol_requerido;
        $query = User::query()->with('roles')->orderBy('name');

        if ($roleName) {
            $query->role($roleName);
        }

        return $query->get();
    }

    public function usuarioPuedeActuar(ProgramaRevision $revision, User $actor): bool
    {
        if ($revision->estado === 'PENDIENTE_ASIGNACION') {
            return false;
        }
        if ($this->esAdministrador($actor)) {
            return true;
        }
        if ($revision->asignado_usuario_id && (int) $revision->asignado_usuario_id !== (int) $actor->id) {
            return false;
        }

        return $this->usuarioTieneRolDeEtapa($revision, $actor)
            || (! $revision->rol_requerido && in_array((int) $actor->id, array_map('intval', array_filter([$revision->asignado_usuario_id, $revision->responsable_usuario_id])), true));
    }

    public function usuarioPuedeVer(ProgramaRevision $revision, User $actor): bool
    {
        $revision->loadMissing(['programa', 'flujoEtapa.rolRevisor']);
        $programa = $revision->programa;

        if (! $programa) {
            return false;
        }

        if ($this->esAdministrador($actor)) {
            return $programa->estado_flujo === 'APROBADO'
                || $programa->etapaActual()?->id === $revision->id;
        }

        if ($programa->estado_flujo === 'APROBADO') {
            return $this->usuarioParticipoEnEtapa($revision, $actor);
        }

        if ($programa->etapaActual()?->id !== $revision->id
            || ! $this->usuarioTieneRolDeEtapa($revision, $actor)) {
            return false;
        }

        if ($revision->asignado_usuario_id) {
            return (int) $revision->asignado_usuario_id === (int) $actor->id;
        }

        if ($revision->responsable_usuario_id) {
            return (int) $revision->responsable_usuario_id === (int) $actor->id;
        }

        return true;
    }

    public function usuarioParticipoEnEtapa(ProgramaRevision $revision, User $actor): bool
    {
        if ($this->esAdministrador($actor)) {
            return true;
        }

        $participantes = array_map('intval', array_filter([
            $revision->asignado_usuario_id,
            $revision->responsable_usuario_id,
            $revision->decidido_por_usuario_id,
        ]));

        return in_array((int) $actor->id, $participantes, true)
            || $this->usuarioTieneRolDeEtapa($revision, $actor);
    }

    protected function responsableAnteriorElegible(FlujoAprobacionEtapa|ProgramaRevision $stage): ?User
    {
        if (! ($stage instanceof ProgramaRevision)) {
            return null;
        }

        $responsableId = $stage->estado === 'RECHAZADO'
            ? $stage->decidido_por_usuario_id
            : $stage->asignado_usuario_id;
        $responsable = $responsableId ? User::withTrashed()->find($responsableId) : null;

        return app(WorkflowResumptionPolicy::class)->eligibleRecipient($responsable, $stage->rol_requerido);
    }

    protected function usuarioEsElegibleParaRevision(ProgramaRevision $revision, User $user): bool
    {
        if (! $user->exists || $user->trashed()) {
            return false;
        }

        $roleName = $revision->flujoEtapa?->rolRevisor?->name ?: $revision->rol_requerido;

        return ! $roleName || $user->hasRole($roleName);
    }

    protected function esAdministrador(User $user): bool
    {
        return $user->activeRole?->name === 'admin';
    }

    protected function etapasParaNuevoCiclo(ProgramaCertificacion $programa, FlujoAprobacion $flujo)
    {
        if (! $programa->tieneSubsanacionPendiente()) {
            return $flujo->etapas;
        }

        $ultimoCiclo = (int) $programa->revisiones()->max('revision_ciclo');
        $revisiones = $programa->revisiones()
            ->with(['flujoEtapa.rolRevisor', 'flujoEtapa.usuarioResponsable', 'asignadoUsuario', 'decididoPorUsuario'])
            ->where('revision_ciclo', $ultimoCiclo)
            ->orderBy('orden')
            ->get();

        $plan = app(WorkflowResumptionPolicy::class)->plan(
            $revisiones->map(fn (ProgramaRevision $revision): array => [
                'stage_id' => (int) $revision->flujo_aprobacion_etapa_id,
                'order' => (int) $revision->orden,
                'status' => match ($revision->estado) {
                    'APROBADO' => 'APPROVED',
                    'RECHAZADO' => 'REJECTED',
                    'PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO' => 'PENDING',
                    default => 'INVALID',
                },
                'source' => $revision,
            ])->values()
        );

        if ((int) $plan->rejectedStage['source']->id !== (int) $programa->subsanacion_revision_id) {
            throw new \DomainException('El historial de subsanación no coincide con la etapa rechazada del último ciclo.');
        }

        return $plan->stages->pluck('source')->values();
    }

    protected function resolverReemplazoHistorico(ProgramaRevision $stage, array $destinatariosEmisor): User
    {
        $selectedUserId = $destinatariosEmisor[(int) $stage->flujo_aprobacion_etapa_id] ?? null;
        $user = $selectedUserId ? User::find((int) $selectedUserId) : null;
        $elegible = app(WorkflowResumptionPolicy::class)->eligibleRecipient($user, $stage->rol_requerido);

        if (! $elegible) {
            throw new \DomainException(sprintf(
                'El revisor anterior de la etapa "%s" ya no es elegible; seleccione un reemplazo válido.',
                $stage->etapa_nombre
            ));
        }

        return $elegible;
    }

    protected function resolverDestinatarioDelEmisor(
        FlujoAprobacionEtapa $flowStage,
        FlujoAprobacionEtapa|ProgramaRevision $stage,
        array $destinatariosEmisor
    ): User {
        $selectedUserId = $destinatariosEmisor[$flowStage->id]
            ?? ($stage instanceof ProgramaRevision ? $stage->asignado_usuario_id : null);

        if (! $selectedUserId) {
            throw new \DomainException('Selecciona el destinatario para la etapa "'.$flowStage->nombre.'".');
        }

        $user = User::find($selectedUserId);
        if (! $user) {
            throw new \DomainException('El destinatario seleccionado para la etapa "'.$flowStage->nombre.'" no existe.');
        }

        if ($flowStage->rol_revisor_id && ! $user->roles()->whereKey($flowStage->rol_revisor_id)->exists()) {
            throw new \DomainException('El destinatario de la etapa "'.$flowStage->nombre.'" no pertenece al rol revisor configurado.');
        }

        return $user;
    }

    protected function sincronizarVersion(ProgramaCertificacion $programa, string $estado, User $actor, ?string $notas = null): void
    {
        $programa = $programa->fresh(['centroFacultad', 'tipoPrograma', 'centrosPrograma.centroFacultad', 'asignaturasPrograma.asignatura']);
        $snapshot = $programa->buildVersionSnapshot();
        if ($estado === 'APROBADO') {
            $programa->versiones()->update(['vigente' => false]);
        }
        $programa->versiones()->updateOrCreate(['numero_version' => $programa->version_actual], [
            'estado' => $estado,
            'vigente' => $estado === 'APROBADO',
            'publicado_en' => $estado === 'APROBADO' ? now() : null,
            'publicado_por_usuario_id' => $estado === 'APROBADO' ? $actor->id : null,
            'notas' => $notas,
            'datos_programa' => $snapshot['programa'],
            'centros_facultad' => $snapshot['centros_facultad'],
            'asignaturas' => $snapshot['asignaturas'],
        ]);
    }

    private function notificarRevisionAsignada(ProgramaRevision $revision): void
    {
        if (! $revision->asignado_usuario_id) {
            return;
        }

        $revision->loadMissing(['programa', 'asignadoUsuario']);
        $destinatario = $revision->asignadoUsuario;

        if (! $destinatario || blank($destinatario->email) || ! filter_var($destinatario->email, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException(sprintf(
                'La etapa "%s" no tiene un revisor asignado con correo válido.',
                $revision->etapa_nombre
            ));
        }

        Mail::to($destinatario->email)->queue(
            (new ProgramaRevisionAsignada($revision))->afterCommit()
        );
    }
}

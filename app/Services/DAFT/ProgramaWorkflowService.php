<?php

namespace App\Services\DAFT;

use App\Models\DAFT\ProgramaCertificacion;
use App\Models\DAFT\ProgramaRevision;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProgramaWorkflowService
{
    public function enviarARevision(ProgramaCertificacion $programa, User $actor, array $destinatariosEmisor = []): void
    {
        if (! $programa->estaEditable()) {
            throw new \DomainException('Solo los programas en elaboración o subsanación pueden enviarse a revisión.');
        }

        $flujo = $this->resolverFlujo($programa);

        if (! $flujo || $flujo->etapas->isEmpty()) {
            throw new \DomainException('No hay un flujo activo configurado para este tipo de programa.');
        }

        DB::transaction(function () use ($programa, $flujo, $actor, $destinatariosEmisor): void {
            $nextCycle = ((int) $programa->revision_ciclo) + 1;
            $stages = $this->etapasParaNuevoCiclo($programa, $flujo);

            foreach ($stages as $stage) {
                $flowStage = $stage instanceof ProgramaRevision ? $stage->flujoEtapa : $stage;
                $emisorDefineDestinatario = (bool) ($flowStage?->emisor_define_destinatario ?? false);
                $reviewerSelectedBySender = $emisorDefineDestinatario
                    ? $this->resolverDestinatarioDelEmisor($flowStage, $stage, $destinatariosEmisor)
                    : null;
                $defaultReviewer = $reviewerSelectedBySender
                    ?? ($stage instanceof ProgramaRevision
                        ? $stage->asignadoUsuario
                        : ($flowStage instanceof FlujoAprobacionEtapa ? $this->resolverRevisorPredeterminado($flowStage) : null));
                $requiresAssignment = ! $emisorDefineDestinatario && (bool) ($flowStage?->requiere_asignacion ?? false);

                ProgramaRevision::create([
                    'programa_certificacion_id' => $programa->id,
                    'flujo_aprobacion_etapa_id' => $flowStage?->id,
                    'revision_ciclo' => $nextCycle,
                    'orden' => $stage->orden,
                    'etapa_codigo' => $stage instanceof ProgramaRevision ? $stage->etapa_codigo : $stage->codigo,
                    'etapa_nombre' => $stage instanceof ProgramaRevision ? $stage->etapa_nombre : $stage->nombre,
                    'rol_requerido' => $stage instanceof ProgramaRevision ? $stage->rol_requerido : $flowStage?->rolRevisor?->name,
                    'responsable_usuario_id' => $stage instanceof ProgramaRevision ? $stage->responsable_usuario_id : $flowStage?->usuario_responsable_id,
                    'asignado_usuario_id' => $requiresAssignment ? null : $defaultReviewer?->id,
                    'estado' => $requiresAssignment ? 'PENDIENTE_ASIGNACION' : ($defaultReviewer ? 'ASIGNADO' : 'PENDIENTE'),
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
            abort_if(! $this->usuarioTieneRolDeEtapa($revisionActual, $actor), 403);

            $roleName = $revisionActual->flujoEtapa?->rolRevisor?->name ?: $revisionActual->rol_requerido;
            if (! $roleName) {
                throw new \DomainException('La etapa no tiene un rol revisor configurado para asignar usuarios.');
            }
            if (! $destinatario->hasRole($roleName)) {
                throw new \DomainException('El usuario seleccionado no pertenece al rol revisor de esta etapa.');
            }

            $revisionActual->update(['asignado_usuario_id' => $destinatario->id, 'estado' => 'ASIGNADO']);
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

    public function etapasConDestinatarioDefinidoPorEmisor(ProgramaCertificacion $programa)
    {
        $flujo = $this->resolverFlujo($programa);

        if (! $flujo) {
            return collect();
        }

        return $this->etapasParaNuevoCiclo($programa, $flujo)
            ->map(fn ($stage) => $stage instanceof ProgramaRevision ? $stage->flujoEtapa : $stage)
            ->filter(fn (?FlujoAprobacionEtapa $stage) => $stage?->activo && $stage->emisor_define_destinatario)
            ->unique('id')
            ->values();
    }

    public function usuarioTieneRolDeEtapa(ProgramaRevision $revision, User $actor): bool
    {
        $roleName = $revision->flujoEtapa?->rolRevisor?->name ?: $revision->rol_requerido;

        return ! $roleName || $actor->activeRole?->name === $roleName;
    }

    public function usuarioPuedeActuar(ProgramaRevision $revision, User $actor): bool
    {
        if ($revision->asignado_usuario_id && (int) $revision->asignado_usuario_id !== (int) $actor->id) {
            return false;
        }
        if ($revision->estado === 'PENDIENTE_ASIGNACION') {
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
        $participantes = array_map('intval', array_filter([
            $revision->asignado_usuario_id,
            $revision->responsable_usuario_id,
            $revision->decidido_por_usuario_id,
        ]));

        return in_array((int) $actor->id, $participantes, true)
            || $this->usuarioTieneRolDeEtapa($revision, $actor);
    }

    protected function etapasParaNuevoCiclo(ProgramaCertificacion $programa, FlujoAprobacion $flujo)
    {
        if (! $programa->tieneSubsanacionPendiente()) {
            return $flujo->etapas;
        }

        $rejectedStage = $programa->revisiones()->where('revision_ciclo', $programa->revision_ciclo)->find($programa->subsanacion_revision_id);
        if (! $rejectedStage) {
            return $flujo->etapas;
        }

        return $programa->revisiones()
            ->with(['flujoEtapa.rolRevisor', 'flujoEtapa.usuarioResponsable', 'asignadoUsuario'])
            ->where('revision_ciclo', $programa->revision_ciclo)
            ->where('orden', '>=', $rejectedStage->orden)
            ->orderBy('orden')
            ->get();
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
}

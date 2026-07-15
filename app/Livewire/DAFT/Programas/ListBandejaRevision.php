<?php

namespace App\Livewire\DAFT\Programas;

use App\Models\DAFT\ProgramaRevision;
use App\Models\DAFT\ProgramaCertificacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ListBandejaRevision extends Component
{
    public array $observaciones = [];

    public function assignToMe(int $revisionId): void
    {
        $revision = ProgramaRevision::with(['programa', 'flujoEtapa.rolRevisor'])->findOrFail($revisionId);
        abort_if($revision->estado !== 'PENDIENTE_ASIGNACION', 422);
        abort_if(! $revision->programa || $revision->programa->etapaActual()?->id !== $revision->id, 422);
        abort_if(! $this->userHasStageRole($revision), 403);

        $revision->update([
            'asignado_usuario_id' => Auth::id(),
            'estado' => 'ASIGNADO',
        ]);

        session()->flash('programas_status', 'Revision asignada correctamente.');
    }

    public function approveRevision(int $revisionId): void
    {
        $revision = ProgramaRevision::with(['programa', 'flujoEtapa.rolRevisor', 'flujoEtapa.usuarioResponsable'])->findOrFail($revisionId);
        $programa = $revision->programa;
        abort_if(! $programa || $programa->etapaActual()?->id !== $revision->id, 422);
        abort_if(! $this->canActOnStage($revision), 403);

        DB::transaction(function () use ($programa, $revision) {
            $revision->update([
                'estado' => 'APROBADO',
                'decidido_por_usuario_id' => Auth::id(),
                'observaciones' => $this->observaciones[$revision->id] ?? null,
                'firma_nombre' => Auth::user()?->name,
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
                    'modificado_por_usuario_id' => Auth::id(),
                ]);

                $this->syncCurrentVersionRecord($programa->fresh([
                    'centroFacultad',
                    'tipoPrograma',
                    'centrosPrograma.centroFacultad',
                    'asignaturasPrograma.asignatura',
                ]), 'APROBADO');

                return;
            }

            if (in_array($next->estado, ['PENDIENTE', 'PENDIENTE_ASIGNACION'], true)) {
                $defaultReviewer = $next->flujoEtapa
                    ? $this->resolveDefaultReviewer($next->flujoEtapa)
                    : ($next->rol_requerido ? User::role($next->rol_requerido)->orderBy('name')->first() : null);

                $next->update([
                    'estado' => $next->flujoEtapa?->requiere_asignacion
                        ? 'PENDIENTE_ASIGNACION'
                        : ($defaultReviewer ? 'ASIGNADO' : 'PENDIENTE'),
                    'asignado_usuario_id' => $next->flujoEtapa?->requiere_asignacion ? null : $defaultReviewer?->id,
                ]);
            }
        });

        unset($this->observaciones[$revisionId]);
        session()->flash('programas_status', 'Etapa aprobada correctamente.');
    }

    public function rejectRevision(int $revisionId): void
    {
        $revision = ProgramaRevision::with(['programa', 'flujoEtapa.rolRevisor'])->findOrFail($revisionId);
        $programa = $revision->programa;
        $observacion = trim((string) ($this->observaciones[$revisionId] ?? ''));

        abort_if(! $programa || $programa->etapaActual()?->id !== $revision->id, 422);
        abort_if(! $this->canActOnStage($revision), 403);

        if ($observacion === '') {
            $this->addError('observaciones.'.$revisionId, 'Debe indicar las observaciones para subsanacion.');
            return;
        }

        DB::transaction(function () use ($programa, $revision, $observacion) {
            $revision->update([
                'estado' => 'RECHAZADO',
                'decidido_por_usuario_id' => Auth::id(),
                'observaciones' => $observacion,
                'firma_nombre' => Auth::user()?->name,
                'firmado_en' => now(),
            ]);

            $programa->update([
                'estado_flujo' => 'SUBSANACION',
                'observaciones_revision' => $observacion,
                'subsanacion_revision_id' => $revision->id,
                'subsanacion_etapa_orden' => $revision->orden,
                'subsanacion_etapa_nombre' => $revision->etapa_nombre,
                'subsanacion_devuelto_en' => now(),
                'modificado_por_usuario_id' => Auth::id(),
            ]);

            $this->syncCurrentVersionRecord($programa->fresh([
                'centroFacultad',
                'tipoPrograma',
                'centrosPrograma.centroFacultad',
                'asignaturasPrograma.asignatura',
            ]), 'SUBSANACION', $observacion);
        });

        unset($this->observaciones[$revisionId]);
        session()->flash('programas_status', 'Programa enviado a subsanacion.');
    }

    public function render(): View
    {
        $revisiones = ProgramaRevision::query()
            ->with(['programa.centroFacultad', 'programa.tipoPrograma', 'flujoEtapa.rolRevisor', 'asignadoUsuario', 'responsableUsuario'])
            ->orderByDesc('id')
            ->get()
            ->filter(fn (ProgramaRevision $revision) => $this->userCanSeeStage($revision));

        $programasPendientes = $revisiones->filter(fn ($rev) => in_array($rev->estado, ['PENDIENTE', 'PENDIENTE_ASIGNACION'], true));
        $programasEnProceso = $revisiones->filter(fn ($rev) => in_array($rev->estado, ['ASIGNADO', 'EN_PROCESO'], true));
        $programasAprobados = $revisiones->filter(fn ($rev) => ($rev->programa?->estado_flujo ?? null) === 'APROBADO'
            && $this->userParticipatedInStage($rev));
        $revisionesAccionables = $revisiones->filter(fn ($rev) => in_array($rev->estado, ['PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO'], true));
        $pendingNotice = $revisionesAccionables->isNotEmpty()
            ? 'Tienes '.$revisionesAccionables->count().' revision(es) pendiente(s) para el rol activo '.($this->activeRoleName() ?? 'actual').'.'
            : null;

        return view('livewire.daft.programas.list-bandeja-revision', compact('programasPendientes', 'programasEnProceso', 'programasAprobados', 'pendingNotice'))
            ->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    protected function canActOnStage(ProgramaRevision $stage): bool
    {
        if ($stage->asignado_usuario_id && $stage->asignado_usuario_id !== Auth::id()) {
            return false;
        }

        if ($stage->estado === 'PENDIENTE_ASIGNACION') {
            return false;
        }

        if ($this->userHasStageRole($stage)) {
            return true;
        }

        return ! $stage->rol_requerido
            && in_array(Auth::id(), array_filter([$stage->asignado_usuario_id, $stage->responsable_usuario_id]), true);
    }

    protected function userHasStageRole(ProgramaRevision $stage): bool
    {
        $roleName = $stage->flujoEtapa?->rolRevisor?->name ?: $stage->rol_requerido;

        if (! $roleName) {
            return true;
        }

        return $this->activeRoleName() === $roleName;
    }

    protected function resolveDefaultReviewer(FlujoAprobacionEtapa $stage): ?User
    {
        if ($stage->usuario_responsable_id && ! $stage->requiere_asignacion) {
            return $stage->usuarioResponsable;
        }

        if (! $stage->rolRevisor?->name) {
            return null;
        }

        if ($stage->rol_revisor_id) {
            $preferredReviewer = User::role($stage->rolRevisor->name)
                ->where('active_role_id', $stage->rol_revisor_id)
                ->orderBy('name')
                ->first();

            if ($preferredReviewer) {
                return $preferredReviewer;
            }
        }

        return User::role($stage->rolRevisor->name)
            ->orderBy('name')
            ->first();
    }

    protected function userCanSeeStage(ProgramaRevision $stage): bool
    {
        $programa = $stage->programa;

        if (! $programa) {
            return false;
        }

        if (($programa->estado_flujo ?? null) === 'APROBADO') {
            return $this->userParticipatedInStage($stage);
        }

        if ($programa->etapaActual()?->id !== $stage->id) {
            return false;
        }

        if (! $this->userHasStageRole($stage)) {
            return false;
        }

        if ($stage->asignado_usuario_id) {
            return (int) $stage->asignado_usuario_id === Auth::id();
        }

        if ($stage->responsable_usuario_id) {
            return (int) $stage->responsable_usuario_id === Auth::id();
        }

        return true;
    }

    protected function userParticipatedInStage(ProgramaRevision $stage): bool
    {
        $userId = Auth::id();

        return in_array($userId, array_filter([
            $stage->asignado_usuario_id,
            $stage->responsable_usuario_id,
            $stage->decidido_por_usuario_id,
        ]), true) || $this->userHasStageRole($stage);
    }

    protected function activeRoleName(): ?string
    {
        return Auth::user()?->activeRole?->name;
    }

    protected function syncCurrentVersionRecord(ProgramaCertificacion $programa, string $estado, ?string $notas = null): void
    {
        $snapshot = $programa->buildVersionSnapshot();

        if ($estado === 'APROBADO') {
            $programa->versiones()->update(['vigente' => false]);
        }

        $programa->versiones()->updateOrCreate(
            ['numero_version' => $programa->version_actual],
            [
                'estado' => $estado,
                'vigente' => $estado === 'APROBADO',
                'publicado_en' => $estado === 'APROBADO' ? now() : null,
                'publicado_por_usuario_id' => $estado === 'APROBADO' ? Auth::id() : null,
                'notas' => $notas,
                'datos_programa' => $snapshot['programa'],
                'centros_facultad' => $snapshot['centros_facultad'],
                'asignaturas' => $snapshot['asignaturas'],
            ]
        );
    }
}

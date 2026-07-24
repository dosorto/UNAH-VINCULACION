<?php

namespace App\Livewire\DAFT\Programas;

use App\Models\DAFT\ProgramaRevision;
use App\Models\User;
use App\Services\DAFT\ProgramaWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ListBandejaRevision extends Component
{
    public array $observaciones = [];

    public array $reviewerSelections = [];

    public function assignToMe(int $revisionId): void
    {
        try {
            app(ProgramaWorkflowService::class)->asignarAlUsuario(
                ProgramaRevision::findOrFail($revisionId),
                Auth::user()
            );
        } catch (\DomainException $exception) {
            session()->flash('programas_warning', $exception->getMessage());

            return;
        }

        session()->flash('programas_status', 'Revision asignada correctamente.');
        $this->dispatch('daft-review-assigned');
    }

    public function assignReviewer(int $revisionId): void
    {
        $validated = $this->validate([
            'reviewerSelections.'.$revisionId => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
        ], [
            'reviewerSelections.*.required' => 'Selecciona el responsable de la etapa.',
            'reviewerSelections.*.exists' => 'El responsable seleccionado ya no está disponible.',
        ]);
        $reviewerId = (int) data_get($validated, 'reviewerSelections.'.$revisionId);
        $reviewer = User::findOrFail($reviewerId);

        try {
            app(ProgramaWorkflowService::class)->asignarAUsuario(
                ProgramaRevision::findOrFail($revisionId),
                Auth::user(),
                $reviewer
            );
        } catch (\DomainException $exception) {
            $this->addError('reviewerSelections.'.$revisionId, $exception->getMessage());

            return;
        }

        unset($this->reviewerSelections[$revisionId]);
        session()->flash('programas_status', 'Responsable asignado: '.$reviewer->name.'.');
        if ($reviewer->is(Auth::user())) {
            $this->dispatch('daft-review-assigned');
        }
    }

    public function approveRevision(int $revisionId): void
    {
        try {
            app(ProgramaWorkflowService::class)->aprobar(
                ProgramaRevision::findOrFail($revisionId),
                Auth::user(),
                $this->observaciones[$revisionId] ?? null
            );
        } catch (\DomainException $exception) {
            session()->flash('programas_warning', $exception->getMessage());

            return;
        }

        unset($this->observaciones[$revisionId]);
        session()->flash('programas_status', 'Etapa aprobada correctamente.');
    }

    public function rejectRevision(int $revisionId): void
    {
        $observacion = trim((string) ($this->observaciones[$revisionId] ?? ''));

        if ($observacion === '') {
            $this->addError('observaciones.'.$revisionId, 'Debe indicar las observaciones para subsanacion.');

            return;
        }
        try {
            app(ProgramaWorkflowService::class)->rechazar(
                ProgramaRevision::findOrFail($revisionId),
                Auth::user(),
                $observacion
            );
        } catch (\DomainException $exception) {
            session()->flash('programas_warning', $exception->getMessage());

            return;
        }

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
        $reviewerCandidatesByRevision = $programasPendientes
            ->where('estado', 'PENDIENTE_ASIGNACION')
            ->mapWithKeys(fn (ProgramaRevision $revision): array => [
                $revision->id => app(ProgramaWorkflowService::class)
                    ->usuariosElegiblesParaRevision($revision),
            ]);

        return view('livewire.daft.programas.list-bandeja-revision', compact('programasPendientes', 'programasEnProceso', 'programasAprobados', 'pendingNotice', 'reviewerCandidatesByRevision'))
            ->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    protected function canActOnStage(ProgramaRevision $stage): bool
    {
        return app(ProgramaWorkflowService::class)->usuarioPuedeActuar($stage, Auth::user());
    }

    protected function userHasStageRole(ProgramaRevision $stage): bool
    {
        return app(ProgramaWorkflowService::class)->usuarioTieneRolDeEtapa($stage, Auth::user());
    }

    protected function userCanSeeStage(ProgramaRevision $stage): bool
    {
        return app(ProgramaWorkflowService::class)->usuarioPuedeVer($stage, Auth::user());
    }

    protected function userParticipatedInStage(ProgramaRevision $stage): bool
    {
        return app(ProgramaWorkflowService::class)->usuarioParticipoEnEtapa($stage, Auth::user());
    }

    protected function activeRoleName(): ?string
    {
        return Auth::user()?->activeRole?->name;
    }
}

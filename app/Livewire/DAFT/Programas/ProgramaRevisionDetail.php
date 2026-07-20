<?php

namespace App\Livewire\DAFT\Programas;

use App\Models\DAFT\ProgramaAsignatura;
use App\Models\DAFT\ProgramaRevision;
use App\Services\DAFT\ProgramaWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgramaRevisionDetail extends Component
{
    public int $revisionId;

    public string $observaciones = '';

    public ?int $selectedProgramaAsignaturaId = null;

    public function mount(ProgramaRevision $revision): void
    {
        $this->assertVisible($revision);
        $this->revisionId = $revision->id;
        $this->selectedProgramaAsignaturaId = $revision->programa
            ?->asignaturasPrograma()
            ->whereHas('asignatura', fn ($query) => $query->whereNotNull('ruta_documento_descripcion_minima'))
            ->value('id');
    }

    public function selectDocument(int $programaAsignaturaId): void
    {
        $revision = $this->revision();
        $this->programSubject($revision, $programaAsignaturaId);
        $this->selectedProgramaAsignaturaId = $programaAsignaturaId;
    }

    public function downloadDocument(int $programaAsignaturaId): StreamedResponse
    {
        $revision = $this->revision();
        $programaAsignatura = $this->programSubject($revision, $programaAsignaturaId);
        $path = $programaAsignatura->asignatura?->ruta_documento_descripcion_minima;

        abort_if(! $path || ! Storage::disk('public')->exists($path), 404, 'El documento no está disponible.');

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';
        $filename = Str::slug($programaAsignatura->asignatura->codigo.'-'.$programaAsignatura->asignatura->nombre).'.'.$extension;

        return Storage::disk('public')->download($path, $filename);
    }

    public function assignToMe(): void
    {
        try {
            app(ProgramaWorkflowService::class)->asignarAlUsuario($this->revision(), Auth::user());
        } catch (\DomainException $exception) {
            session()->flash('programas_warning', $exception->getMessage());

            return;
        }

        session()->flash('programas_status', 'Revisión asignada correctamente. Ya puedes evaluar el programa.');
    }

    public function approveRevision(): void
    {
        try {
            app(ProgramaWorkflowService::class)->aprobar(
                $this->revision(),
                Auth::user(),
                filled($this->observaciones) ? trim($this->observaciones) : null
            );
        } catch (\DomainException $exception) {
            session()->flash('programas_warning', $exception->getMessage());

            return;
        }

        session()->flash('programas_status', 'Etapa aprobada correctamente.');
        $this->redirectRoute('daft.bandeja-revision', navigate: true);
    }

    public function rejectRevision(): void
    {
        $this->validate([
            'observaciones' => ['required', 'string', 'min:5', 'max:5000'],
        ], [
            'observaciones.required' => 'Indica las correcciones que debe realizar el responsable del programa.',
            'observaciones.min' => 'Describe la subsanación con al menos 5 caracteres.',
        ]);

        try {
            app(ProgramaWorkflowService::class)->rechazar(
                $this->revision(),
                Auth::user(),
                trim($this->observaciones)
            );
        } catch (\DomainException $exception) {
            session()->flash('programas_warning', $exception->getMessage());

            return;
        }

        session()->flash('programas_status', 'Programa enviado a subsanación.');
        $this->redirectRoute('daft.bandeja-revision', navigate: true);
    }

    public function render(): View
    {
        $revision = $this->revision()->load([
            'programa.centroFacultad',
            'programa.tipoPrograma',
            'programa.creadoPor',
            'programa.centrosPrograma.centroFacultad',
            'programa.asignaturasPrograma.asignatura.prerrequisitos',
            'programa.versiones',
            'programa.revisiones.flujoEtapa.rolRevisor',
            'programa.revisiones.asignadoUsuario',
            'programa.revisiones.responsableUsuario',
            'programa.revisiones.decididoPorUsuario',
            'flujoEtapa.rolRevisor',
            'asignadoUsuario',
            'responsableUsuario',
        ]);
        $programa = $revision->programa;
        $workflow = app(ProgramaWorkflowService::class);
        $selectedAssignment = $programa?->asignaturasPrograma
            ->firstWhere('id', $this->selectedProgramaAsignaturaId);
        $documentPath = $selectedAssignment?->asignatura?->ruta_documento_descripcion_minima;
        $isCurrentStage = $programa?->etapaActual()?->id === $revision->id;

        return view('livewire.daft.programas.programa-revision-detail', [
            'revision' => $revision,
            'programa' => $programa,
            'canAct' => $isCurrentStage
                && in_array($revision->estado, ['PENDIENTE', 'ASIGNADO', 'EN_PROCESO'], true)
                && $workflow->usuarioPuedeActuar($revision, Auth::user()),
            'canTake' => $isCurrentStage
                && $revision->estado === 'PENDIENTE_ASIGNACION'
                && $workflow->usuarioTieneRolDeEtapa($revision, Auth::user()),
            'currentStages' => $programa?->revisiones
                ->where('revision_ciclo', $programa->revision_ciclo)
                ->sortBy('orden')
                ->values() ?? collect(),
            'activityFeed' => $this->activityFeed($revision),
            'selectedAssignment' => $selectedAssignment,
            'documentUrl' => $documentPath && Storage::disk('public')->exists($documentPath)
                ? Storage::disk('public')->url($documentPath)
                : null,
            'documentIsPdf' => strtolower(pathinfo((string) $documentPath, PATHINFO_EXTENSION)) === 'pdf',
        ])->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    private function revision(): ProgramaRevision
    {
        $revision = ProgramaRevision::with(['programa', 'flujoEtapa.rolRevisor'])->findOrFail($this->revisionId);
        $this->assertVisible($revision);

        return $revision;
    }

    private function assertVisible(ProgramaRevision $revision): void
    {
        abort_unless(app(ProgramaWorkflowService::class)->usuarioPuedeVer($revision, Auth::user()), 403);
    }

    private function programSubject(ProgramaRevision $revision, int $programaAsignaturaId): ProgramaAsignatura
    {
        return ProgramaAsignatura::query()
            ->with('asignatura')
            ->where('programa_certificacion_id', $revision->programa_certificacion_id)
            ->findOrFail($programaAsignaturaId);
    }

    private function activityFeed(ProgramaRevision $revision): array
    {
        $programa = $revision->programa;
        $feed = [[
            'title' => 'Programa creado',
            'description' => 'Creado por '.($programa?->creadoPor?->name ?? 'usuario institucional').'.',
            'at' => $programa?->created_at,
            'tone' => 'sky',
        ]];

        if ($programa?->enviado_revision_en) {
            $feed[] = [
                'title' => 'Enviado a revisión',
                'description' => 'Ciclo '.$programa->revision_ciclo.' iniciado en la etapa '.$revision->etapa_nombre.'.',
                'at' => $programa->enviado_revision_en,
                'tone' => 'emerald',
            ];
        }

        foreach ($programa?->revisiones->whereIn('estado', ['APROBADO', 'RECHAZADO']) ?? [] as $decision) {
            $feed[] = [
                'title' => $decision->estado === 'APROBADO' ? 'Etapa aprobada' : 'Enviado a subsanación',
                'description' => $decision->etapa_nombre.' · '.($decision->decididoPorUsuario?->name ?? 'Revisor').(filled($decision->observaciones) ? ' · '.$decision->observaciones : ''),
                'at' => $decision->firmado_en ?? $decision->updated_at,
                'tone' => $decision->estado === 'APROBADO' ? 'emerald' : 'rose',
            ];
        }

        return collect($feed)->sortByDesc('at')->values()->all();
    }
}

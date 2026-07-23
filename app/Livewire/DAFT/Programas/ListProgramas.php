<?php

namespace App\Livewire\DAFT\Programas;

use App\Models\DAFT\ProgramaCertificacion;
use App\Models\DAFT\TipoPrograma;
use App\Services\DAFT\ProgramaWorkflowService;
use App\Models\UnidadAcademica\FacultadCentro;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ListProgramas extends Component
{
    use WithPagination;

    public string $search = '';
    public string $estado = '';
    public bool $showTrashed = false;
    public bool $formModal = false;
    public ?int $editingProgramaId = null;

    public array $programaForm = [
        'codigo' => '',
        'nombre' => '',
        'tipo_programa_id' => null,
        'centro_facultad_id' => null,
        'descripcion' => '',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEstado(): void
    {
        $this->resetPage();
    }

    public function updatingShowTrashed(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->formModal = true;
    }

    public function openEdit(int $programaId): void
    {
        $programa = ProgramaCertificacion::withTrashed()
            ->findOrFail($programaId);

        $this->editingProgramaId = $programa->id;
        $this->programaForm = [
            'codigo' => $programa->codigo,
            'nombre' => $programa->nombre,
            'tipo_programa_id' => $programa->tipo_programa_id,
            'centro_facultad_id' => $programa->centro_facultad_id,
            'descripcion' => $programa->descripcion ?? '',
        ];
        $this->formModal = true;
    }

    public function savePrograma(): void
    {
        $validated = $this->validate([
            'programaForm.codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('programas_certificacion', 'codigo')->ignore($this->editingProgramaId),
            ],
            'programaForm.nombre' => ['required', 'string', 'max:200'],
            'programaForm.tipo_programa_id' => ['required', 'exists:tipos_programa,id'],
            'programaForm.centro_facultad_id' => ['required', 'exists:centro_facultad,id'],
            'programaForm.descripcion' => ['nullable', 'string'],
        ]);

        $payload = $validated['programaForm'];
        $payload['modificado_por_usuario_id'] = Auth::id();

        if (! $this->editingProgramaId) {
            $payload['creado_por_usuario_id'] = Auth::id();
            $payload['horas_maximas_programa'] = 0;
            $payload['version_actual'] = 1;
            $payload['estado'] = 1;
            $payload['estado_flujo'] = 'ELABORACION';
        }

        ProgramaCertificacion::updateOrCreate(
            ['id' => $this->editingProgramaId],
            $payload
        );

        $this->resetForm();
        session()->flash('programas_status', 'Programa guardado correctamente.');
    }

    public function deletePrograma(int $programaId): void
    {
        ProgramaCertificacion::findOrFail($programaId)->delete();
        session()->flash('programas_status', 'Programa eliminado.');
    }

    public function restorePrograma(int $programaId): void
    {
        ProgramaCertificacion::withTrashed()->findOrFail($programaId)->restore();
        session()->flash('programas_status', 'Programa restaurado.');
    }

    public function sendToReview(int $programaId): void
    {
        try {
            app(ProgramaWorkflowService::class)->enviarARevision(
                ProgramaCertificacion::findOrFail($programaId),
                Auth::user()
            );
        } catch (\DomainException $exception) {
            session()->flash('programas_status', $exception->getMessage());
            return;
        }

        session()->flash('programas_status', 'Programa enviado a revision.');
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        $baseQuery = ProgramaCertificacion::query()
            ->with(['centroFacultad', 'tipoPrograma'])
            ->when($this->showTrashed, fn ($q) => $q->withTrashed())
            ->when($this->search, fn ($q) => $q->where(fn ($query) => $query
                ->where('nombre', 'like', '%' . $this->search . '%')
                ->orWhere('codigo', 'like', '%' . $this->search . '%')
            ))
            ->when($this->estado, fn ($q) => $q->where('estado_flujo', $this->estado))
            ->orderBy('nombre');

        $records = $baseQuery->paginate(10);

        $metricas = [
            'programas' => ProgramaCertificacion::count(),
            'borradores' => ProgramaCertificacion::whereIn('estado_flujo', ['BORRADOR', 'ELABORACION'])->count(),
            'revision' => ProgramaCertificacion::where('estado_flujo', 'EN_REVISION')->count(),
        ];

        return view('livewire.daft.programas.list-programas', [
            'records' => $records,
            'metricas' => $metricas,
            'tiposPrograma' => TipoPrograma::where('activo', true)->orderBy('nombre')->get(),
            'centrosFacultad' => FacultadCentro::orderBy('nombre')->get(),
            'estadosFlujo' => $this->estadosFlujo(),
        ])
            ->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    protected function resetForm(): void
    {
        $this->resetErrorBag();
        $this->editingProgramaId = null;
        $this->formModal = false;
        $this->programaForm = [
            'codigo' => '',
            'nombre' => '',
            'tipo_programa_id' => null,
            'centro_facultad_id' => null,
            'descripcion' => '',
        ];
    }

    protected function estadosFlujo(): array
    {
        return [
            'ELABORACION' => 'En elaboracion',
            'SUBSANACION' => 'Subsanacion',
            'EN_REVISION' => 'En revision',
            'APROBADO' => 'Aprobado',
            'RECHAZADO' => 'Rechazado',
        ];
    }

}

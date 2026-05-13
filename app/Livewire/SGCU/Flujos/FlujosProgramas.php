<?php

namespace App\Livewire\SGCU\Flujos;

use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\SGCU\TipoPrograma;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FlujosProgramas extends Component
{
    public ?int $selectedTipoProgramaId = null;
    public ?int $workflowId = null;

    public array $workflow = [
        'codigo' => '',
        'nombre' => '',
        'proceso' => 'PROGRAMA',
        'descripcion' => '',
        'activo' => true,
    ];

    public array $stages = [];

    public function mount(): void
    {
        $this->selectedTipoProgramaId = TipoPrograma::orderBy('nombre')->value('id');
        $this->loadWorkflowForSelectedTipo();
    }

    public function selectTipoPrograma(int $tipoId): void
    {
        $this->selectedTipoProgramaId = $tipoId;
        $this->loadWorkflowForSelectedTipo();
    }

    public function addStage(): void
    {
        $this->stages[] = $this->blankStage(count($this->stages) + 1);
    }

    public function removeStage(int $index): void
    {
        unset($this->stages[$index]);
        $this->stages = array_values($this->stages);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'workflow.codigo' => ['required', 'string', 'max:80'],
            'workflow.nombre' => ['required', 'string', 'max:180'],
            'workflow.descripcion' => ['nullable', 'string'],
            'workflow.activo' => ['boolean'],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.codigo' => ['required', 'string', 'max:80'],
            'stages.*.nombre' => ['required', 'string', 'max:180'],
            'stages.*.cargo_firma_id' => ['required', 'exists:cargo_firma,id'],
            'stages.*.requiere_asignacion' => ['boolean'],
            'stages.*.emisor_define_destinatario' => ['boolean'],
            'stages.*.activo' => ['boolean'],
        ]);

        if (! $this->selectedTipoProgramaId) {
            $this->addError('workflow.nombre', 'Seleccione un tipo de programa.');
            return;
        }

        DB::transaction(function () use ($validated) {
            $flow = FlujoAprobacion::updateOrCreate(
                ['id' => $this->workflowId],
                [
                    'codigo' => strtoupper(trim($validated['workflow']['codigo'])),
                    'nombre' => $validated['workflow']['nombre'],
                    'proceso' => 'PROGRAMA',
                    'descripcion' => $validated['workflow']['descripcion'] ?? null,
                    'activo' => $validated['workflow']['activo'] ?? true,
                    'tipo_programa_id' => $this->selectedTipoProgramaId,
                ]
            );

            $flow->etapas()->delete();

            foreach (array_values($validated['stages']) as $index => $stage) {
                $flow->etapas()->create([
                    'orden' => $index + 1,
                    'codigo' => strtoupper(trim($stage['codigo'])),
                    'nombre' => $stage['nombre'],
                    'cargo_firma_id' => $stage['cargo_firma_id'],
                    'requiere_asignacion' => (bool) ($stage['requiere_asignacion'] ?? false),
                    'emisor_define_destinatario' => (bool) ($stage['emisor_define_destinatario'] ?? false),
                    'activo' => $stage['activo'] ?? true,
                ]);
            }

            $this->workflowId = $flow->id;
        });

        session()->flash('status', 'Flujo de programa guardado correctamente.');
    }

    public function render(): View
    {
        $tiposPrograma = TipoPrograma::with('flujoAprobacion')->orderBy('nombre')->get();
        $selectedTipoPrograma = $tiposPrograma->firstWhere('id', $this->selectedTipoProgramaId);

        $cargos = CargoFirma::query()
            ->join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
            ->where('cargo_firma.descripcion', 'Proyecto')
            ->orderBy('tipo_cargo_firma.nombre')
            ->select('cargo_firma.*', 'tipo_cargo_firma.nombre as cargo_nombre')
            ->get();

        return view('livewire.sgcu.flujos.flujos-programas', compact('tiposPrograma', 'cargos', 'selectedTipoPrograma'))
            ->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    protected function blankStage(int $order): array
    {
        return [
            'codigo' => 'ETAPA_' . $order,
            'nombre' => 'Etapa ' . $order,
            'cargo_firma_id' => null,
            'requiere_asignacion' => true,
            'emisor_define_destinatario' => false,
            'activo' => true,
        ];
    }

    protected function resetWorkflowForm(): void
    {
        $this->workflowId = null;
        $this->workflow = [
            'codigo' => 'PROGRAMA_DEFAULT',
            'nombre' => 'Flujo de aprobacion de programas',
            'proceso' => 'PROGRAMA',
            'descripcion' => '',
            'activo' => true,
        ];
        $this->stages = [$this->blankStage(1)];
    }

    protected function loadWorkflowForSelectedTipo(): void
    {
        if (! $this->selectedTipoProgramaId) {
            $this->resetWorkflowForm();
            return;
        }

        $flow = FlujoAprobacion::with('etapas')
            ->where('tipo_programa_id', $this->selectedTipoProgramaId)
            ->first();

        if (! $flow) {
            $this->resetWorkflowForm();
            return;
        }

        $this->workflowId = $flow->id;
        $this->workflow = [
            'codigo' => $flow->codigo,
            'nombre' => $flow->nombre,
            'proceso' => $flow->proceso,
            'descripcion' => $flow->descripcion ?? '',
            'activo' => $flow->activo,
        ];
        $this->stages = $flow->etapas
            ->sortBy('orden')
            ->values()
            ->map(fn ($stage) => [
                'codigo' => $stage->codigo,
                'nombre' => $stage->nombre,
                'cargo_firma_id' => $stage->cargo_firma_id,
                'requiere_asignacion' => (bool) $stage->requiere_asignacion,
                'emisor_define_destinatario' => (bool) $stage->emisor_define_destinatario,
                'activo' => (bool) $stage->activo,
            ])
            ->toArray();
    }
}

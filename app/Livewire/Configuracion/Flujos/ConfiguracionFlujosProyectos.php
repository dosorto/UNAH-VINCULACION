<?php

namespace App\Livewire\Configuracion\Flujos;

use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ConfiguracionFlujosProyectos extends Component
{
    public ?int $selectedActionId = null;
    public ?int $selectedSubactionId = null;
    public ?int $selectedWorkflowId = null;
    public ?int $workflowId = null;

    public array $workflow = [
        'codigo' => '',
        'nombre' => '',
        'proceso' => 'PROYECTO',
        'descripcion' => '',
        'activo' => true,
    ];

    public array $stages = [];

    public function mount(): void
    {
        $firstActionId = DB::table('vinculacion_tipos_accion')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->value('id');

        if ($firstActionId) {
            $this->selectedActionId = $firstActionId;
        }

        $this->loadFirstWorkflow();
    }

    public function selectAction(int $actionId): void
    {
        $this->selectedActionId = $actionId;
        $this->selectedSubactionId = null;
        $this->loadFirstWorkflow();
    }

    public function selectSubaction(int $subactionId): void
    {
        $this->selectedSubactionId = $subactionId;
        $this->selectedWorkflowId = null;
        $this->resetWorkflowForm();
    }

    public function selectWorkflow(int $workflowId): void
    {
        $flow = FlujoAprobacion::query()
            ->where('proceso', 'PROYECTO')
            ->findOrFail($workflowId);

        $this->selectedWorkflowId = $flow->id;
        $this->selectedActionId = $flow->tipo_accion_id;
        $this->selectedSubactionId = $flow->tipo_accion_id;
        $this->loadWorkflow($flow);
    }

    public function newWorkflow(): void
    {
        if (! $this->selectedSubactionId) {
            $this->addError('workflow.nombre', 'Seleccione una subaccion para crear un flujo.');
            return;
        }

        $this->selectedWorkflowId = null;
        $this->resetWorkflowForm();
    }

    public function addStage(): void
    {
        $this->stages[] = $this->blankStage(count($this->stages) + 1);
        $this->normalizeStageCodes();
    }

    public function removeStage(int $index): void
    {
        unset($this->stages[$index]);
        $this->stages = array_values($this->stages);

        if ($this->stages === []) {
            $this->stages[] = $this->blankStage(1);
        }

        $this->normalizeStageCodes();
    }

    public function moveStageUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->stages[$index], $this->stages[$index - 1])) {
            return;
        }

        [$this->stages[$index - 1], $this->stages[$index]] = [$this->stages[$index], $this->stages[$index - 1]];
        $this->stages = array_values($this->stages);
        $this->normalizeStageCodes();
    }

    public function moveStageDown(int $index): void
    {
        if (! isset($this->stages[$index], $this->stages[$index + 1])) {
            return;
        }

        [$this->stages[$index + 1], $this->stages[$index]] = [$this->stages[$index], $this->stages[$index + 1]];
        $this->stages = array_values($this->stages);
        $this->normalizeStageCodes();
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

        if (! $this->selectedSubactionId) {
            $this->addError('workflow.nombre', 'Seleccione una subaccion para el flujo.');
            return;
        }

        $flow = DB::transaction(function () use ($validated) {
            $flow = FlujoAprobacion::updateOrCreate(
                ['id' => $this->workflowId],
                [
                    'codigo' => strtoupper(trim($validated['workflow']['codigo'])),
                    'nombre' => $validated['workflow']['nombre'],
                    'proceso' => 'PROYECTO',
                    'descripcion' => $validated['workflow']['descripcion'] ?? null,
                    'activo' => $validated['workflow']['activo'] ?? true,
                    'tipo_accion_id' => $this->selectedSubactionId,
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

            return $flow;
        });

        $this->selectedWorkflowId = $flow->id;
        $this->loadWorkflow($flow->fresh('etapas'));
        session()->flash('status', 'Flujo de proyectos guardado correctamente.');
    }

    public function render(): View
    {
        $flows = FlujoAprobacion::query()
            ->where('proceso', 'PROYECTO')
            ->when($this->selectedSubactionId, fn ($query) => $query->where('tipo_accion_id', $this->selectedSubactionId))
            ->orderBy('nombre')
            ->get();

        $actions = DB::table('vinculacion_tipos_accion')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $cargos = CargoFirma::query()
            ->join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
            ->where('cargo_firma.descripcion', 'Proyecto')
            ->orderBy('tipo_cargo_firma.nombre')
            ->select('cargo_firma.*', 'tipo_cargo_firma.nombre as cargo_nombre')
            ->get();

        return view('livewire.configuracion.flujos.configuracion-flujos-proyectos', [
            'flows' => $flows,
            'cargos' => $cargos,
            'actions' => $actions,
        ])->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    protected function loadFirstWorkflow(): void
    {
        if (! $this->selectedSubactionId) {
            $this->selectedWorkflowId = null;
            $this->resetWorkflowForm();
            return;
        }

        $flow = FlujoAprobacion::query()
            ->where('proceso', 'PROYECTO')
            ->where('tipo_accion_id', $this->selectedSubactionId)
            ->orderBy('nombre')
            ->first();

        if ($flow) {
            $this->selectedWorkflowId = $flow->id;
            $this->loadWorkflow($flow);
            return;
        }

        $this->selectedWorkflowId = null;
        $this->resetWorkflowForm();
    }

    protected function loadWorkflow(FlujoAprobacion $flow): void
    {
        $this->workflowId = $flow->id;
        $this->workflow = [
            'codigo' => $flow->codigo,
            'nombre' => $flow->nombre,
            'proceso' => $flow->proceso,
            'descripcion' => $flow->descripcion ?? '',
            'activo' => (bool) $flow->activo,
        ];

        $this->stages = $flow->etapas
            ->sortBy('orden')
            ->map(fn ($stage) => [
                'codigo' => $stage->codigo,
                'nombre' => $stage->nombre,
                'cargo_firma_id' => (string) $stage->cargo_firma_id,
                'requiere_asignacion' => (bool) $stage->requiere_asignacion,
                'emisor_define_destinatario' => (bool) $stage->emisor_define_destinatario,
                'activo' => (bool) $stage->activo,
            ])
            ->values()
            ->all();

        if ($this->stages === []) {
            $this->stages[] = $this->blankStage(1);
        }
    }

    protected function resetWorkflowForm(): void
    {
        $this->workflowId = null;
        $this->workflow = [
            'codigo' => 'PROYECTO_DEFAULT',
            'nombre' => 'Flujo de aprobacion de proyectos',
            'proceso' => 'PROYECTO',
            'descripcion' => 'Flujo configurable para aprobacion de proyectos.',
            'activo' => true,
        ];
        $this->stages = [$this->blankStage(1)];
    }

    protected function blankStage(int $order): array
    {
        return [
            'codigo' => 'ETAPA_'.$order,
            'nombre' => '',
            'cargo_firma_id' => '',
            'requiere_asignacion' => true,
            'emisor_define_destinatario' => false,
            'activo' => true,
        ];
    }

    protected function normalizeStageCodes(): void
    {
        foreach ($this->stages as $index => $stage) {
            $codigo = $stage['codigo'] ?? '';
            if ($codigo === '') {
                $this->stages[$index]['codigo'] = 'ETAPA_'.($index + 1);
            }
        }
    }
}

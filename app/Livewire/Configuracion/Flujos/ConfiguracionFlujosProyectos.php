<?php

namespace App\Livewire\Configuracion\Flujos;

use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\SGCU\TipoPrograma;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class ConfiguracionFlujosProyectos extends Component
{
    protected array $cargoFirmaCache = [];

    public string $activeFlowTab = 'proyectos';

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

    public ?int $programSelectedTipoProgramaId = null;
    public ?int $programWorkflowId = null;

    public array $programWorkflow = [
        'codigo' => '',
        'nombre' => '',
        'proceso' => 'PROGRAMA',
        'descripcion' => '',
        'activo' => true,
    ];

    public array $programStages = [];

    public function mount(): void
    {
        $firstActionId = DB::table('vinculacion_tipos_accion')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->value('id');

        if ($firstActionId) {
            $this->selectedActionId = $firstActionId;
            $this->selectedSubactionId = $firstActionId;
        }

        $this->loadFirstWorkflow();
        $this->programSelectedTipoProgramaId = TipoPrograma::orderBy('nombre')->value('id');
        $this->loadProgramWorkflowForSelectedTipo();
    }

    public function showProjectFlows(): void
    {
        $this->activeFlowTab = 'proyectos';
        $this->resetErrorBag();
    }

    public function showProgramFlows(): void
    {
        $this->activeFlowTab = 'programas';
        $this->resetErrorBag();
    }

    public function selectAction(int $actionId): void
    {
        $this->selectedActionId = $actionId;
        $this->selectedSubactionId = $actionId;
        $this->loadWorkflowForSelectedSubaction();
    }

    public function selectSubaction(int $subactionId): void
    {
        $this->selectedSubactionId = $subactionId;
        $this->selectedWorkflowId = null;
        $this->loadWorkflowForSelectedSubaction();
    }

    public function selectWorkflow(int $workflowId): void
    {
        $flow = FlujoAprobacion::with('etapas')->findOrFail($workflowId);

        $this->selectedWorkflowId = $flow->id;
        $this->selectedActionId = $flow->tipo_accion_id;
        $this->selectedSubactionId = $flow->tipo_accion_id;
        $this->loadWorkflow($flow);
    }

    public function newWorkflow(): void
    {
        $this->resetWorkflowForm();
        session()->flash('status', 'Nuevo flujo para la accion seleccionada.');
    }

    public function addStage(): void
    {
        if ($this->activeFlowTab === 'programas') {
            $this->programStages[] = $this->blankStage(count($this->programStages) + 1);
            $this->normalizeProgramStageCodes();
            return;
        }

        $this->stages[] = $this->blankStage(
            count($this->stages) + 1,
            $this->generateUniqueStageCode($this->stages, $this->workflowId)
        );
        $this->normalizeStageCodes();
    }

    public function updated(string $property, mixed $value): void
    {
        if (preg_match('/^stages\.(\d+)\.rol_revisor_id$/', $property, $matches)) {
            $this->clearInvalidResponsible($this->stages, (int) $matches[1]);
            return;
        }

        if (preg_match('/^programStages\.(\d+)\.rol_revisor_id$/', $property, $matches)) {
            $this->clearInvalidResponsible($this->programStages, (int) $matches[1]);
            return;
        }

        if (preg_match('/^stages\.(\d+)\.(requiere_asignacion|emisor_define_destinatario)$/', $property, $matches)) {
            $this->syncResponsibleAvailability($this->stages, (int) $matches[1]);
            return;
        }

        if (preg_match('/^programStages\.(\d+)\.(requiere_asignacion|emisor_define_destinatario)$/', $property, $matches)) {
            $this->syncResponsibleAvailability($this->programStages, (int) $matches[1]);
        }
    }

    public function removeStage(int $index): void
    {
        if ($this->activeFlowTab === 'programas') {
            unset($this->programStages[$index]);
            $this->programStages = array_values($this->programStages);

            if ($this->programStages === []) {
                $this->programStages[] = $this->blankStage(1);
            }

            $this->normalizeProgramStageCodes();
            return;
        }

        unset($this->stages[$index]);
        $this->stages = array_values($this->stages);

        if ($this->stages === []) {
            $this->stages[] = $this->blankStage(1);
        }

        $this->normalizeStageCodes();
    }

    public function moveStageUp(int $index): void
    {
        if ($this->activeFlowTab === 'programas') {
            if ($index <= 0 || ! isset($this->programStages[$index], $this->programStages[$index - 1])) {
                return;
            }

            [$this->programStages[$index - 1], $this->programStages[$index]] = [$this->programStages[$index], $this->programStages[$index - 1]];
            $this->programStages = array_values($this->programStages);
            $this->normalizeProgramStageCodes();
            return;
        }

        if ($index <= 0 || ! isset($this->stages[$index], $this->stages[$index - 1])) {
            return;
        }

        [$this->stages[$index - 1], $this->stages[$index]] = [$this->stages[$index], $this->stages[$index - 1]];
        $this->stages = array_values($this->stages);
        $this->normalizeStageCodes();
    }

    public function moveStageDown(int $index): void
    {
        if ($this->activeFlowTab === 'programas') {
            if (! isset($this->programStages[$index], $this->programStages[$index + 1])) {
                return;
            }

            [$this->programStages[$index + 1], $this->programStages[$index]] = [$this->programStages[$index], $this->programStages[$index + 1]];
            $this->programStages = array_values($this->programStages);
            $this->normalizeProgramStageCodes();
            return;
        }

        if (! isset($this->stages[$index], $this->stages[$index + 1])) {
            return;
        }

        [$this->stages[$index + 1], $this->stages[$index]] = [$this->stages[$index], $this->stages[$index + 1]];
        $this->stages = array_values($this->stages);
        $this->normalizeStageCodes();
    }

    public function save(): void
    {
        if ($this->activeFlowTab === 'programas') {
            $this->saveProgramFlow();
            return;
        }

        if (! $this->selectedSubactionId) {
            $this->addError('workflow.nombre', 'Seleccione una subaccion para el flujo.');
            return;
        }

        $this->workflow['codigo'] = $this->workflow['codigo']
            ?: $this->generateProjectFlowCode($this->selectedSubactionId);
        $this->normalizeStageCodes();

        $validated = $this->validate([
            'workflow.codigo' => ['required', 'string', 'max:80', Rule::unique('flujos_aprobacion', 'codigo')->ignore($this->workflowId)],
            'workflow.nombre' => ['required', 'string', 'max:180'],
            'workflow.descripcion' => ['nullable', 'string'],
            'workflow.activo' => ['boolean'],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.id' => ['nullable', 'integer', 'exists:flujos_aprobacion_etapas,id'],
            'stages.*.codigo' => ['required', 'string', 'max:80'],
            'stages.*.aplica_inscripcion' => ['boolean'],
            'stages.*.aplica_informe_intermedio' => ['boolean'],
            'stages.*.aplica_cierre_proyecto' => ['boolean'],
            'stages.*.nombre' => ['required', 'string', 'max:180'],
            'stages.*.tipo_etapa' => ['required', 'in:FORMULACION,REVISION,APROBACION'],
            'stages.*.rol_revisor_id' => ['nullable', 'exists:roles,id'],
            'stages.*.usuario_responsable_id' => ['nullable', 'exists:users,id'],
            'stages.*.cargo_firma_id' => ['nullable', 'exists:cargo_firma,id'],
            'stages.*.requiere_asignacion' => ['boolean'],
            'stages.*.emisor_define_destinatario' => ['boolean'],
            'stages.*.activo' => ['boolean'],
        ]);

        $validated['stages'] = $this->prepareStagesForSave($validated['stages'], 'REVISION', $this->workflowId);

        $flow = DB::transaction(function () use ($validated) {
            $flow = FlujoAprobacion::updateOrCreate(
                $this->workflowId
                    ? ['id' => $this->workflowId]
                    : ['proceso' => 'PROYECTO', 'tipo_accion_id' => $this->selectedSubactionId],
                [
                    'codigo' => strtoupper(trim($validated['workflow']['codigo'])),
                    'nombre' => $validated['workflow']['nombre'],
                    'proceso' => 'PROYECTO',
                    'descripcion' => $validated['workflow']['descripcion'] ?? null,
                    'activo' => $validated['workflow']['activo'] ?? true,
                    'tipo_accion_id' => $this->selectedSubactionId ?: $this->selectedActionId,
                ]
            );

            $this->syncFlowStages($flow, $validated['stages']);

            return $flow;
        });

        $this->selectedWorkflowId = $flow->id;
        $this->loadWorkflow($flow->fresh('etapas'));
        session()->flash('status', 'Flujo de proyectos guardado correctamente.');
    }

    public function selectProgramTipoPrograma(int $tipoId): void
    {
        $this->programSelectedTipoProgramaId = $tipoId;
        $this->loadProgramWorkflowForSelectedTipo();
    }

    public function render(): View
    {
        $actions = DB::table('vinculacion_tipos_accion')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $tiposPrograma = TipoPrograma::with('flujoAprobacion')->orderBy('nombre')->get();
        $selectedTipoPrograma = $tiposPrograma->firstWhere('id', $this->programSelectedTipoProgramaId);
        $roles = Role::query()->orderBy('name')->get();

        return view('livewire.configuracion.flujos.configuracion-flujos-proyectos', [
            'roles' => $roles,
            'usuariosPorRol' => $this->usersGroupedByRole($roles),
            'actions' => $actions,
            'tiposPrograma' => $tiposPrograma,
            'selectedTipoPrograma' => $selectedTipoPrograma,
        ])->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    protected function saveProgramFlow(): void
    {
        $this->programWorkflow['codigo'] = $this->programWorkflow['codigo']
            ?: $this->generateUniqueFlowCode('PROGRAMA_'.$this->programSelectedTipoProgramaId, $this->programWorkflowId);
        $this->normalizeProgramStageCodes();

        $validated = $this->validate([
            'programWorkflow.codigo' => ['required', 'string', 'max:80', Rule::unique('flujos_aprobacion', 'codigo')->ignore($this->programWorkflowId)],
            'programWorkflow.nombre' => ['required', 'string', 'max:180'],
            'programWorkflow.descripcion' => ['nullable', 'string'],
            'programWorkflow.activo' => ['boolean'],
            'programStages' => ['required', 'array', 'min:1'],
            'programStages.*.id' => ['nullable', 'integer', 'exists:flujos_aprobacion_etapas,id'],
            'programStages.*.codigo' => ['required', 'string', 'max:80'],
            'programStages.*.nombre' => ['required', 'string', 'max:180'],
            'programStages.*.rol_revisor_id' => ['nullable', 'exists:roles,id'],
            'programStages.*.usuario_responsable_id' => ['nullable', 'exists:users,id'],
            'programStages.*.cargo_firma_id' => ['nullable', 'exists:cargo_firma,id'],
            'programStages.*.requiere_asignacion' => ['boolean'],
            'programStages.*.emisor_define_destinatario' => ['boolean'],
            'programStages.*.activo' => ['boolean'],
        ]);

        $validated['programStages'] = $this->prepareStagesForSave($validated['programStages'], 'REVISION');

        if (! $this->programSelectedTipoProgramaId) {
            $this->addError('programWorkflow.nombre', 'Seleccione un tipo de programa.');
            return;
        }

        DB::transaction(function () use ($validated) {
            $flow = FlujoAprobacion::updateOrCreate(
                $this->programWorkflowId
                    ? ['id' => $this->programWorkflowId]
                    : ['proceso' => 'PROGRAMA', 'tipo_programa_id' => $this->programSelectedTipoProgramaId],
                [
                    'codigo' => strtoupper(trim($validated['programWorkflow']['codigo'])),
                    'nombre' => $validated['programWorkflow']['nombre'],
                    'proceso' => 'PROGRAMA',
                    'descripcion' => $validated['programWorkflow']['descripcion'] ?? null,
                    'activo' => $validated['programWorkflow']['activo'] ?? true,
                    'tipo_programa_id' => $this->programSelectedTipoProgramaId,
                ]
            );

            $this->syncFlowStages($flow, $validated['programStages']);

            $this->programWorkflowId = $flow->id;
        });

        $this->loadProgramWorkflowForSelectedTipo();
        session()->flash('status', 'Flujo de programa guardado correctamente.');
    }

    protected function loadFirstWorkflow(): void
    {
        $this->loadWorkflowForSelectedSubaction();
    }

    protected function loadWorkflowForSelectedSubaction(): void
    {
        if (! $this->selectedSubactionId) {
            $this->resetWorkflowForm();
            return;
        }

        $flow = $this->projectWorkflowForAction($this->selectedSubactionId);

        if (! $flow) {
            $this->resetWorkflowForm();
            return;
        }

        $this->loadWorkflow($flow);
    }

    protected function projectWorkflowForAction(int $actionId): ?FlujoAprobacion
    {
        return FlujoAprobacion::query()
            ->with('etapas')
            ->where('proceso', 'PROYECTO')
            ->where('tipo_accion_id', $actionId)
            ->orderBy('id')
            ->first();
    }

    protected function loadWorkflow(FlujoAprobacion $flow): void
    {
        $this->workflowId = $flow->id;
        $this->selectedWorkflowId = $flow->id;
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
                'id' => $stage->id,
                'codigo' => $stage->codigo,
                'aplica_inscripcion' => (bool) ($stage->aplica_inscripcion ?? true),
                'aplica_informe_intermedio' => (bool) ($stage->aplica_informe_intermedio ?? false),
                'aplica_cierre_proyecto' => (bool) ($stage->aplica_cierre_proyecto ?? false),
                'nombre' => $stage->nombre,
                'tipo_etapa' => $stage->tipo_etapa ?? 'REVISION',
                'rol_revisor_id' => (string) ($stage->rol_revisor_id ?? ''),
                'usuario_responsable_id' => (string) ($stage->usuario_responsable_id ?? ''),
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
        $this->selectedWorkflowId = null;
        $this->workflow = [
            'codigo' => $this->selectedSubactionId ? $this->generateProjectFlowCode($this->selectedSubactionId) : '',
            'nombre' => 'Flujo de aprobacion de '.($this->selectedActionName() ?: 'proyectos'),
            'proceso' => 'PROYECTO',
            'descripcion' => 'Flujo configurable para aprobacion de proyectos.',
            'activo' => true,
        ];
        $this->stages = [$this->blankStage(1)];
    }

    protected function blankStage(int $order, ?string $codigo = null): array
    {
        return [
            'id' => null,
            'codigo' => $codigo ?: 'ETAPA_'.$order,
            'aplica_inscripcion' => true,
            'aplica_informe_intermedio' => false,
            'aplica_cierre_proyecto' => false,
            'nombre' => '',
            'tipo_etapa' => 'REVISION',
            'rol_revisor_id' => '',
            'usuario_responsable_id' => '',
            'cargo_firma_id' => (string) $this->fallbackCargoFirmaId('REVISION'),
            'requiere_asignacion' => true,
            'emisor_define_destinatario' => false,
            'activo' => true,
        ];
    }

    protected function normalizeStageCodes(): void
    {
        $used = [];

        foreach ($this->stages as $index => $stage) {
            $codigo = $this->normalizeCode($stage['codigo'] ?? '');

            if ($codigo === '') {
                $codigo = 'ETAPA_'.($index + 1);
            }

            $base = $codigo;
            $suffix = 2;

            while (in_array($codigo, $used, true)) {
                $codigo = $base.'_'.$suffix;
                $suffix++;
            }

            $used[] = $codigo;
            $this->stages[$index]['codigo'] = $codigo;
        }
    }

    protected function resetProgramWorkflowForm(): void
    {
        $this->programWorkflowId = null;
        $this->programWorkflow = [
            'codigo' => $this->generateUniqueFlowCode('PROGRAMA_'.$this->programSelectedTipoProgramaId, $this->programWorkflowId),
            'nombre' => 'Flujo de aprobacion de programas',
            'proceso' => 'PROGRAMA',
            'descripcion' => '',
            'activo' => true,
        ];
        $this->programStages = [$this->blankStage(1)];
    }

    protected function loadProgramWorkflowForSelectedTipo(): void
    {
        if (! $this->programSelectedTipoProgramaId) {
            $this->resetProgramWorkflowForm();
            return;
        }

        $flow = FlujoAprobacion::with('etapas')
            ->where('proceso', 'PROGRAMA')
            ->where('tipo_programa_id', $this->programSelectedTipoProgramaId)
            ->first();

        if (! $flow) {
            $this->resetProgramWorkflowForm();
            return;
        }

        $this->programWorkflowId = $flow->id;
        $this->programWorkflow = [
            'codigo' => $flow->codigo,
            'nombre' => $flow->nombre,
            'proceso' => $flow->proceso,
            'descripcion' => $flow->descripcion ?? '',
            'activo' => (bool) $flow->activo,
        ];
        $this->programStages = $flow->etapas
            ->sortBy('orden')
            ->map(fn ($stage) => [
                'id' => $stage->id,
                'codigo' => $stage->codigo,
                'aplica_inscripcion' => (bool) ($stage->aplica_inscripcion ?? true),
                'aplica_informe_intermedio' => (bool) ($stage->aplica_informe_intermedio ?? false),
                'aplica_cierre_proyecto' => (bool) ($stage->aplica_cierre_proyecto ?? false),
                'nombre' => $stage->nombre,
                'tipo_etapa' => $stage->tipo_etapa ?? 'REVISION',
                'rol_revisor_id' => (string) ($stage->rol_revisor_id ?? ''),
                'usuario_responsable_id' => (string) ($stage->usuario_responsable_id ?? ''),
                'cargo_firma_id' => (string) $stage->cargo_firma_id,
                'requiere_asignacion' => (bool) $stage->requiere_asignacion,
                'emisor_define_destinatario' => (bool) $stage->emisor_define_destinatario,
                'activo' => (bool) $stage->activo,
            ])
            ->values()
            ->all();

        if ($this->programStages === []) {
            $this->programStages[] = $this->blankStage(1);
        }
    }

    protected function normalizeProgramStageCodes(): void
    {
        $used = [];

        foreach ($this->programStages as $index => $stage) {
            $codigo = $this->normalizeCode($stage['codigo'] ?? '');

            if ($codigo === '') {
                $codigo = 'ETAPA_'.($index + 1);
            }

            $base = $codigo;
            $suffix = 2;

            while (in_array($codigo, $used, true)) {
                $codigo = $base.'_'.$suffix;
                $suffix++;
            }

            $used[] = $codigo;
            $this->programStages[$index]['codigo'] = $codigo;
        }
    }

    protected function prepareStagesForSave(array $stages, string $defaultType = 'REVISION', ?int $flowId = null): array
    {
        $prepared = [];
        $usedCodes = [];

        foreach (array_values($stages) as $index => $stage) {
            $tipoEtapa = $stage['tipo_etapa'] ?? $defaultType;
            $codigo = $this->normalizeCode($stage['codigo'] ?? '')
                ?: ($flowId ? $this->generateUniqueStageCode($prepared, $flowId) : 'ETAPA_'.($index + 1));
            $baseCode = $codigo;
            $suffix = 2;

            while (in_array($codigo, $usedCodes, true)) {
                $codigo = $baseCode.'_'.$suffix;
                $suffix++;
            }

            $usedCodes[] = $codigo;

            $rolId = $stage['rol_revisor_id'] ?: null;
            $responsableId = $stage['usuario_responsable_id'] ?: null;
            $requiereAsignacion = (bool) ($stage['requiere_asignacion'] ?? false);
            $emisorDefine = (bool) ($stage['emisor_define_destinatario'] ?? false);

            if (! $requiereAsignacion) {
                $responsableId = null;
                $emisorDefine = false;
            }

            if ($emisorDefine || ! $rolId || ! $this->userBelongsToRole($responsableId, $rolId)) {
                $responsableId = null;
            }

            $prepared[] = [
                'id' => $stage['id'] ?? null,
                'orden' => $index + 1,
                'codigo' => $codigo,
                'aplica_inscripcion' => (bool) ($stage['aplica_inscripcion'] ?? true),
                'aplica_informe_intermedio' => (bool) ($stage['aplica_informe_intermedio'] ?? false),
                'aplica_cierre_proyecto' => (bool) ($stage['aplica_cierre_proyecto'] ?? false),
                'nombre' => $stage['nombre'],
                'tipo_etapa' => $tipoEtapa,
                'rol_revisor_id' => $rolId,
                'usuario_responsable_id' => $responsableId,
                'cargo_firma_id' => $this->fallbackCargoFirmaId($tipoEtapa, $stage['cargo_firma_id'] ?? null),
                'requiere_asignacion' => $requiereAsignacion,
                'emisor_define_destinatario' => $emisorDefine,
                'activo' => (bool) ($stage['activo'] ?? true),
            ];
        }

        return $prepared;
    }

    protected function generateUniqueStageCode(array $stages = [], ?int $flowId = null): string
    {
        $codes = collect($stages)
            ->pluck('codigo')
            ->map(fn ($code) => $this->normalizeCode((string) $code))
            ->filter()
            ->values();

        if ($flowId) {
            $codes = $codes->merge(
                FlujoAprobacionEtapa::query()
                    ->where('flujo_aprobacion_id', $flowId)
                    ->pluck('codigo')
                    ->map(fn ($code) => $this->normalizeCode((string) $code))
                    ->filter()
                    ->values()
            );
        }

        $maxNumericSuffix = $codes
            ->map(function (string $code) {
                return preg_match('/^ETAPA_(\d+)$/', $code, $matches)
                    ? (int) $matches[1]
                    : null;
            })
            ->filter()
            ->max();

        $next = max((int) $maxNumericSuffix + 1, count($stages) + 1);

        do {
            $candidate = sprintf('ETAPA_%02d', $next);
            $next++;
        } while ($codes->contains($candidate));

        return $candidate;
    }

    protected function syncFlowStages(FlujoAprobacion $flow, array $stages): void
    {
        $existing = $flow->etapas()->get()->keyBy('id');
        $keptIds = collect();

        $existing->each(function ($stage) {
            $stage->update([
                'orden' => 100000 + (int) $stage->id,
                'codigo' => 'TMP_'.$stage->id.'_'.substr(md5((string) microtime(true)), 0, 12),
            ]);
        });

        foreach ($stages as $stage) {
            $payload = [
                'orden' => $stage['orden'],
                'codigo' => $stage['codigo'],
                'aplica_inscripcion' => $stage['aplica_inscripcion'],
                'aplica_informe_intermedio' => $stage['aplica_informe_intermedio'],
                'aplica_cierre_proyecto' => $stage['aplica_cierre_proyecto'],
                'nombre' => $stage['nombre'],
                'tipo_etapa' => $stage['tipo_etapa'],
                'rol_revisor_id' => $stage['rol_revisor_id'],
                'usuario_responsable_id' => $stage['usuario_responsable_id'],
                'cargo_firma_id' => $stage['cargo_firma_id'],
                'requiere_asignacion' => $stage['requiere_asignacion'],
                'emisor_define_destinatario' => $stage['emisor_define_destinatario'],
                'activo' => $stage['activo'],
            ];

            $stageModel = $stage['id'] ? $existing->get((int) $stage['id']) : null;

            if ($stageModel) {
                $stageModel->update($payload);
                $keptIds->push($stageModel->id);
                continue;
            }

            $keptIds->push($flow->etapas()->create($payload)->id);
        }

        $flow->etapas()->whereNotIn('id', $keptIds->all())->delete();
    }

    protected function usersGroupedByRole($roles): array
    {
        $users = User::query()
            ->with('roles')
            ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $roles->pluck('id')))
            ->orderBy('name')
            ->get(['id', 'name']);

        return $roles
            ->mapWithKeys(fn ($role) => [
                (string) $role->id => $users
                    ->filter(fn (User $user) => $user->hasRole($role->name))
                    ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    protected function clearInvalidResponsible(array &$stages, int $index): void
    {
        if (! isset($stages[$index])) {
            return;
        }

        $roleId = $stages[$index]['rol_revisor_id'] ?? null;
        $userId = $stages[$index]['usuario_responsable_id'] ?? null;

        if (! $roleId || ! $this->userBelongsToRole($userId, $roleId)) {
            $stages[$index]['usuario_responsable_id'] = '';
        }
    }

    protected function syncResponsibleAvailability(array &$stages, int $index): void
    {
        if (! isset($stages[$index])) {
            return;
        }

        if (! ($stages[$index]['requiere_asignacion'] ?? false)) {
            $stages[$index]['emisor_define_destinatario'] = false;
            $stages[$index]['usuario_responsable_id'] = '';
            return;
        }

        if ($stages[$index]['emisor_define_destinatario'] ?? false) {
            $stages[$index]['usuario_responsable_id'] = '';
        }
    }

    protected function userBelongsToRole(mixed $userId, mixed $roleId): bool
    {
        if (! $userId || ! $roleId) {
            return false;
        }

        return User::query()
            ->whereKey($userId)
            ->whereHas('roles', fn ($query) => $query->where('roles.id', $roleId))
            ->exists();
    }

    protected function fallbackCargoFirmaId(string $tipoEtapa = 'REVISION', mixed $currentCargoId = null): int
    {
        if ($currentCargoId && CargoFirma::whereKey($currentCargoId)->exists()) {
            return (int) $currentCargoId;
        }

        $cargoName = match ($tipoEtapa) {
            'FORMULACION' => 'Coordinador Proyecto',
            'APROBACION' => 'Director Vinculacion',
            default => 'Revisor Vinculacion',
        };

        return $this->cargoFirmaCache[$cargoName] ??= (int) (CargoFirma::query()
            ->join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
            ->where('cargo_firma.descripcion', 'Proyecto')
            ->where('tipo_cargo_firma.nombre', $cargoName)
            ->value('cargo_firma.id')
            ?? CargoFirma::where('descripcion', 'Proyecto')->orderBy('id')->value('id'));
    }

    protected function generateUniqueFlowCode(string $base, ?int $ignoreId = null): string
    {
        $base = $this->normalizeCode($base) ?: 'FLUJO';
        $candidate = $base;
        $suffix = 2;

        while (FlujoAprobacion::query()
            ->where('codigo', $candidate)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $candidate = $base.'_'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    protected function generateProjectFlowCode(int $actionId): string
    {
        $actionCode = (string) DB::table('vinculacion_tipos_accion')
            ->where('id', $actionId)
            ->value('codigo');

        return $this->generateUniqueFlowCode('PROYECTO_'.$actionCode, $this->workflowId);
    }

    protected function selectedActionName(): ?string
    {
        if (! $this->selectedSubactionId) {
            return null;
        }

        return DB::table('vinculacion_tipos_accion')
            ->where('id', $this->selectedSubactionId)
            ->value('nombre');
    }

    protected function normalizeCode(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^A-Z0-9]+/', '_', $value) ?? '';

        return trim($value, '_');
    }
}

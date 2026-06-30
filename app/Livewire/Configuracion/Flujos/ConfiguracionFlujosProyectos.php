<?php

namespace App\Livewire\Configuracion\Flujos;

use App\Http\Controllers\ENF\EnfAccionController;
use App\Models\PpsServicioSocial;
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
    private const PPS_ACTION_CODE = 'PPS_VOLUNTARIADO_GESTION_RIESGO';
    private const PPS_DEFAULT_CODE = 'PPS_SERVICIO_SOCIAL_DEFAULT';
    private const ACTION_DESARROLLO_ID = -101;
    private const ACTION_PPS_ID = -102;
    private const ACTION_ENF_ID = -103;
    private const FORM_DESARROLLO_LOCAL_ID = -1001;
    private const FORM_PPS_SERVICIO_SOCIAL_ID = -1002;
    private const FORM_VOLUNTARIADO_ID = -1003;
    private const FORM_ENF_CERTIFICADO_ID = -1004;
    private const FORM_ENF_PROYECTO_ID = -1005;

    protected array $cargoFirmaCache = [];
    protected array $tipoAccionIdCache = [];

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

    public array $estadoOpciones = [
        'borrador' => 'Borrador',
        'enviado' => 'Enviado',
        'en_revision' => 'En revision',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'subsanacion' => 'Subsanacion',
    ];

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
        $firstAction = $this->projectFlowActions()->first();

        if ($firstAction) {
            $this->selectedActionId = (int) $firstAction->id;
            $this->selectedSubactionId = $this->subactionsForAction($this->selectedActionId)->first()?->id;
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
        $this->selectedSubactionId = $this->subactionsForAction($actionId)->first()?->id;
        $this->loadFirstWorkflow();
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
        $subaction = $this->subactionConfigForFlow($flow);

        $this->selectedWorkflowId = $flow->id;
        $this->selectedActionId = $subaction['action_id'] ?? $flow->tipo_accion_id;
        $this->selectedSubactionId = $subaction['id'] ?? $flow->tipo_accion_id;
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

        if ($this->isPpsActionSelected()) {
            $this->stages[] = $this->blankPpsStage(count($this->stages) + 1);
            $this->normalizePpsStageCodes();
            return;
        }

        $this->stages[] = $this->blankStage(
            count($this->stages) + 1,
            $this->generateUniqueStageCode($this->stages, $this->workflowId)
        );
        $this->normalizeStageCodes();
    }

    public function cargarEtapasSugeridas(): void
    {
        if ($this->activeFlowTab !== 'proyectos' || ! $this->isPpsActionSelected()) {
            return;
        }

        $this->stages = $this->defaultPpsStages();
        $this->resetErrorBag();
        session()->flash('status', 'Etapas sugeridas cargadas. Revise responsables y guarde el flujo.');
    }

    public function updated(string $property, mixed $value): void
    {
        if ($this->isPpsActionSelected() && preg_match('/^stages\.(\d+)\.(.+)$/', $property, $matches)) {
            $this->updatedPpsStage((int) $matches[1], $matches[2]);
            return;
        }

        if (preg_match('/^stages\.(\d+)\.rol_revisor_id$/', $property, $matches)) {
            $this->clearInvalidResponsible($this->stages, (int) $matches[1]);
            return;
        }

        if (preg_match('/^programStages\.(\d+)\.rol_revisor_id$/', $property, $matches)) {
            $this->clearInvalidResponsible($this->programStages, (int) $matches[1]);
            return;
        }

        if (preg_match('/^stages\.(\d+)\.(requiere_asignacion|emisor_define_destinatario)$/', $property, $matches)) {
            $this->syncResponsibleAvailability($this->stages, (int) $matches[1], $matches[2]);
            return;
        }

        if (preg_match('/^programStages\.(\d+)\.(requiere_asignacion|emisor_define_destinatario)$/', $property, $matches)) {
            $this->syncResponsibleAvailability($this->programStages, (int) $matches[1], $matches[2]);
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

        if ($this->isPpsActionSelected()) {
            unset($this->stages[$index]);
            $this->stages = array_values($this->stages);

            if ($this->stages === []) {
                $this->stages[] = $this->blankPpsStage(1);
            }

            $this->normalizePpsStageOrders();
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

        if ($this->isPpsActionSelected()) {
            if ($index <= 0 || ! isset($this->stages[$index], $this->stages[$index - 1])) {
                return;
            }

            [$this->stages[$index - 1], $this->stages[$index]] = [$this->stages[$index], $this->stages[$index - 1]];
            $this->stages = array_values($this->stages);
            $this->normalizePpsStageOrders();
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

        if ($this->isPpsActionSelected()) {
            if (! isset($this->stages[$index], $this->stages[$index + 1])) {
                return;
            }

            [$this->stages[$index + 1], $this->stages[$index]] = [$this->stages[$index], $this->stages[$index + 1]];
            $this->stages = array_values($this->stages);
            $this->normalizePpsStageOrders();
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

        if ($this->isPpsActionSelected()) {
            $this->savePpsFlow();
            return;
        }

        $subaction = $this->selectedSubactionConfig();

        if (! $subaction || ! ($subaction['tipo_accion_id'] ?? null)) {
            $this->addError('workflow.nombre', 'Seleccione un formulario disponible para configurar su flujo.');
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

        $flow = DB::transaction(function () use ($validated, $subaction) {
            $flow = FlujoAprobacion::updateOrCreate(
                $this->workflowId
                    ? ['id' => $this->workflowId]
                    : [
                        'proceso' => $subaction['proceso'],
                        'tipo_accion_id' => $subaction['tipo_accion_id'],
                        'codigo_formulario' => $subaction['codigo_formulario'],
                    ],
                [
                    'codigo' => strtoupper(trim($validated['workflow']['codigo'])),
                    'nombre' => $validated['workflow']['nombre'],
                    'proceso' => $subaction['proceso'],
                    'descripcion' => $validated['workflow']['descripcion'] ?? null,
                    'activo' => $validated['workflow']['activo'] ?? true,
                    'tipo_accion_id' => $subaction['tipo_accion_id'],
                    'tipo_programa_id' => null,
                    'codigo_formulario' => $subaction['codigo_formulario'],
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
        $actions = $this->projectFlowActions();
        $tiposPrograma = TipoPrograma::with('flujoAprobacion')->orderBy('nombre')->get();
        $selectedTipoPrograma = $tiposPrograma->firstWhere('id', $this->programSelectedTipoProgramaId);
        $roles = Role::query()->orderBy('name')->get();
        $usuarios = User::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.configuracion.flujos.configuracion-flujos-proyectos', [
            'roles' => $roles,
            'usuarios' => $usuarios,
            'usuariosPorRol' => $this->usersGroupedByRole($roles),
            'actions' => $actions,
            'subactions' => $this->subactionsForAction($this->selectedActionId),
            'isPpsActionSelected' => $this->isPpsActionSelected(),
            'estadoOpciones' => $this->estadoOpciones,
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

    protected function savePpsFlow(): void
    {
        $this->resetErrorBag();
        $this->normalizePpsStageCodes();

        $validated = $this->validate([
            'workflow.codigo' => ['required', 'string', 'max:80', Rule::unique('flujos_aprobacion', 'codigo')->ignore($this->workflowId)],
            'workflow.nombre' => ['required', 'string', 'max:180'],
            'workflow.descripcion' => ['nullable', 'string'],
            'workflow.activo' => ['boolean'],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.id' => ['nullable', 'integer', 'exists:flujos_aprobacion_etapas,id'],
            'stages.*.codigo' => ['required', 'string', 'max:80'],
            'stages.*.nombre' => ['required', 'string', 'max:180'],
            'stages.*.tipo_etapa' => ['required', 'in:FORMULACION,REVISION,APROBACION'],
            'stages.*.orden' => ['required', 'integer', 'min:1'],
            'stages.*.rol_revisor_id' => ['nullable', 'exists:roles,id'],
            'stages.*.usuario_responsable_id' => ['nullable', 'exists:users,id'],
            'stages.*.requiere_asignacion' => ['boolean'],
            'stages.*.activo' => ['boolean'],
            'stages.*.estado_resultante' => ['nullable', 'string', Rule::in(array_keys($this->estadoOpciones))],
            'stages.*.permite_edicion' => ['boolean'],
            'stages.*.permite_rechazo' => ['boolean'],
            'stages.*.es_estado_final_aprobado' => ['boolean'],
        ], [], $this->ppsValidationAttributes());

        $preparedStages = $this->preparePpsStagesForSave($validated['stages']);

        if (! $this->validatePpsBusinessRules($preparedStages)) {
            return;
        }

        if (! $this->ppsCargoFirmaTecnicoId()) {
            $this->addError('stages', 'No existe un cargo de firma tecnico para guardar etapas. Revise el catalogo cargo_firma antes de guardar este flujo.');

            return;
        }

        DB::transaction(function () use ($validated, $preparedStages): void {
            $flow = $this->workflowId
                ? FlujoAprobacion::findOrFail($this->workflowId)
                : new FlujoAprobacion();

            $flow->fill([
                'codigo' => $this->normalizeCode($validated['workflow']['codigo']),
                'nombre' => $validated['workflow']['nombre'],
                'proceso' => PpsServicioSocial::PROCESO_FLUJO,
                'tipo_accion_id' => $this->realPpsActionId(),
                'tipo_programa_id' => null,
                'codigo_formulario' => 'FORM-DVUS-014',
                'descripcion' => $validated['workflow']['descripcion'] ?? null,
                'activo' => (bool) ($validated['workflow']['activo'] ?? true),
            ]);
            $flow->save();

            if ($flow->activo) {
                FlujoAprobacion::query()
                    ->where('proceso', PpsServicioSocial::PROCESO_FLUJO)
                    ->whereKeyNot($flow->id)
                    ->update(['activo' => false]);
            }

            $this->syncPpsFlowStages($flow, $preparedStages);
            $this->workflowId = $flow->id;
            $this->selectedWorkflowId = $flow->id;
        });

        $this->loadPpsWorkflow();
        session()->flash('status', 'Flujo PPS / Servicio Social guardado correctamente.');
    }

    protected function projectFlowActions()
    {
        return collect($this->projectFlowCatalog())
            ->filter(fn (array $action) => $this->subactionsForAction((int) $action['id'])->isNotEmpty())
            ->sortBy('orden')
            ->map(fn (array $action) => (object) [
                'id' => $action['id'],
                'codigo' => $action['codigo'],
                'nombre' => $action['nombre'],
                'descripcion' => $action['descripcion'],
                'orden' => $action['orden'],
            ])
            ->values();
    }

    protected function subactionsForAction(?int $actionId)
    {
        if (! $actionId) {
            return collect();
        }

        $action = collect($this->projectFlowCatalog())
            ->firstWhere('id', $actionId);

        if (! $action) {
            return collect();
        }

        return collect($action['subactions'])
            ->filter(fn (array $subaction) => $this->subactionIsAvailable($subaction))
            ->map(function (array $subaction) use ($action): object {
                $subaction['action_id'] = $action['id'];
                $subaction['tipo_accion_id'] = $this->tipoAccionIdByCode($subaction['tipo_accion_codigo'] ?? null);

                return (object) $subaction;
            })
            ->values();
    }

    protected function selectedSubactionConfig(?int $subactionId = null): ?array
    {
        $subactionId ??= $this->selectedSubactionId;

        if (! $subactionId) {
            return null;
        }

        foreach ($this->projectFlowCatalog() as $action) {
            foreach ($action['subactions'] as $subaction) {
                if ((int) $subaction['id'] !== $subactionId || ! $this->subactionIsAvailable($subaction)) {
                    continue;
                }

                $subaction['action_id'] = $action['id'];
                $subaction['tipo_accion_id'] = $this->tipoAccionIdByCode($subaction['tipo_accion_codigo'] ?? null);

                return $subaction;
            }
        }

        return null;
    }

    protected function subactionConfigForFlow(FlujoAprobacion $flow): ?array
    {
        foreach ($this->projectFlowCatalog() as $action) {
            foreach ($action['subactions'] as $subaction) {
                if (! $this->subactionIsAvailable($subaction)) {
                    continue;
                }

                $tipoAccionId = $this->tipoAccionIdByCode($subaction['tipo_accion_codigo'] ?? null);
                $matchesForm = $flow->codigo_formulario
                    ? $flow->codigo_formulario === ($subaction['codigo_formulario'] ?? null)
                    : (int) $flow->tipo_accion_id === (int) $tipoAccionId;

                if ($flow->proceso === $subaction['proceso'] && $matchesForm) {
                    $subaction['action_id'] = $action['id'];
                    $subaction['tipo_accion_id'] = $tipoAccionId;

                    return $subaction;
                }
            }
        }

        return null;
    }

    protected function subactionIsAvailable(array $subaction): bool
    {
        if (! ($subaction['enabled'] ?? true)) {
            return false;
        }

        if (($subaction['tipo_accion_codigo'] ?? null) && ! $this->tipoAccionIdByCode($subaction['tipo_accion_codigo'])) {
            return false;
        }

        return true;
    }

    protected function tipoAccionIdByCode(?string $code): ?int
    {
        if (! $code) {
            return null;
        }

        if (array_key_exists($code, $this->tipoAccionIdCache)) {
            return $this->tipoAccionIdCache[$code];
        }

        $id = DB::table('vinculacion_tipos_accion')
            ->where('codigo', $code)
            ->where('activo', true)
            ->value('id');

        return $this->tipoAccionIdCache[$code] = $id ? (int) $id : null;
    }

    protected function projectFlowCatalog(): array
    {
        return [
            [
                'id' => self::ACTION_DESARROLLO_ID,
                'codigo' => 'DESARROLLO_LOCAL_REGIONAL',
                'nombre' => 'Proyectos de desarrollo local y regional',
                'descripcion' => 'Registra proyectos de vinculacion con contraparte y enfoque territorial.',
                'orden' => 1,
                'subactions' => [
                    [
                        'id' => self::FORM_DESARROLLO_LOCAL_ID,
                        'codigo_formulario' => 'FORM-DVUS-001',
                        'codigo' => 'FORM-DVUS-001',
                        'nombre' => 'FORM-DVUS-001 - Registro de Proyectos de Vinculacion Desarrollo Local y Regional',
                        'proceso' => 'PROYECTO',
                        'tipo_accion_codigo' => 'DESARROLLO_LOCAL_REGIONAL',
                        'workflow_codigo_base' => 'PROYECTO_FORM_DVUS_001',
                        'workflow_nombre' => 'Flujo FORM-DVUS-001 - Desarrollo local y regional',
                        'workflow_descripcion' => 'Flujo configurable para el FORM-DVUS-001.',
                        'enabled' => true,
                    ],
                ],
            ],
            [
                'id' => self::ACTION_PPS_ID,
                'codigo' => self::PPS_ACTION_CODE,
                'nombre' => 'Practica Profesional Supervisada / Servicio Social / Voluntariado',
                'descripcion' => 'Registra acciones relacionadas con pasantias, PPS, servicio social y voluntariado universitario.',
                'orden' => 2,
                'subactions' => [
                    [
                        'id' => self::FORM_PPS_SERVICIO_SOCIAL_ID,
                        'codigo_formulario' => 'FORM-DVUS-014',
                        'codigo' => 'FORM-DVUS-014',
                        'nombre' => 'FORM-DVUS-014 - Registro PPS y Servicio Social',
                        'proceso' => PpsServicioSocial::PROCESO_FLUJO,
                        'tipo_accion_codigo' => self::PPS_ACTION_CODE,
                        'workflow_codigo_base' => self::PPS_DEFAULT_CODE,
                        'workflow_nombre' => 'Flujo FORM-DVUS-014 - PPS / Servicio Social',
                        'workflow_descripcion' => 'Flujo configurable para revision del FORM-DVUS-014.',
                        'enabled' => true,
                    ],
                    [
                        'id' => self::FORM_VOLUNTARIADO_ID,
                        'codigo_formulario' => 'FORM-DVUS-015',
                        'codigo' => 'FORM-DVUS-015',
                        'nombre' => 'FORM-DVUS-015 - Registro Proyecto de Voluntariado',
                        'proceso' => 'PROYECTO',
                        'tipo_accion_codigo' => 'VOLUNTARIADO',
                        'workflow_codigo_base' => 'PROYECTO_FORM_DVUS_015',
                        'workflow_nombre' => 'Flujo FORM-DVUS-015 - Voluntariado',
                        'workflow_descripcion' => 'Flujo configurable para el FORM-DVUS-015.',
                        'enabled' => true,
                    ],
                ],
            ],
            [
                'id' => self::ACTION_ENF_ID,
                'codigo' => 'EDUCACION_NO_FORMAL',
                'nombre' => 'Educacion no formal',
                'descripcion' => 'Registra cursos, talleres, diplomados, congresos, seminarios y educacion continua.',
                'orden' => 3,
                'subactions' => [
                    [
                        'id' => self::FORM_ENF_CERTIFICADO_ID,
                        'codigo_formulario' => 'FORM-DVUS-016',
                        'codigo' => 'FORM-DVUS-016',
                        'nombre' => 'FORM-DVUS-016 - Registro de Certificados Universitarios',
                        'proceso' => 'PROYECTO',
                        'tipo_accion_codigo' => 'EDUCACION_NO_FORMAL',
                        'workflow_codigo_base' => 'PROYECTO_FORM_DVUS_016',
                        'workflow_nombre' => 'Flujo FORM-DVUS-016 - Certificados universitarios',
                        'workflow_descripcion' => 'Flujo configurable para el FORM-DVUS-016.',
                        'enabled' => EnfAccionController::formularioCertificadoUniversitarioDisponible(),
                    ],
                    [
                        'id' => self::FORM_ENF_PROYECTO_ID,
                        'codigo_formulario' => 'FORM-DVUS-018',
                        'codigo' => 'FORM-DVUS-018',
                        'nombre' => 'FORM-DVUS-018 - Registro Educacion No Formal - Proyectos',
                        'proceso' => 'PROYECTO',
                        'tipo_accion_codigo' => 'EDUCACION_NO_FORMAL',
                        'workflow_codigo_base' => 'PROYECTO_FORM_DVUS_018',
                        'workflow_nombre' => 'Flujo FORM-DVUS-018 - Educacion No Formal',
                        'workflow_descripcion' => 'Flujo configurable para el FORM-DVUS-018.',
                        'enabled' => true,
                    ],
                ],
            ],
        ];
    }

    protected function loadPpsWorkflow(): void
    {
        $flow = FlujoAprobacion::with('etapas')
            ->where('proceso', PpsServicioSocial::PROCESO_FLUJO)
            ->where('codigo_formulario', 'FORM-DVUS-014')
            ->first()
            ?? FlujoAprobacion::with('etapas')
                ->where('proceso', PpsServicioSocial::PROCESO_FLUJO)
                ->where('codigo', self::PPS_DEFAULT_CODE)
                ->orderByDesc('activo')
                ->orderBy('id')
                ->first();

        if (! $flow) {
            $this->workflowId = null;
            $this->selectedWorkflowId = null;
            $this->workflow = [
                'codigo' => self::PPS_DEFAULT_CODE,
                'nombre' => 'Flujo FORM-DVUS-014 - PPS / Servicio Social',
                'proceso' => PpsServicioSocial::PROCESO_FLUJO,
                'descripcion' => 'Flujo configurable para revision del FORM-DVUS-014.',
                'activo' => true,
            ];
            $this->stages = $this->defaultPpsStages();

            return;
        }

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
            ->map(fn (FlujoAprobacionEtapa $stage): array => [
                'id' => $stage->id,
                'codigo' => $stage->codigo,
                'nombre' => $stage->nombre,
                'tipo_etapa' => $stage->tipo_etapa ?? 'REVISION',
                'orden' => (int) $stage->orden,
                'rol_revisor_id' => (string) ($stage->rol_revisor_id ?? ''),
                'usuario_responsable_id' => (string) ($stage->usuario_responsable_id ?? ''),
                'requiere_asignacion' => (bool) $stage->requiere_asignacion,
                'activo' => (bool) $stage->activo,
                'estado_resultante' => $stage->estado_resultante ?? '',
                'permite_edicion' => (bool) $stage->permite_edicion,
                'permite_rechazo' => (bool) $stage->permite_rechazo,
                'es_estado_final_aprobado' => (bool) $stage->es_estado_final_aprobado,
            ])
            ->values()
            ->all();

        if ($this->stages === []) {
            $this->stages = $this->defaultPpsStages();
        }
    }

    protected function loadFirstWorkflow(): void
    {
        if ($this->isPpsActionSelected()) {
            $this->loadPpsWorkflow();
            return;
        }

        if ($this->selectedSubactionId) {
            $this->loadWorkflowForSelectedSubaction();
            return;
        }

        $this->resetWorkflowForm();
    }

    protected function loadWorkflowForSelectedSubaction(): void
    {
        if ($this->isPpsActionSelected()) {
            $this->loadPpsWorkflow();
            return;
        }

        $subaction = $this->selectedSubactionConfig();

        if (! $subaction || ! ($subaction['tipo_accion_id'] ?? null)) {
            $this->resetWorkflowForm();
            return;
        }

        $flow = FlujoAprobacion::with('etapas')
            ->where('proceso', $subaction['proceso'])
            ->where('tipo_accion_id', $subaction['tipo_accion_id'])
            ->where('codigo_formulario', $subaction['codigo_formulario'])
            ->first();

        if (! $flow) {
            $this->resetWorkflowForm();
            return;
        }

        $this->applyFlowSelection($flow);
    }

    protected function applyFlowSelection(FlujoAprobacion $flow): void
    {
        $subaction = $this->subactionConfigForFlow($flow);

        if ($subaction) {
            $this->selectedActionId = $subaction['action_id'];
            $this->selectedSubactionId = $subaction['id'];
        }

        $this->selectedWorkflowId = $flow->id;
        $this->loadWorkflow($flow);
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
        $subaction = $this->selectedSubactionConfig();

        $this->workflowId = null;
        $this->selectedWorkflowId = null;
        $this->workflow = [
            'codigo' => $subaction ? $this->generateProjectFlowCode($this->selectedSubactionId) : '',
            'nombre' => $subaction['workflow_nombre'] ?? 'Flujo de aprobacion de proyectos',
            'proceso' => $subaction['proceso'] ?? 'PROYECTO',
            'descripcion' => $subaction['workflow_descripcion'] ?? 'Flujo configurable para aprobacion de proyectos.',
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

    protected function defaultPpsStages(): array
    {
        return [
            [
                'id' => null,
                'codigo' => 'REVISION_PPS',
                'nombre' => 'Revision PPS/SS',
                'tipo_etapa' => 'REVISION',
                'orden' => 1,
                'rol_revisor_id' => '',
                'usuario_responsable_id' => '',
                'requiere_asignacion' => false,
                'activo' => true,
                'estado_resultante' => PpsServicioSocial::ESTADO_ENVIADO,
                'permite_edicion' => false,
                'permite_rechazo' => true,
                'es_estado_final_aprobado' => false,
            ],
            [
                'id' => null,
                'codigo' => 'APROBADO',
                'nombre' => 'Aprobado',
                'tipo_etapa' => 'APROBACION',
                'orden' => 2,
                'rol_revisor_id' => '',
                'usuario_responsable_id' => '',
                'requiere_asignacion' => false,
                'activo' => true,
                'estado_resultante' => PpsServicioSocial::ESTADO_APROBADO,
                'permite_edicion' => false,
                'permite_rechazo' => false,
                'es_estado_final_aprobado' => true,
            ],
        ];
    }

    protected function blankPpsStage(int $order): array
    {
        return [
            'id' => null,
            'codigo' => 'ETAPA_'.$order,
            'nombre' => '',
            'tipo_etapa' => 'REVISION',
            'orden' => $order,
            'rol_revisor_id' => '',
            'usuario_responsable_id' => '',
            'requiere_asignacion' => false,
            'activo' => true,
            'estado_resultante' => 'en_revision',
            'permite_edicion' => false,
            'permite_rechazo' => true,
            'es_estado_final_aprobado' => false,
        ];
    }

    protected function preparePpsStagesForSave(array $stages): array
    {
        return collect($stages)
            ->sortBy(fn (array $stage) => (int) ($stage['orden'] ?? 0))
            ->values()
            ->map(function (array $stage, int $index): array {
                $rolId = $stage['rol_revisor_id'] ?: null;
                $responsableId = $stage['usuario_responsable_id'] ?: null;
                $requiereAsignacion = (bool) ($stage['requiere_asignacion'] ?? false);

                if (! $requiereAsignacion || ($responsableId && ! $this->userBelongsToRole($responsableId, $rolId))) {
                    $responsableId = null;
                }

                return [
                    'id' => $stage['id'] ?? null,
                    'orden' => $index + 1,
                    'codigo' => $this->normalizeCode($stage['codigo'] ?? '') ?: 'ETAPA_'.($index + 1),
                    'nombre' => $stage['nombre'],
                    'tipo_etapa' => $stage['tipo_etapa'] ?? 'REVISION',
                    'rol_revisor_id' => $rolId,
                    'usuario_responsable_id' => $responsableId,
                    'requiere_asignacion' => $requiereAsignacion,
                    'activo' => (bool) ($stage['activo'] ?? true),
                    'estado_resultante' => $stage['estado_resultante'] ?: null,
                    'permite_edicion' => (bool) ($stage['permite_edicion'] ?? false),
                    'permite_rechazo' => (bool) ($stage['permite_rechazo'] ?? true),
                    'es_estado_final_aprobado' => (bool) ($stage['es_estado_final_aprobado'] ?? false),
                ];
            })
            ->all();
    }

    protected function validatePpsBusinessRules(array $stages): bool
    {
        $valid = true;
        $activeStages = collect($stages)->filter(fn (array $stage) => $stage['activo']);

        $duplicatedCodes = collect($stages)
            ->pluck('codigo')
            ->duplicates()
            ->values();

        if ($duplicatedCodes->isNotEmpty()) {
            $this->addError('stages', 'Los codigos de etapa deben ser unicos dentro del flujo.');
            $valid = false;
        }

        $finalStages = $activeStages->filter(fn (array $stage) => $stage['es_estado_final_aprobado']);

        if ($finalStages->count() !== 1) {
            $this->addError('stages', 'Debe existir exactamente una etapa activa marcada como aprobacion final.');
            $valid = false;
        }

        if ($activeStages->where('es_estado_final_aprobado', false)->isEmpty()) {
            $this->addError('stages', 'Debe existir al menos una etapa activa antes de la aprobacion final.');
            $valid = false;
        }

        foreach ($stages as $index => $stage) {
            $fieldPrefix = 'stages.'.$index;

            if ($stage['activo'] && blank($stage['estado_resultante'])) {
                $this->addError($fieldPrefix.'.estado_resultante', 'El estado resultante es obligatorio para etapas activas.');
                $valid = false;
            }

            if ($stage['activo'] && $stage['requiere_asignacion'] && ! $stage['rol_revisor_id'] && ! $stage['usuario_responsable_id']) {
                $this->addError($fieldPrefix.'.rol_revisor_id', 'Seleccione un rol o responsable para etapas que requieren asignacion.');
                $valid = false;
            }

            if ($stage['es_estado_final_aprobado'] && $stage['estado_resultante'] !== PpsServicioSocial::ESTADO_APROBADO) {
                $this->addError($fieldPrefix.'.estado_resultante', 'La etapa final aprobada debe usar estado aprobado.');
                $valid = false;
            }

            if ($stage['permite_edicion'] && ! in_array($stage['estado_resultante'], ['borrador', 'subsanacion'], true)) {
                $this->addError($fieldPrefix.'.estado_resultante', 'Una etapa editable debe usar estado borrador o subsanacion.');
                $valid = false;
            }
        }

        return $valid;
    }

    protected function syncPpsFlowStages(FlujoAprobacion $flow, array $stages): void
    {
        $existing = $flow->etapas()->get()->keyBy('id');
        $keptIds = collect();

        $existing->each(function (FlujoAprobacionEtapa $stage): void {
            $stage->update([
                'orden' => 100000 + (int) $stage->id,
                'codigo' => 'TMP_'.$stage->id.'_'.substr(md5((string) microtime(true)), 0, 12),
            ]);
        });

        foreach ($stages as $stage) {
            $payload = [
                'orden' => $stage['orden'],
                'codigo' => $stage['codigo'],
                'aplica_inscripcion' => false,
                'aplica_informe_intermedio' => false,
                'aplica_cierre_proyecto' => false,
                'nombre' => $stage['nombre'],
                'tipo_etapa' => $stage['tipo_etapa'],
                'rol_revisor_id' => $stage['rol_revisor_id'],
                'usuario_responsable_id' => $stage['usuario_responsable_id'],
                'cargo_firma_id' => $this->ppsCargoFirmaTecnicoId((int) ($existing->get((int) ($stage['id'] ?? 0))?->cargo_firma_id ?? 0)),
                'requiere_asignacion' => $stage['requiere_asignacion'],
                'emisor_define_destinatario' => false,
                'activo' => $stage['activo'],
                'estado_resultante' => $stage['estado_resultante'],
                'permite_edicion' => $stage['permite_edicion'],
                'permite_rechazo' => $stage['permite_rechazo'],
                'es_estado_final_aprobado' => $stage['es_estado_final_aprobado'],
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

    protected function normalizePpsStageCodes(): void
    {
        foreach ($this->stages as $index => $stage) {
            $this->stages[$index]['codigo'] = $this->normalizeCode($stage['codigo'] ?? '')
                ?: 'ETAPA_'.($index + 1);
        }
    }

    protected function normalizePpsStageOrders(): void
    {
        foreach (array_values($this->stages) as $index => $stage) {
            $this->stages[$index] = $stage;
            $this->stages[$index]['orden'] = $index + 1;
        }
    }

    protected function updatedPpsStage(int $index, string $field): void
    {
        if (! isset($this->stages[$index])) {
            return;
        }

        if ($field === 'rol_revisor_id') {
            $this->clearInvalidResponsible($this->stages, $index);
        }

        if ($field === 'requiere_asignacion' && ! ($this->stages[$index]['requiere_asignacion'] ?? false)) {
            $this->stages[$index]['usuario_responsable_id'] = '';
        }

        if ($field === 'es_estado_final_aprobado' && ($this->stages[$index]['es_estado_final_aprobado'] ?? false)) {
            $this->stages[$index]['estado_resultante'] = PpsServicioSocial::ESTADO_APROBADO;
            $this->stages[$index]['permite_edicion'] = false;
            $this->stages[$index]['permite_rechazo'] = false;
        }
    }

    protected function ppsCargoFirmaTecnicoId(?int $currentCargoId = null): ?int
    {
        if ($currentCargoId && CargoFirma::whereKey($currentCargoId)->exists()) {
            return $currentCargoId;
        }

        $projectCargoId = CargoFirma::query()
            ->where('descripcion', 'Proyecto')
            ->orderBy('id')
            ->value('id');

        if ($projectCargoId) {
            return (int) $projectCargoId;
        }

        $fallbackCargoId = CargoFirma::query()
            ->orderBy('id')
            ->value('id');

        return $fallbackCargoId ? (int) $fallbackCargoId : null;
    }

    protected function isPpsActionSelected(): bool
    {
        return ($this->selectedSubactionConfig()['proceso'] ?? null) === PpsServicioSocial::PROCESO_FLUJO;
    }

    protected function realPpsActionId(): ?int
    {
        $actionId = DB::table('vinculacion_tipos_accion')
            ->where('codigo', self::PPS_ACTION_CODE)
            ->value('id');

        return $actionId ? (int) $actionId : null;
    }

    protected function ppsValidationAttributes(): array
    {
        return [
            'workflow.codigo' => 'codigo del flujo',
            'workflow.nombre' => 'nombre del flujo',
            'stages.*.codigo' => 'codigo de etapa',
            'stages.*.nombre' => 'nombre de etapa',
            'stages.*.orden' => 'orden de etapa',
            'stages.*.estado_resultante' => 'estado resultante',
        ];
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

    protected function syncResponsibleAvailability(array &$stages, int $index, string $changedField = ''): void
    {
        if (! isset($stages[$index])) {
            return;
        }

        if ($changedField === 'requiere_asignacion' && ($stages[$index]['requiere_asignacion'] ?? false)) {
            $stages[$index]['emisor_define_destinatario'] = false;
        } elseif ($changedField === 'emisor_define_destinatario' && ($stages[$index]['emisor_define_destinatario'] ?? false)) {
            $stages[$index]['requiere_asignacion'] = false;
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
        $subaction = $this->selectedSubactionConfig($actionId);
        $actionCode = $subaction['workflow_codigo_base']
            ?? 'PROYECTO_'.($this->normalizeCode($subaction['codigo_formulario'] ?? '') ?: 'FLUJO');

        return $this->generateUniqueFlowCode($actionCode, $this->workflowId);
    }

    protected function normalizeCode(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^A-Z0-9]+/', '_', $value) ?? '';

        return trim($value, '_');
    }
}

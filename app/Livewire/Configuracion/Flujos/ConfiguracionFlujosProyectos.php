<?php

namespace App\Livewire\Configuracion\Flujos;

use App\Http\Controllers\ENF\EnfAccionController;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Support\Notification;
use App\Models\DAFT\TipoPrograma;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    /**
     * Todo formulario tiene siempre estas 4 firmas fijas (etapas de tipo
     * APROBACION), sin importar el flujo. Lo único configurable por flujo es
     * el ROL con acceso a cada etapa (quién ocupa ese cargo), no el cargo en
     * sí. Clave = nombre real en tipo_cargo_firma; valor = etiqueta a mostrar.
     */
    private const CARGOS_FIRMA_FIJOS = [
        'Coordinador Proyecto' => 'Coordinador de la acción por la UNAH',
        'Jefe Departamento' => 'Jefe de la Unidad Académica que lidera la acción',
        'Enlace Vinculacion' => 'Coordinador(a) del Comité de Vinculación del Centro Regional',
        'Director centro' => 'Decano(a) o Director(a) del Centro Regional',
    ];

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
            $this->programStages[] = $this->blankProgramStage(count($this->programStages) + 1);
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
        if (preg_match('/^stages\.(\d+)\.tipo_etapa$/', $property, $matches) && $value !== 'APROBACION') {
            $this->stages[(int) $matches[1]]['cargo_firma_id'] = '';
            return;
        }

        if (preg_match('/^programStages\.(\d+)\.tipo_etapa$/', $property, $matches) && $value !== 'APROBACION') {
            $this->programStages[(int) $matches[1]]['cargo_firma_id'] = '';
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
                $this->programStages[] = $this->blankProgramStage(1);
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
            'stages.*.cargo_firma_id' => ['nullable', 'required_if:stages.*.tipo_etapa,APROBACION', 'exists:cargo_firma,id'],
            'stages.*.requiere_asignacion' => ['boolean'],
            'stages.*.emisor_define_destinatario' => ['boolean'],
            'stages.*.activo' => ['boolean'],
        ]);

        $validated['stages'] = $this->prepareStagesForSave($validated['stages'], 'REVISION', $this->workflowId);

        if ($this->hasDuplicateCargoFirmaEnEtapasActivas($validated['stages'], 'stages', soloAprobacion: true)) {
            return;
        }

        if ($this->workflowId) {
            $tieneProyectosActivos = Proyecto::where('flujo_aprobacion_id', $this->workflowId)
                ->whereHas('firmasDeEtapa', fn ($q) => $q->where('estado_revision', 'Pendiente'))
                ->exists();

            if ($tieneProyectosActivos) {
                Notification::make()
                    ->title('Flujo con proyectos activos')
                    ->body('Hay proyectos en revisión que usan este flujo. Los cambios solo afectarán proyectos nuevos.')
                    ->warning()
                    ->send();
            }
        }

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
        // Todo formulario tiene siempre las mismas 4 firmas fijas (lo que varía
        // por flujo es el ROL con acceso a cada etapa, no el cargo de firma).
        // 'descripcion' = Proyecto son los cargos vinculados al estado del propio
        // Proyecto (los que usan estas etapas).
        $cargosPorNombre = CargoFirma::with('tipoCargoFirma')
            ->where('descripcion', 'Proyecto')
            ->get()
            ->keyBy(fn (CargoFirma $cargo) => $cargo->tipoCargoFirma?->nombre);

        $cargoFirmas = collect(self::CARGOS_FIRMA_FIJOS)
            ->map(fn (string $label, string $nombreCargo) => $cargosPorNombre->has($nombreCargo)
                ? (object) ['id' => $cargosPorNombre->get($nombreCargo)->id, 'label' => $label]
                : null)
            ->filter()
            ->values();

        return view('livewire.configuracion.flujos.configuracion-flujos-proyectos', [
            'roles' => $roles,
            'usuarios' => $usuarios,
            'usuariosPorRol' => $this->usersGroupedByRole($roles),
            'actions' => $actions,
            'subactions' => $this->subactionsForAction($this->selectedActionId),
            'tiposPrograma' => $tiposPrograma,
            'selectedTipoPrograma' => $selectedTipoPrograma,
            'cargoFirmas' => $cargoFirmas,
        ])->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    protected function saveProgramFlow(): void
    {
        $this->programWorkflow['codigo'] = $this->automaticProgramFlowCode();
        $this->normalizeProgramStageCodes();

        $validated = $this->validate([
            'programWorkflow.codigo' => ['required', 'string', 'max:80', Rule::unique('flujos_aprobacion', 'codigo')->ignore($this->programWorkflowId)],
            'programWorkflow.nombre' => ['required', 'string', 'max:180'],
            'programWorkflow.descripcion' => ['nullable', 'string'],
            'programWorkflow.activo' => ['boolean'],
            'programStages' => ['required', 'array', 'min:1'],
            'programStages.*.id' => ['nullable', 'integer', 'exists:flujos_aprobacion_etapas,id'],
            'programStages.*.codigo' => ['required', 'string', 'max:80'],
            'programStages.*.aplica_inscripcion' => ['boolean'],
            'programStages.*.aplica_informe_intermedio' => ['boolean'],
            'programStages.*.aplica_cierre_proyecto' => ['boolean'],
            'programStages.*.nombre' => ['required', 'string', 'max:180'],
            'programStages.*.tipo_etapa' => ['required', 'in:REVISION,APROBACION'],
            'programStages.*.rol_revisor_id' => ['nullable', 'exists:roles,id'],
            'programStages.*.usuario_responsable_id' => ['nullable', 'exists:users,id'],
            'programStages.*.cargo_firma_id' => ['nullable', 'required_if:programStages.*.tipo_etapa,APROBACION', 'exists:cargo_firma,id'],
            'programStages.*.requiere_asignacion' => ['boolean'],
            'programStages.*.emisor_define_destinatario' => ['boolean'],
            'programStages.*.activo' => ['boolean'],
        ]);

        $validated['programStages'] = $this->prepareStagesForSave($validated['programStages'], 'REVISION');

        if ($this->hasDuplicateCargoFirmaEnEtapasActivas($validated['programStages'], 'programStages', soloAprobacion: true)) {
            return;
        }

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

    protected function loadFirstWorkflow(): void
    {
        if ($this->selectedSubactionId) {
            $this->loadWorkflowForSelectedSubaction();
            return;
        }

        $this->resetWorkflowForm();
    }

    protected function loadWorkflowForSelectedSubaction(): void
    {
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
            'codigo' => $this->automaticProgramFlowCode(),
            'nombre' => 'Flujo de aprobacion de programas',
            'proceso' => 'PROGRAMA',
            'descripcion' => '',
            'activo' => true,
        ];
        $this->programStages = [$this->blankProgramStage(1)];
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
            'codigo' => $this->automaticProgramFlowCode($flow->id),
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
            $this->programStages[] = $this->blankProgramStage(1);
        }
    }

    protected function blankProgramStage(int $order): array
    {
        return array_replace($this->blankStage($order), [
            'requiere_asignacion' => false,
            'usuario_responsable_id' => '',
        ]);
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

            $preparedStage = [
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

            $prepared[] = $preparedStage;
        }

        return $prepared;
    }

    protected function hasDuplicateCargoFirmaEnEtapasActivas(array $stages, string $field, bool $soloAprobacion = false): bool
    {
        $repetidos = collect($stages)
            ->filter(fn (array $stage) => (bool) ($stage['activo'] ?? true))
            ->filter(fn (array $stage) => ! $soloAprobacion || ($stage['tipo_etapa'] ?? null) === 'APROBACION')
            ->groupBy('cargo_firma_id')
            ->filter(fn ($grupo) => $grupo->count() > 1);

        if ($repetidos->isEmpty()) {
            return false;
        }

        $nombres = $repetidos->flatten(1)->pluck('nombre')->implode(', ');

        $this->addError(
            $field,
            "Varias etapas activas tienen el mismo cargo de firma asignado ({$nombres}). Cada etapa activa debe tener un cargo de firma distinto."
        );

        return true;
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
            $stageModel = $stage['id'] ? $existing->get((int) $stage['id']) : null;
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

    protected function fallbackCargoFirmaId(string $tipoEtapa = 'REVISION', mixed $currentCargoId = null): ?int
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
            ?? CargoFirma::where('descripcion', 'Proyecto')->orderBy('id')->value('id')
            ?? 0) ?: null;
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

    protected function automaticProgramFlowCode(?int $ignoreId = null): string
    {
        $tipoPrograma = $this->programSelectedTipoProgramaId
            ? TipoPrograma::find($this->programSelectedTipoProgramaId)
            : null;
        $nombre = $tipoPrograma?->nombre
            ? $this->normalizeCode(Str::ascii($tipoPrograma->nombre))
            : 'SIN_TIPO';

        return $this->generateUniqueFlowCode('PROGRAMA_'.$nombre, $ignoreId ?? $this->programWorkflowId);
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

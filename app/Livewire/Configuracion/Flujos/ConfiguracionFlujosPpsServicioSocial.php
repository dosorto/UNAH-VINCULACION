<?php

namespace App\Livewire\Configuracion\Flujos;

use App\Models\PpsServicioSocial;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class ConfiguracionFlujosPpsServicioSocial extends Component
{
    private const DEFAULT_CODE = 'PPS_SERVICIO_SOCIAL_DEFAULT';

    public ?int $workflowId = null;

    public array $workflow = [
        'codigo' => self::DEFAULT_CODE,
        'nombre' => 'Flujo PPS / Servicio Social',
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

    public function mount(): void
    {
        $this->loadWorkflow();
    }

    public function addStage(): void
    {
        $nextOrder = count($this->stages) + 1;
        $this->stages[] = $this->blankStage($nextOrder);
        $this->normalizeStageCodes();
    }

    public function removeStage(int $index): void
    {
        unset($this->stages[$index]);
        $this->stages = array_values($this->stages);
        $this->normalizeStageOrders();
    }

    public function moveStageUp(int $index): void
    {
        if ($index <= 0 || !isset($this->stages[$index], $this->stages[$index - 1])) {
            return;
        }

        [$this->stages[$index - 1], $this->stages[$index]] = [$this->stages[$index], $this->stages[$index - 1]];
        $this->stages = array_values($this->stages);
        $this->normalizeStageOrders();
    }

    public function moveStageDown(int $index): void
    {
        if (!isset($this->stages[$index], $this->stages[$index + 1])) {
            return;
        }

        [$this->stages[$index + 1], $this->stages[$index]] = [$this->stages[$index], $this->stages[$index + 1]];
        $this->stages = array_values($this->stages);
        $this->normalizeStageOrders();
    }

    public function cargarEtapasSugeridas(): void
    {
        $this->stages = $this->defaultStages();
        $this->resetErrorBag();
        session()->flash('status', 'Etapas sugeridas cargadas. Revise responsables y guarde el flujo.');
    }

    public function updated(string $property): void
    {
        if (!preg_match('/^stages\.(\d+)\.(.+)$/', $property, $matches)) {
            return;
        }

        $index = (int) $matches[1];
        $field = $matches[2];

        if (!isset($this->stages[$index])) {
            return;
        }

        if ($field === 'requiere_asignacion' && !($this->stages[$index]['requiere_asignacion'] ?? false)) {
            $this->stages[$index]['rol_revisor_id'] = '';
            $this->stages[$index]['usuario_responsable_id'] = '';
        }

        if ($field === 'es_estado_final_aprobado' && ($this->stages[$index]['es_estado_final_aprobado'] ?? false)) {
            $this->stages[$index]['estado_resultante'] = PpsServicioSocial::ESTADO_APROBADO;
            $this->stages[$index]['permite_edicion'] = false;
            $this->stages[$index]['permite_rechazo'] = false;
        }
    }

    public function save(): void
    {
        $this->resetErrorBag();
        $this->normalizeStageCodes();

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
        ], [], $this->validationAttributes());

        $preparedStages = $this->prepareStagesForSave($validated['stages']);

        if (!$this->validateBusinessRules($preparedStages)) {
            return;
        }

        if (!$this->cargoFirmaTecnicoId()) {
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

            $this->syncFlowStages($flow, $preparedStages);
            $this->workflowId = $flow->id;
        });

        $this->loadWorkflow();
        session()->flash('status', 'Flujo PPS / Servicio Social guardado correctamente.');
    }

    public function render(): View
    {
        $roles = Role::query()->orderBy('name')->get();
        $usuarios = User::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.configuracion.flujos.configuracion-flujos-pps-servicio-social', [
            'roles' => $roles,
            'usuarios' => $usuarios,
        ])->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    private function loadWorkflow(): void
    {
        $flow = FlujoAprobacion::with('etapas')
            ->where('proceso', PpsServicioSocial::PROCESO_FLUJO)
            ->where('codigo', self::DEFAULT_CODE)
            ->first()
            ?? FlujoAprobacion::with('etapas')
                ->where('proceso', PpsServicioSocial::PROCESO_FLUJO)
                ->orderByDesc('activo')
                ->orderBy('id')
                ->first();

        if (!$flow) {
            $this->workflowId = null;
            $this->workflow = [
                'codigo' => self::DEFAULT_CODE,
                'nombre' => 'Flujo PPS / Servicio Social',
                'descripcion' => 'Flujo configurable para revision del FORM-DVUS-015/016.',
                'activo' => true,
            ];
            $this->stages = $this->defaultStages();

            return;
        }

        $this->workflowId = $flow->id;
        $this->workflow = [
            'codigo' => $flow->codigo,
            'nombre' => $flow->nombre,
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
            $this->stages = $this->defaultStages();
        }
    }

    private function defaultStages(): array
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

    private function blankStage(int $order): array
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

    private function prepareStagesForSave(array $stages): array
    {
        return collect($stages)
            ->sortBy(fn (array $stage) => (int) ($stage['orden'] ?? 0))
            ->values()
            ->map(function (array $stage, int $index): array {
                $requiereAsignacion = (bool) ($stage['requiere_asignacion'] ?? false);

                return [
                    'id' => $stage['id'] ?? null,
                    'orden' => $index + 1,
                    'codigo' => $this->normalizeCode($stage['codigo'] ?? '') ?: 'ETAPA_'.($index + 1),
                    'nombre' => $stage['nombre'],
                    'tipo_etapa' => $stage['tipo_etapa'] ?? 'REVISION',
                    'rol_revisor_id' => $requiereAsignacion ? ($stage['rol_revisor_id'] ?: null) : null,
                    'usuario_responsable_id' => $requiereAsignacion ? ($stage['usuario_responsable_id'] ?: null) : null,
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

    private function validateBusinessRules(array $stages): bool
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

            if ($stage['activo'] && $stage['requiere_asignacion'] && !$stage['rol_revisor_id'] && !$stage['usuario_responsable_id']) {
                $this->addError($fieldPrefix.'.rol_revisor_id', 'Seleccione un rol o responsable para etapas que requieren asignacion.');
                $valid = false;
            }

            if ($stage['es_estado_final_aprobado'] && $stage['estado_resultante'] !== PpsServicioSocial::ESTADO_APROBADO) {
                $this->addError($fieldPrefix.'.estado_resultante', 'La etapa final aprobada debe usar estado aprobado.');
                $valid = false;
            }

            if ($stage['permite_edicion'] && !in_array($stage['estado_resultante'], ['borrador', 'subsanacion'], true)) {
                $this->addError($fieldPrefix.'.estado_resultante', 'Una etapa editable debe usar estado borrador o subsanacion.');
                $valid = false;
            }
        }

        return $valid;
    }

    private function syncFlowStages(FlujoAprobacion $flow, array $stages): void
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
                // TODO: Hacer cargo_firma_id nullable en una fase futura si el motor de flujos se desacopla por completo de Proyectos.
                'cargo_firma_id' => $this->cargoFirmaTecnicoId((int) ($existing->get((int) ($stage['id'] ?? 0))?->cargo_firma_id ?? 0)),
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

    private function normalizeStageCodes(): void
    {
        foreach ($this->stages as $index => $stage) {
            $this->stages[$index]['codigo'] = $this->normalizeCode($stage['codigo'] ?? '')
                ?: 'ETAPA_'.($index + 1);
        }
    }

    private function normalizeStageOrders(): void
    {
        foreach (array_values($this->stages) as $index => $stage) {
            $this->stages[$index] = $stage;
            $this->stages[$index]['orden'] = $index + 1;
        }
    }

    private function cargoFirmaTecnicoId(?int $currentCargoId = null): ?int
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

    private function normalizeCode(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^A-Z0-9]+/', '_', $value) ?? '';

        return trim($value, '_');
    }

    private function validationAttributes(): array
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
}

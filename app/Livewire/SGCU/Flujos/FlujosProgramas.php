<?php

namespace App\Livewire\SGCU\Flujos;

use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\SGCU\TipoPrograma;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

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

    public function updated(string $property): void
    {
        if (preg_match('/^stages\.(\d+)\.rol_revisor_id$/', $property, $matches)) {
            $this->clearInvalidResponsible((int) $matches[1]);
            return;
        }

        if (preg_match('/^stages\.(\d+)\.(requiere_asignacion|emisor_define_destinatario)$/', $property, $matches)) {
            $this->syncResponsibleAvailability((int) $matches[1]);
        }
    }

    public function save(): void
    {
        $this->workflow['codigo'] = $this->normalizeCode((string) ($this->workflow['codigo'] ?? ''))
            ?: $this->generateUniqueFlowCode($this->programFlowCodeBase(), $this->workflowId);

        $validated = $this->validate(
            [
                'workflow.codigo' => ['required', 'string', 'max:80', Rule::unique('flujos_aprobacion', 'codigo')->ignore($this->workflowId)],
                'workflow.nombre' => ['required', 'string', 'max:180'],
                'workflow.descripcion' => ['nullable', 'string'],
                'workflow.activo' => ['boolean'],
                'stages' => ['required', 'array', 'min:1'],
                'stages.*.codigo' => ['required', 'string', 'max:80'],
                'stages.*.nombre' => ['required', 'string', 'max:180'],
                'stages.*.cargo_firma_id' => ['required', 'exists:cargo_firma,id'],
                'stages.*.rol_revisor_id' => ['nullable', 'exists:roles,id'],
                'stages.*.usuario_responsable_id' => ['nullable', 'exists:users,id'],
                'stages.*.requiere_asignacion' => ['boolean'],
                'stages.*.emisor_define_destinatario' => ['boolean'],
                'stages.*.activo' => ['boolean'],
            ],
            [
                'workflow.codigo.unique' => 'Ya existe un flujo con este codigo. Cambie el codigo antes de guardar.',
            ]
        );

        if (! $this->selectedTipoProgramaId) {
            $this->addError('workflow.nombre', 'Seleccione un tipo de programa.');
            return;
        }

        DB::transaction(function () use ($validated) {
            $flow = FlujoAprobacion::updateOrCreate(
                $this->workflowId
                    ? ['id' => $this->workflowId]
                    : ['proceso' => 'PROGRAMA', 'tipo_programa_id' => $this->selectedTipoProgramaId],
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
                $rolId = $stage['rol_revisor_id'] ?: null;
                $responsableId = $stage['usuario_responsable_id'] ?: null;
                $requiereAsignacion = (bool) ($stage['requiere_asignacion'] ?? false);
                $emisorDefine = (bool) ($stage['emisor_define_destinatario'] ?? false);

                if ($requiereAsignacion) {
                    $responsableId = null;
                }

                if ($emisorDefine || ! $rolId || ! $this->userBelongsToRole($responsableId, $rolId)) {
                    $responsableId = null;
                }

                $flow->etapas()->create([
                    'orden' => $index + 1,
                    'codigo' => strtoupper(trim($stage['codigo'])),
                    'nombre' => $stage['nombre'],
                    'cargo_firma_id' => $stage['cargo_firma_id'],
                    'rol_revisor_id' => $rolId,
                    'usuario_responsable_id' => $responsableId,
                    'requiere_asignacion' => $requiereAsignacion,
                    'emisor_define_destinatario' => $emisorDefine,
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
        $roles = Role::query()->orderBy('name')->get();

        $cargos = CargoFirma::query()
            ->join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
            ->where('cargo_firma.descripcion', 'Proyecto')
            ->orderBy('tipo_cargo_firma.nombre')
            ->select('cargo_firma.*', 'tipo_cargo_firma.nombre as cargo_nombre')
            ->get();

        return view('livewire.sgcu.flujos.flujos-programas', [
            'tiposPrograma' => $tiposPrograma,
            'cargos' => $cargos,
            'selectedTipoPrograma' => $selectedTipoPrograma,
            'roles' => $roles,
            'usuariosPorRol' => $this->usersGroupedByRole($roles),
        ])
            ->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    protected function blankStage(int $order): array
    {
        return [
            'codigo' => 'ETAPA_' . $order,
            'nombre' => 'Etapa ' . $order,
            'cargo_firma_id' => null,
            'rol_revisor_id' => null,
            'usuario_responsable_id' => null,
            'requiere_asignacion' => true,
            'emisor_define_destinatario' => false,
            'activo' => true,
        ];
    }

    protected function resetWorkflowForm(): void
    {
        $this->workflowId = null;
        $this->workflow = [
            'codigo' => $this->generateUniqueFlowCode($this->programFlowCodeBase(), $this->workflowId),
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
                'rol_revisor_id' => (string) ($stage->rol_revisor_id ?? ''),
                'usuario_responsable_id' => (string) ($stage->usuario_responsable_id ?? ''),
                'requiere_asignacion' => (bool) $stage->requiere_asignacion,
                'emisor_define_destinatario' => (bool) $stage->emisor_define_destinatario,
                'activo' => (bool) $stage->activo,
            ])
            ->toArray();
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

    protected function clearInvalidResponsible(int $index): void
    {
        if (! isset($this->stages[$index])) {
            return;
        }

        $roleId = $this->stages[$index]['rol_revisor_id'] ?? null;
        $userId = $this->stages[$index]['usuario_responsable_id'] ?? null;

        if (! $roleId || ! $this->userBelongsToRole($userId, $roleId)) {
            $this->stages[$index]['usuario_responsable_id'] = '';
        }
    }

    protected function syncResponsibleAvailability(int $index): void
    {
        if (! isset($this->stages[$index])) {
            return;
        }

        if ($this->stages[$index]['requiere_asignacion'] ?? false) {
            $this->stages[$index]['usuario_responsable_id'] = '';
            return;
        }

        $this->stages[$index]['emisor_define_destinatario'] = false;
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

    protected function programFlowCodeBase(): string
    {
        $tipoPrograma = $this->selectedTipoProgramaId
            ? TipoPrograma::find($this->selectedTipoProgramaId)
            : null;

        return 'PROGRAMA_'.($tipoPrograma?->nombre ?: $this->selectedTipoProgramaId ?: 'DEFAULT');
    }

    protected function generateUniqueFlowCode(string $base, ?int $ignoreId = null): string
    {
        $base = $this->normalizeCode($base) ?: 'PROGRAMA';
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

    protected function normalizeCode(string $value): string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^A-Z0-9]+/', '_', $value) ?? '';

        return trim($value, '_');
    }
}

<?php

namespace App\Livewire\SGCU\Programas;

use App\Models\SGCU\ProgramaCertificacion;
use App\Models\SGCU\ProgramaRevision;
use App\Models\SGCU\TipoPrograma;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\User;
use App\Models\UnidadAcademica\FacultadCentro;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $programa = ProgramaCertificacion::with([
            'flujoAprobacion.etapas.rolRevisor',
            'flujoAprobacion.etapas.usuarioResponsable',
            'centroFacultad',
            'tipoPrograma',
            'centrosPrograma.centroFacultad',
            'asignaturasPrograma.asignatura',
        ])->findOrFail($programaId);

        if (! $programa->estaEditable()) {
            session()->flash('programas_status', 'Solo los programas en elaboracion o subsanacion pueden enviarse a revision.');
            return;
        }

        $flujo = $this->resolveWorkflow($programa);

        if (! $flujo || $flujo->etapas->isEmpty()) {
            session()->flash('programas_status', 'No hay un flujo activo configurado para este tipo de programa.');
            return;
        }

        DB::transaction(function () use ($programa, $flujo) {
            $nextCycle = $programa->revision_ciclo + 1;
            $stages = $flujo->etapas;

            if ($programa->tieneSubsanacionPendiente()) {
                $rejectedStage = $programa->revisiones()
                    ->where('revision_ciclo', $programa->revision_ciclo)
                    ->find($programa->subsanacion_revision_id);

                if ($rejectedStage) {
                    $stages = $programa->revisiones()
                        ->with(['flujoEtapa.rolRevisor', 'flujoEtapa.usuarioResponsable', 'asignadoUsuario'])
                        ->where('revision_ciclo', $programa->revision_ciclo)
                        ->where('orden', '>=', $rejectedStage->orden)
                        ->orderBy('orden')
                        ->get();
                }
            }

            foreach ($stages as $stage) {
                $flowStage = $stage instanceof ProgramaRevision ? $stage->flujoEtapa : $stage;
                $defaultReviewer = $flowStage instanceof FlujoAprobacionEtapa
                    ? $this->resolveDefaultReviewer($flowStage)
                    : $stage->asignadoUsuario;
                $requiresAssignment = (bool) ($flowStage?->requiere_asignacion ?? false);
                $stageOrder = $stage instanceof ProgramaRevision ? $stage->orden : $stage->orden;
                $stageCode = $stage instanceof ProgramaRevision ? $stage->etapa_codigo : $stage->codigo;
                $stageName = $stage instanceof ProgramaRevision ? $stage->etapa_nombre : $stage->nombre;
                $stageRole = $stage instanceof ProgramaRevision
                    ? $stage->rol_requerido
                    : $flowStage?->rolRevisor?->name;
                $responsableId = $stage instanceof ProgramaRevision
                    ? $stage->responsable_usuario_id
                    : $flowStage?->usuario_responsable_id;

                ProgramaRevision::create([
                    'programa_certificacion_id' => $programa->id,
                    'flujo_aprobacion_etapa_id' => $flowStage?->id,
                    'revision_ciclo' => $nextCycle,
                    'orden' => $stageOrder,
                    'etapa_codigo' => $stageCode,
                    'etapa_nombre' => $stageName,
                    'rol_requerido' => $stageRole,
                    'responsable_usuario_id' => $responsableId,
                    'asignado_usuario_id' => $requiresAssignment ? null : $defaultReviewer?->id,
                    'estado' => $requiresAssignment
                        ? 'PENDIENTE_ASIGNACION'
                        : ($defaultReviewer ? 'ASIGNADO' : 'PENDIENTE'),
                ]);
            }

            $programa->update([
                'estado_flujo' => 'EN_REVISION',
                'revision_ciclo' => $nextCycle,
                'enviado_revision_en' => now(),
                'observaciones_revision' => null,
                'subsanacion_revision_id' => null,
                'subsanacion_etapa_orden' => null,
                'subsanacion_etapa_nombre' => null,
                'subsanacion_devuelto_en' => null,
                'flujo_aprobacion_id' => $flujo->id,
                'modificado_por_usuario_id' => Auth::id(),
            ]);

            $this->syncCurrentVersionRecord($programa->fresh([
                'centroFacultad',
                'tipoPrograma',
                'centrosPrograma.centroFacultad',
                'asignaturasPrograma.asignatura',
            ]), 'EN_REVISION');
        });

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

        return view('livewire.sgcu.programas.list-programas', [
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

    protected function resolveWorkflow(ProgramaCertificacion $programa): ?FlujoAprobacion
    {
        if ($programa->flujoAprobacion?->exists) {
            return $programa->flujoAprobacion->load([
                'etapas' => fn ($query) => $query->where('activo', true)->orderBy('orden'),
                'etapas.rolRevisor',
                'etapas.usuarioResponsable',
            ]);
        }

        return FlujoAprobacion::query()
            ->with([
                'etapas' => fn ($query) => $query->where('activo', true)->orderBy('orden'),
                'etapas.rolRevisor',
                'etapas.usuarioResponsable',
            ])
            ->where('proceso', 'PROGRAMA')
            ->where('tipo_programa_id', $programa->tipo_programa_id)
            ->where('activo', true)
            ->first();
    }

    protected function resolveDefaultReviewer(FlujoAprobacionEtapa $stage): ?User
    {
        if ($stage->usuario_responsable_id && ! $stage->requiere_asignacion) {
            return $stage->usuarioResponsable;
        }

        if (! $stage->rolRevisor?->name) {
            return null;
        }

        return User::role($stage->rolRevisor->name)->orderBy('name')->first();
    }

    protected function syncCurrentVersionRecord(ProgramaCertificacion $programa, string $estado): void
    {
        $snapshot = $programa->buildVersionSnapshot();

        $programa->versiones()->updateOrCreate(
            ['numero_version' => $programa->version_actual],
            [
                'estado' => $estado,
                'vigente' => $estado === 'APROBADO',
                'publicado_en' => $estado === 'APROBADO' ? now() : null,
                'publicado_por_usuario_id' => $estado === 'APROBADO' ? Auth::id() : null,
                'datos_programa' => $snapshot['programa'],
                'centros_facultad' => $snapshot['centros_facultad'],
                'asignaturas' => $snapshot['asignaturas'],
            ]
        );
    }
}

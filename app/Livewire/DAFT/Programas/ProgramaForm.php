<?php

namespace App\Livewire\DAFT\Programas;

use App\Models\Asignatura;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\DAFT\ProgramaCertificacion;
use App\Models\DAFT\ProgramaRevision;
use App\Models\DAFT\TipoPrograma;
use App\Models\User;
use App\Models\UnidadAcademica\FacultadCentro;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProgramaForm extends Component
{
    use WithFileUploads;

    public ?int $programaId = null;

    public array $programaForm = [
        'centro_facultad_id' => null,
        'codigo' => '',
        'tipo_programa_id' => null,
        'nombre' => '',
        'descripcion' => '',
    ];

    public array $centros = [];
    public array $asignaturas = [];

    public ?int $selectedCentroId = null;
    public ?int $selectedAsignaturaId = null;
    public bool $editProgramModal = false;
    public bool $showCreateAsignaturaModal = false;
    public array $newAsignatura = [
        'codigo' => '',
        'nombre' => '',
        'creditos_academicos' => '',
        'horas_academicas' => '',
    ];
    public $newAsignaturaDocumento = null;

    protected array $validationAttributes = [
        'newAsignatura.codigo' => 'código',
        'newAsignatura.nombre' => 'nombre',
        'newAsignatura.creditos_academicos' => 'créditos',
        'newAsignatura.horas_academicas' => 'horas',
        'newAsignaturaDocumento' => 'documento de descripción mínima',
    ];

    protected array $messages = [
        'newAsignatura.codigo.required' => 'El código es obligatorio.',
        'newAsignatura.nombre.required' => 'El nombre es obligatorio.',
        'newAsignatura.creditos_academicos.required' => 'Los créditos son obligatorios.',
        'newAsignatura.horas_academicas.required' => 'Las horas son obligatorias.',
        'newAsignaturaDocumento.required' => 'El documento de descripción mínima es obligatorio.',
        'newAsignaturaDocumento.file' => 'El documento debe ser un archivo válido.',
        'newAsignaturaDocumento.mimes' => 'El documento debe ser PDF, DOC o DOCX.',
        'newAsignaturaDocumento.max' => 'El documento no debe pesar más de 10 MB.',
        'newAsignaturaDocumento.uploaded' => 'No se pudo subir el documento. Revisa el tamaño del archivo e inténtalo de nuevo.',
    ];

    public function mount(mixed $programa = null): void
    {
        if ($programa instanceof ProgramaCertificacion && $programa->exists) {
            $this->programaId = $programa->id;
            $this->fillFromPrograma($programa->load(['centrosPrograma.centroFacultad', 'asignaturasPrograma.asignatura']));
            return;
        }

        if (filled($programa) && $programa !== 'crear') {
            $programa = ProgramaCertificacion::with(['centrosPrograma.centroFacultad', 'asignaturasPrograma.asignatura'])
                ->findOrFail($programa);
            $this->programaId = $programa->id;
            $this->fillFromPrograma($programa);
            return;
        }

        $this->resetNewProgramForm();
    }

    public function savePrograma(): void
    {
        $validated = $this->validate([
            'programaForm.codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('programas_certificacion', 'codigo')->ignore($this->programaId),
            ],
            'programaForm.nombre' => ['required', 'string', 'max:200'],
            'programaForm.tipo_programa_id' => ['required', 'exists:tipos_programa,id'],
            'programaForm.centro_facultad_id' => ['required', 'exists:centro_facultad,id'],
            'programaForm.descripcion' => ['nullable', 'string'],
        ]);

        $payload = $validated['programaForm'];
        $payload['tipo_programa'] = TipoPrograma::find($payload['tipo_programa_id'])?->nombre;
        $payload['modificado_por_usuario_id'] = Auth::id();

        if (! $this->programaId) {
            $payload['creado_por_usuario_id'] = Auth::id();
            $payload['horas_maximas_programa'] = 0;
            $payload['version_actual'] = 1;
            $payload['estado'] = 1;
            $payload['estado_flujo'] = 'ELABORACION';
        }

        $programa = ProgramaCertificacion::updateOrCreate(['id' => $this->programaId], $payload);
        $this->programaId = $programa->id;

        $this->syncProgramaRelations($programa);
        session()->flash('programas_status', 'Programa guardado correctamente.');

        $this->redirectRoute('daft.programas.edit', $programa, navigate: true);
    }

    public function openEditProgramModal(): void
    {
        $this->editProgramModal = true;
    }

    public function closeEditProgramModal(): void
    {
        $this->editProgramModal = false;
    }

    public function addCentro(): void
    {
        $this->validate(['selectedCentroId' => ['required', 'exists:centro_facultad,id']]);

        if (collect($this->centros)->contains('centro_facultad_id', (int) $this->selectedCentroId)) {
            $this->selectedCentroId = null;
            return;
        }

        $centro = FacultadCentro::findOrFail($this->selectedCentroId);
        $this->centros[] = [
            'centro_facultad_id' => $centro->id,
            'nombre' => $centro->nombre,
            'activo' => true,
        ];
        $this->selectedCentroId = null;
        $this->autoSaveRelations();
    }

    public function removeCentro(int $index): void
    {
        unset($this->centros[$index]);
        $this->centros = array_values($this->centros);
        $this->autoSaveRelations();
    }

    public function addAsignatura(): void
    {
        $this->validate(['selectedAsignaturaId' => ['required', 'exists:asignaturas,id']]);

        if (collect($this->asignaturas)->contains('asignatura_id', (int) $this->selectedAsignaturaId)) {
            $this->selectedAsignaturaId = null;
            return;
        }

        $asignatura = Asignatura::findOrFail($this->selectedAsignaturaId);
        $this->attachAsignatura($asignatura);
        $this->selectedAsignaturaId = null;
        $this->autoSaveRelations();
    }

    public function openCreateAsignaturaModal(): void
    {
        $this->resetErrorBag([
            'newAsignatura.codigo',
            'newAsignatura.nombre',
            'newAsignatura.creditos_academicos',
            'newAsignatura.horas_academicas',
            'newAsignaturaDocumento',
        ]);
        $this->showCreateAsignaturaModal = true;
    }

    public function closeCreateAsignaturaModal(): void
    {
        $this->showCreateAsignaturaModal = false;
        $this->newAsignatura = [
            'codigo' => '',
            'nombre' => '',
            'creditos_academicos' => '',
            'horas_academicas' => '',
        ];
        $this->newAsignaturaDocumento = null;
        $this->resetErrorBag([
            'newAsignatura.codigo',
            'newAsignatura.nombre',
            'newAsignatura.creditos_academicos',
            'newAsignatura.horas_academicas',
            'newAsignaturaDocumento',
        ]);
    }

    public function createAsignaturaAndAttach(): void
    {
        $validated = $this->validate([
            'newAsignatura.codigo' => ['required', 'string', 'max:50', Rule::unique('asignaturas', 'codigo')],
            'newAsignatura.nombre' => ['required', 'string', 'max:255'],
            'newAsignatura.creditos_academicos' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'newAsignatura.horas_academicas' => ['required', 'integer', 'min:1', 'max:9999'],
            'newAsignaturaDocumento' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $documentoPath = $this->newAsignaturaDocumento
            ? $this->newAsignaturaDocumento->store('asignaturas/descripciones-minimas', 'public')
            : null;

        $asignatura = Asignatura::create($validated['newAsignatura'] + [
            'ruta_documento_descripcion_minima' => $documentoPath,
            'activa' => true,
        ]);

        $this->attachAsignatura($asignatura);
        $this->autoSaveRelations();
        $this->closeCreateAsignaturaModal();
        session()->flash('programas_status', 'Asignatura creada y agregada al programa.');
    }

    public function removeAsignatura(int $index): void
    {
        unset($this->asignaturas[$index]);
        $this->asignaturas = array_values($this->asignaturas);

        foreach ($this->asignaturas as $position => &$asignatura) {
            $asignatura['orden'] = $position + 1;
        }

        $this->autoSaveRelations();
    }

    public function deleteDraft(): void
    {
        if (! $this->programaId) {
            return;
        }

        ProgramaCertificacion::findOrFail($this->programaId)->delete();
        session()->flash('programas_status', 'Programa eliminado.');
        $this->redirectRoute('daft.programas', navigate: true);
    }

    public function sendToReview(): void
    {
        if (! $this->programaId) {
            return;
        }

        $programa = ProgramaCertificacion::with([
            'flujoAprobacion.etapas.rolRevisor',
            'flujoAprobacion.etapas.usuarioResponsable',
            'centroFacultad',
            'tipoPrograma',
            'centrosPrograma.centroFacultad',
            'asignaturasPrograma.asignatura',
        ])->findOrFail($this->programaId);
        $setup = $this->draftSetup($programa);

        if (! $setup['ready_for_review']) {
            session()->flash('programas_status', 'Completa asignaturas, centros y horas válidas antes de enviar a revisión.');
            return;
        }

        if (! $programa->estaEditable()) {
            session()->flash('programas_status', 'Solo los programas en elaboración o subsanación pueden enviarse a revisión.');
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

        session()->flash('programas_status', 'Programa enviado a revisión.');
        $this->redirectRoute('daft.programas', navigate: true);
    }

    public function updatedAsignaturas(): void
    {
        $this->autoSaveRelations();
    }

    public function render(): View
    {
        $programa = $this->programaId
            ? ProgramaCertificacion::with(['centroFacultad', 'tipoPrograma', 'centrosPrograma.centroFacultad', 'asignaturasPrograma.asignatura', 'versiones'])->find($this->programaId)
            : null;
        $currentTipoPrograma = filled($this->programaForm['tipo_programa_id'])
            ? TipoPrograma::find($this->programaForm['tipo_programa_id'])
            : $programa?->tipoPrograma;

        return view('livewire.daft.programas.programa-form', [
            'programa' => $programa,
            'tiposPrograma' => TipoPrograma::where('activo', true)->orderBy('nombre')->get(),
            'centrosFacultad' => FacultadCentro::orderBy('nombre')->get(),
            'asignaturasDisponibles' => Asignatura::query()->where('activa', true)->orderBy('nombre')->get(),
            'currentTipoPrograma' => $currentTipoPrograma,
            'hoursStatus' => $this->hoursStatus($currentTipoPrograma),
            'draftSetup' => $programa ? $this->draftSetup($programa) : null,
            'timelineEntries' => $this->timelineEntries($programa),
            'timelineProgress' => $this->timelineProgress($programa),
            'activityFeed' => $this->activityFeed($programa),
            'isEditableState' => ! $programa || $programa->estaEditable(),
        ])->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    protected function fillFromPrograma(ProgramaCertificacion $programa): void
    {
        $this->programaForm = [
            'centro_facultad_id' => $programa->centro_facultad_id,
            'codigo' => $programa->codigo,
            'tipo_programa_id' => $programa->tipo_programa_id,
            'nombre' => $programa->nombre,
            'descripcion' => $programa->descripcion ?? '',
        ];

        $this->centros = $programa->centrosPrograma
            ->map(fn ($centro) => [
                'centro_facultad_id' => $centro->centro_facultad_id,
                'nombre' => $centro->centroFacultad?->nombre,
                'activo' => (bool) $centro->activo,
            ])
            ->values()
            ->all();

        $this->asignaturas = $programa->asignaturasPrograma
            ->map(fn ($programaAsignatura) => [
                'asignatura_id' => $programaAsignatura->asignatura_id,
                'codigo' => $programaAsignatura->asignatura?->codigo,
                'nombre' => $programaAsignatura->asignatura?->nombre,
                'creditos_academicos' => $programaAsignatura->asignatura?->creditos_academicos,
                'horas_academicas' => $programaAsignatura->asignatura?->horas_academicas,
                'orden' => $programaAsignatura->orden,
                'es_obligatoria' => (bool) $programaAsignatura->es_obligatoria,
            ])
            ->values()
            ->all();
    }

    protected function resetNewProgramForm(): void
    {
        $this->programaId = null;
        $this->selectedCentroId = null;
        $this->selectedAsignaturaId = null;
        $this->centros = [];
        $this->asignaturas = [];
        $this->programaForm = [
            'centro_facultad_id' => null,
            'codigo' => '',
            'tipo_programa_id' => null,
            'nombre' => '',
            'descripcion' => '',
        ];
        $this->resetErrorBag();
    }

    protected function autoSaveRelations(): void
    {
        if (! $this->programaId) {
            return;
        }

        $programa = ProgramaCertificacion::findOrFail($this->programaId);
        $this->syncProgramaRelations($programa);
    }

    protected function syncProgramaRelations(ProgramaCertificacion $programa): void
    {
        DB::transaction(function () use ($programa) {
            $programa->centrosPrograma()->delete();
            foreach ($this->centros as $centro) {
                if (! filled($centro['centro_facultad_id'] ?? null)) {
                    continue;
                }

                $programa->centrosPrograma()->create([
                    'centro_facultad_id' => $centro['centro_facultad_id'],
                    'activo' => (bool) ($centro['activo'] ?? true),
                ]);
            }

            $programa->asignaturasPrograma()->delete();
            foreach ($this->asignaturas as $position => $asignatura) {
                if (! filled($asignatura['asignatura_id'] ?? null)) {
                    continue;
                }

                $programa->asignaturasPrograma()->create([
                    'asignatura_id' => $asignatura['asignatura_id'],
                    'orden' => (int) ($asignatura['orden'] ?? ($position + 1)),
                    'es_obligatoria' => (bool) ($asignatura['es_obligatoria'] ?? true),
                ]);
            }

            $programa->update([
                'horas_maximas_programa' => collect($this->asignaturas)->sum(fn ($item) => (int) ($item['horas_academicas'] ?? 0)),
                'modificado_por_usuario_id' => Auth::id(),
            ]);
        });
    }

    protected function attachAsignatura(Asignatura $asignatura): void
    {
        if (collect($this->asignaturas)->contains('asignatura_id', $asignatura->id)) {
            return;
        }

        $this->asignaturas[] = [
            'asignatura_id' => $asignatura->id,
            'codigo' => $asignatura->codigo,
            'nombre' => $asignatura->nombre,
            'creditos_academicos' => $asignatura->creditos_academicos,
            'horas_academicas' => $asignatura->horas_academicas,
            'orden' => count($this->asignaturas) + 1,
            'es_obligatoria' => true,
        ];
    }

    protected function hoursStatus(?TipoPrograma $tipoPrograma): array
    {
        $total = (int) collect($this->asignaturas)->sum(fn ($item) => (int) ($item['horas_academicas'] ?? 0));
        $min = (int) ($tipoPrograma?->horas_minimas ?? 0);
        $max = $tipoPrograma?->horas_maximas;
        $inRange = $tipoPrograma
            ? $total >= $min && (is_null($max) || $total <= (int) $max)
            : $total > 0;

        return [
            'total' => $total,
            'min' => $min,
            'max' => $max,
            'in_range' => $inRange,
            'range_label' => $tipoPrograma ? $min . ' - ' . ($max ?? 'N/D') . ' horas' : 'Sin tipo seleccionado',
            'message' => $inRange
                ? 'Las horas acumuladas cumplen el rango permitido.'
                : 'Las horas acumuladas del programa están fuera del rango permitido para este tipo.',
        ];
    }

    protected function draftSetup(ProgramaCertificacion $programa): array
    {
        $hoursStatus = $this->hoursStatus($programa->tipoPrograma);
        $asignaturasCount = count($this->asignaturas);
        $centrosCount = count($this->centros);

        return [
            'asignaturas_count' => $asignaturasCount,
            'centros_count' => $centrosCount,
            'asignaturas_completed' => $asignaturasCount > 0,
            'centros_completed' => $centrosCount > 0,
            'ready_for_review' => $asignaturasCount > 0 && $centrosCount > 0 && $hoursStatus['in_range'],
        ];
    }

    protected function timelineEntries(?ProgramaCertificacion $programa): array
    {
        if (! $programa) {
            return [['title' => 'Creación del programa', 'status' => 'PENDIENTE', 'tone' => 'slate']];
        }

        return [
            ['title' => 'Creación del programa', 'status' => 'COMPLETADO', 'tone' => 'sky'],
            ['title' => 'Construcción académica', 'status' => $programa->estaEditable() ? 'ACTUAL' : 'COMPLETADO', 'tone' => $programa->estaEditable() ? 'sky' : 'emerald'],
            ['title' => 'Revisión institucional', 'status' => $programa->estado_flujo === 'EN_REVISION' ? 'ACTUAL' : 'PENDIENTE', 'tone' => $programa->estado_flujo === 'EN_REVISION' ? 'sky' : 'slate'],
            ['title' => 'Aprobación final', 'status' => $programa->estado_flujo === 'APROBADO' ? 'COMPLETADO' : 'PENDIENTE', 'tone' => $programa->estado_flujo === 'APROBADO' ? 'emerald' : 'slate'],
        ];
    }

    protected function timelineProgress(?ProgramaCertificacion $programa): string
    {
        if (! $programa) {
            return 'Etapa 1/4';
        }

        $current = match ($programa->estado_flujo) {
            'EN_REVISION' => 3,
            'APROBADO' => 4,
            default => 2,
        };

        return 'Etapa ' . $current . '/4';
    }

    protected function activityFeed(?ProgramaCertificacion $programa): array
    {
        if (! $programa) {
            return [];
        }

        $feed = [[
            'title' => 'Programa creado',
            'description' => 'Registro inicial del programa.',
            'at' => $programa->created_at,
        ]];

        if ($programa->enviado_revision_en) {
            $feed[] = [
                'title' => 'Enviado a revisión',
                'description' => 'El programa entró al flujo institucional.',
                'at' => $programa->enviado_revision_en,
            ];
        }

        return $feed;
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

        if ($stage->rol_revisor_id) {
            $preferredReviewer = User::role($stage->rolRevisor->name)
                ->where('active_role_id', $stage->rol_revisor_id)
                ->orderBy('name')
                ->first();

            if ($preferredReviewer) {
                return $preferredReviewer;
            }
        }

        return User::role($stage->rolRevisor->name)
            ->orderBy('name')
            ->first();
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

<?php

namespace App\Livewire\DAFT\Programas;

use App\Models\Asignatura;
use App\Models\DAFT\ProgramaAsignatura;
use App\Models\DAFT\ProgramaCertificacion;
use App\Models\DAFT\ProgramaRevision;
use App\Models\DAFT\TipoPrograma;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Services\DAFT\ProgramaWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public bool $showEditAsignaturaModal = false;

    public ?int $editingAsignaturaId = null;

    public array $editingAsignatura = [
        'codigo' => '',
        'nombre' => '',
        'creditos_academicos' => '',
        'horas_academicas' => '',
        'ruta_documento_descripcion_minima' => null,
    ];

    public $editingAsignaturaDocumento = null;

    public bool $showPrerequisitosModal = false;

    public ?int $prerequisiteAsignaturaId = null;

    public array $selectedPrerequisiteIds = [];

    public bool $showSendReviewModal = false;

    public array $reviewRecipientStages = [];

    public array $reviewRecipients = [];

    protected array $validationAttributes = [
        'newAsignatura.codigo' => 'código',
        'newAsignatura.nombre' => 'nombre',
        'newAsignatura.creditos_academicos' => 'créditos',
        'newAsignatura.horas_academicas' => 'horas',
        'newAsignaturaDocumento' => 'documento de descripción mínima',
        'editingAsignatura.codigo' => 'código',
        'editingAsignatura.nombre' => 'nombre',
        'editingAsignatura.creditos_academicos' => 'créditos',
        'editingAsignatura.horas_academicas' => 'horas',
        'editingAsignaturaDocumento' => 'documento de descripción mínima',
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
        'editingAsignaturaDocumento.mimes' => 'El documento debe ser PDF, DOC o DOCX.',
        'editingAsignaturaDocumento.max' => 'El documento no debe pesar más de 10 MB.',
        'editingAsignaturaDocumento.uploaded' => 'No se pudo subir el documento. Revisa el tamaño del archivo e inténtalo de nuevo.',
    ];

    public function mount(mixed $programa = null): void
    {
        if ($programa instanceof ProgramaCertificacion && $programa->exists) {
            $this->programaId = $programa->id;
            $this->fillFromPrograma($programa->load(['centrosPrograma.centroFacultad', 'asignaturasPrograma.asignatura.prerrequisitos']));

            return;
        }

        if (filled($programa) && $programa !== 'crear') {
            $programa = ProgramaCertificacion::with(['centrosPrograma.centroFacultad', 'asignaturasPrograma.asignatura.prerrequisitos'])
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

    public function openEditAsignaturaModal(int $asignaturaId): void
    {
        $asignatura = $this->programSubject($asignaturaId);

        $this->resetErrorBag();
        $this->editingAsignaturaId = $asignatura->id;
        $this->editingAsignatura = [
            'codigo' => $asignatura->codigo ?? '',
            'nombre' => $asignatura->nombre,
            'creditos_academicos' => $asignatura->creditos_academicos,
            'horas_academicas' => $asignatura->horas_academicas,
            'ruta_documento_descripcion_minima' => $asignatura->ruta_documento_descripcion_minima,
        ];
        $this->editingAsignaturaDocumento = null;
        $this->showEditAsignaturaModal = true;
    }

    public function closeEditAsignaturaModal(): void
    {
        $this->showEditAsignaturaModal = false;
        $this->editingAsignaturaId = null;
        $this->editingAsignaturaDocumento = null;
        $this->resetErrorBag();
    }

    public function updateAsignatura(): void
    {
        $asignatura = $this->programSubject((int) $this->editingAsignaturaId);
        $validated = $this->validate([
            'editingAsignatura.codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('asignaturas', 'codigo')->ignore($asignatura->id),
            ],
            'editingAsignatura.nombre' => ['required', 'string', 'max:255'],
            'editingAsignatura.creditos_academicos' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'editingAsignatura.horas_academicas' => ['required', 'integer', 'min:1', 'max:9999'],
            'editingAsignaturaDocumento' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $payload = collect($validated['editingAsignatura'])
            ->except('ruta_documento_descripcion_minima')
            ->all();

        if ($this->editingAsignaturaDocumento) {
            $payload['ruta_documento_descripcion_minima'] = $this->editingAsignaturaDocumento
                ->store('asignaturas/descripciones-minimas', 'public');
        }

        $asignatura->update($payload);
        $this->refreshLocalAsignatura($asignatura->fresh('prerrequisitos'));
        $this->autoSaveRelations();
        $this->closeEditAsignaturaModal();
        session()->flash('programas_status', 'Asignatura actualizada correctamente.');
    }

    public function openPrerequisitosModal(int $asignaturaId): void
    {
        $asignatura = $this->programSubject($asignaturaId)->load('prerrequisitos');

        $this->resetErrorBag('selectedPrerequisiteIds');
        $this->prerequisiteAsignaturaId = $asignatura->id;
        $this->selectedPrerequisiteIds = $asignatura->prerrequisitos
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->showPrerequisitosModal = true;
    }

    public function closePrerequisitosModal(): void
    {
        $this->showPrerequisitosModal = false;
        $this->prerequisiteAsignaturaId = null;
        $this->selectedPrerequisiteIds = [];
        $this->resetErrorBag('selectedPrerequisiteIds');
    }

    public function savePrerequisitos(): void
    {
        $asignatura = $this->programSubject((int) $this->prerequisiteAsignaturaId);
        $validated = $this->validate([
            'selectedPrerequisiteIds' => ['array'],
            'selectedPrerequisiteIds.*' => ['integer', 'distinct', 'exists:asignaturas,id'],
        ]);
        $selectedIds = collect($validated['selectedPrerequisiteIds'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $allowedIds = collect($this->asignaturas)
            ->pluck('asignatura_id')
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === $asignatura->id);

        if ($selectedIds->diff($allowedIds)->isNotEmpty()) {
            $this->addError('selectedPrerequisiteIds', 'Solo puedes seleccionar otras asignaturas de este programa.');

            return;
        }

        $asignatura->prerrequisitos()->sync($selectedIds->all());
        $this->refreshLocalAsignatura($asignatura->fresh('prerrequisitos'));
        $this->closePrerequisitosModal();
        session()->flash('programas_status', 'Requisitos actualizados correctamente.');
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

    public function downloadTemplate(): StreamedResponse
    {
        $programa = ProgramaCertificacion::with('tipoPrograma')->findOrFail($this->programaId);
        $tipoPrograma = $programa->tipoPrograma;
        $path = $tipoPrograma?->plantilla_docx_path;

        abort_if(! $path || ! Storage::disk('public')->exists($path), 404, 'La plantilla no está disponible.');

        return Storage::disk('public')->download(
            $path,
            'Formato-'.Str::slug($tipoPrograma->nombre).'.docx'
        );
    }

    public function openSendReviewModal(): void
    {
        if (! $this->programaId) {
            return;
        }

        $programa = ProgramaCertificacion::with('tipoPrograma')->findOrFail($this->programaId);
        $setup = $this->draftSetup($programa);

        if (! $setup['ready_for_review']) {
            session()->flash('programas_status', 'Completa asignaturas, centros y horas válidas antes de enviar a revisión.');

            return;
        }

        $workflow = app(ProgramaWorkflowService::class);
        $recipientStages = $workflow->etapasConDestinatarioDefinidoPorEmisor($programa);

        if ($recipientStages->isEmpty()) {
            $this->sendToReview();

            return;
        }

        $this->reviewRecipientStages = $recipientStages
            ->map(function ($stage): array {
                $users = User::query()
                    ->select(['id', 'name', 'email', 'active_role_id'])
                    ->when($stage->rol_revisor_id, fn ($query) => $query->whereHas(
                        'roles',
                        fn ($roleQuery) => $roleQuery->where('roles.id', $stage->rol_revisor_id)
                    ))
                    ->orderByRaw('CASE WHEN active_role_id = ? THEN 0 ELSE 1 END', [$stage->rol_revisor_id ?: 0])
                    ->orderBy('name')
                    ->get()
                    ->map(fn (User $user): array => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'active_role' => (int) $user->active_role_id === (int) $stage->rol_revisor_id,
                    ])
                    ->all();

                return [
                    'id' => $stage->id,
                    'order' => $stage->orden,
                    'name' => $stage->nombre,
                    'role' => $stage->rolRevisor?->name ?? 'Sin rol específico',
                    'users' => $users,
                ];
            })
            ->all();
        $this->reviewRecipients = [];
        $this->resetErrorBag('reviewRecipients');
        $this->showSendReviewModal = true;
    }

    public function closeSendReviewModal(): void
    {
        $this->showSendReviewModal = false;
        $this->reviewRecipientStages = [];
        $this->reviewRecipients = [];
        $this->resetErrorBag('reviewRecipients');
    }

    public function sendToReview(): void
    {
        if (! $this->programaId) {
            return;
        }

        $programa = ProgramaCertificacion::with('tipoPrograma')->findOrFail($this->programaId);
        $setup = $this->draftSetup($programa);

        if (! $setup['ready_for_review']) {
            session()->flash('programas_status', 'Completa asignaturas, centros y horas válidas antes de enviar a revisión.');

            return;
        }

        if ($this->reviewRecipientStages !== []) {
            $rules = [];
            foreach ($this->reviewRecipientStages as $stage) {
                $rules['reviewRecipients.'.$stage['id']] = ['required', 'integer', 'exists:users,id'];
            }
            $this->validate($rules, [
                'reviewRecipients.*.required' => 'Selecciona el destinatario de esta etapa.',
                'reviewRecipients.*.exists' => 'El destinatario seleccionado ya no está disponible.',
            ]);
        }

        try {
            app(ProgramaWorkflowService::class)->enviarARevision($programa, Auth::user(), $this->reviewRecipients);
        } catch (\DomainException $exception) {
            $this->addError('reviewRecipients', $exception->getMessage());

            return;
        }

        $this->closeSendReviewModal();
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
            ? ProgramaCertificacion::with([
                'centroFacultad',
                'tipoPrograma',
                'centrosPrograma.centroFacultad',
                'asignaturasPrograma.asignatura.prerrequisitos',
                'versiones',
                'revisiones.flujoEtapa.rolRevisor',
                'revisiones.flujoEtapa.usuarioResponsable',
                'revisiones.asignadoUsuario',
                'revisiones.responsableUsuario',
                'revisiones.decididoPorUsuario',
            ])->find($this->programaId)
            : null;
        $currentTipoPrograma = filled($this->programaForm['tipo_programa_id'])
            ? TipoPrograma::find($this->programaForm['tipo_programa_id'])
            : $programa?->tipoPrograma;

        $timelineEntries = $this->timelineEntries($programa);

        return view('livewire.daft.programas.programa-form', [
            'programa' => $programa,
            'tiposPrograma' => TipoPrograma::where('activo', true)->orderBy('nombre')->get(),
            'centrosFacultad' => FacultadCentro::orderBy('nombre')->get(),
            'asignaturasDisponibles' => Asignatura::query()->where('activa', true)->orderBy('nombre')->get(),
            'currentTipoPrograma' => $currentTipoPrograma,
            'hoursStatus' => $this->hoursStatus($currentTipoPrograma),
            'draftSetup' => $programa ? $this->draftSetup($programa) : null,
            'timelineEntries' => $timelineEntries,
            'timelineProgress' => $this->timelineProgress($timelineEntries),
            'activityFeed' => $this->activityFeed($programa),
            'isEditableState' => ! $programa || $programa->estaEditable(),
            'prerequisiteCandidates' => collect($this->asignaturas)
                ->reject(fn (array $item) => (int) ($item['asignatura_id'] ?? 0) === $this->prerequisiteAsignaturaId)
                ->values(),
            'prerequisiteSubject' => collect($this->asignaturas)
                ->firstWhere('asignatura_id', $this->prerequisiteAsignaturaId),
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
                'ruta_documento_descripcion_minima' => $programaAsignatura->asignatura?->ruta_documento_descripcion_minima,
                'prerrequisitos' => $programaAsignatura->asignatura?->prerrequisitos
                    ?->map(fn (Asignatura $prerrequisito) => [
                        'id' => $prerrequisito->id,
                        'codigo' => $prerrequisito->codigo,
                        'nombre' => $prerrequisito->nombre,
                    ])->values()->all() ?? [],
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
            'ruta_documento_descripcion_minima' => $asignatura->ruta_documento_descripcion_minima,
            'prerrequisitos' => [],
            'orden' => count($this->asignaturas) + 1,
            'es_obligatoria' => true,
        ];
    }

    protected function programSubject(int $asignaturaId): Asignatura
    {
        abort_unless($this->programaId && $asignaturaId > 0, 404);

        $programa = ProgramaCertificacion::findOrFail($this->programaId);
        abort_unless($programa->estaEditable(), 422, 'El programa ya no se puede editar.');
        abort_unless(ProgramaAsignatura::query()
            ->where('programa_certificacion_id', $this->programaId)
            ->where('asignatura_id', $asignaturaId)
            ->exists(), 404);

        return Asignatura::findOrFail($asignaturaId);
    }

    protected function refreshLocalAsignatura(Asignatura $asignatura): void
    {
        foreach ($this->asignaturas as &$item) {
            if ((int) ($item['asignatura_id'] ?? 0) !== $asignatura->id) {
                continue;
            }

            $item['codigo'] = $asignatura->codigo;
            $item['nombre'] = $asignatura->nombre;
            $item['creditos_academicos'] = $asignatura->creditos_academicos;
            $item['horas_academicas'] = $asignatura->horas_academicas;
            $item['ruta_documento_descripcion_minima'] = $asignatura->ruta_documento_descripcion_minima;
            $item['prerrequisitos'] = $asignatura->prerrequisitos
                ->map(fn (Asignatura $prerrequisito) => [
                    'id' => $prerrequisito->id,
                    'codigo' => $prerrequisito->codigo,
                    'nombre' => $prerrequisito->nombre,
                ])->values()->all();

            break;
        }
        unset($item);
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
            'range_label' => $tipoPrograma ? $min.' - '.($max ?? 'N/D').' horas' : 'Sin tipo seleccionado',
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
            return [];
        }

        $flow = app(ProgramaWorkflowService::class)->resolverFlujo($programa);
        $revisiones = $programa->revisiones
            ->sortByDesc(fn (ProgramaRevision $revision) => ($revision->revision_ciclo * 1_000_000_000) + $revision->id);
        $ultimaPorEtapa = $revisiones
            ->filter(fn (ProgramaRevision $revision) => $revision->flujo_aprobacion_etapa_id)
            ->unique('flujo_aprobacion_etapa_id')
            ->keyBy('flujo_aprobacion_etapa_id');
        $revisionActual = $programa->etapaActual();

        $entries = [];

        foreach (($flow?->etapas ?? collect())->values() as $index => $stage) {
            $revision = $ultimaPorEtapa->get($stage->id);
            $isCurrent = $revision !== null && $revisionActual?->id === $revision->id;
            $isWaitingToSend = $index === 0
                && $programa->estado_flujo === 'ELABORACION'
                && $programa->revisiones->isEmpty();
            $status = match (true) {
                $programa->estado_flujo === 'APROBADO', $revision?->estado === 'APROBADO' => 'COMPLETADO',
                $revision?->estado === 'RECHAZADO' => 'RECHAZADO',
                $isCurrent => str_replace('_', ' ', $revision->estado),
                $isWaitingToSend => 'POR ENVIAR',
                default => 'PENDIENTE',
            };
            $tone = match (true) {
                $status === 'COMPLETADO' => 'emerald',
                $status === 'RECHAZADO' => 'rose',
                $isCurrent, $isWaitingToSend => 'sky',
                default => 'slate',
            };
            $assignee = $revision?->asignadoUsuario?->name
                ?? $revision?->responsableUsuario?->name
                ?? $stage->usuarioResponsable?->name
                ?? $stage->rolRevisor?->name;

            $entries[] = [
                'title' => $stage->nombre,
                'status' => $status,
                'tone' => $tone,
                'assignee' => $assignee,
                'cycle' => $revision?->revision_ciclo,
                'description' => $stage->tipo_etapa === 'APROBACION' ? 'Aprobación institucional del programa.' : 'Revisión institucional del programa.',
            ];
        }

        return $entries;
    }

    protected function timelineProgress(array $entries): string
    {
        if ($entries === []) {
            return 'Sin flujo';
        }

        $current = collect($entries)->search(fn (array $entry) => $entry['tone'] === 'sky' || $entry['tone'] === 'rose');
        $current = $current === false ? count($entries) : $current + 1;

        return 'Etapa '.$current.'/'.count($entries);
    }

    protected function activityFeed(?ProgramaCertificacion $programa): array
    {
        if (! $programa) {
            return [];
        }

        $feed = [[
            'title' => 'Programa creado',
            'description' => 'Se registró el programa '.$programa->codigo.'.',
            'at' => $programa->created_at,
        ]];

        foreach ($programa->revisiones->groupBy('revision_ciclo') as $cycle => $revisiones) {
            $feed[] = [
                'title' => 'Enviado a revisión',
                'description' => 'El programa entró al ciclo '.$cycle.' del flujo institucional.',
                'at' => $revisiones->min('created_at'),
            ];

            foreach ($revisiones->whereIn('estado', ['APROBADO', 'RECHAZADO']) as $revision) {
                $decision = $revision->estado === 'APROBADO' ? 'Etapa aprobada' : 'Devuelto para subsanación';
                $description = $revision->etapa_nombre;
                if ($revision->decididoPorUsuario?->name) {
                    $description .= ' · '.$revision->decididoPorUsuario->name;
                }
                if (filled($revision->observaciones)) {
                    $description .= ' · '.$revision->observaciones;
                }

                $feed[] = [
                    'title' => $decision,
                    'description' => $description,
                    'at' => $revision->firmado_en ?? $revision->updated_at,
                ];
            }
        }

        return collect($feed)->sortByDesc('at')->values()->all();
    }
}

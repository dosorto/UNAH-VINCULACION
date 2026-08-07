<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\ENF\EnfAccion;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Categoria;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\Modalidad;
use App\Models\Proyecto\Od;
use App\Models\Proyecto\Proyecto;
use App\Services\Proyecto\ProyectoLegacyWorkflowAdoptionService;
use App\Support\AdminCsv;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ListProyectosVinculacion extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterEstado = '';
    public string $filterCategoria = '';
    public string $filterModalidad = '';
    public string $filterOds = '';
    public string $filterFechaInicio = '';
    public string $filterFechaFin = '';
    public ?int $filterCentroFacultad = null;
    public ?int $filterDepartamento = null;

    public bool $viewModal = false;
    public ?int $viewProyectoId = null;

    public bool $firmasModal = false;
    public ?int $firmasProyectoId = null;
    public ?int $firmas_jefe_id = null;
    public ?int $firmas_director_id = null;
    public ?int $firmas_enlace_id = null;

    public bool $flowModal = false;
    public ?int $flowProyectoId = null;
    public ?int $flowSelectedId = null;
    public bool $flowIsLegacyAdoption = false;
    public bool $flowHasStarted = false;
    public array $flowDiagnosis = [];
    public array $flowExistingAdoption = [];
    public ?string $flowAdoptionMode = null;
    public ?int $flowStartStageId = null;
    public array $flowReviewers = [];
    public string $flowSubsanacionReason = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterEstado(): void { $this->resetPage(); }
    public function updatingFilterCategoria(): void { $this->resetPage(); }
    public function updatingFilterModalidad(): void { $this->resetPage(); }
    public function updatingFilterOds(): void { $this->resetPage(); }
    public function updatingFilterFechaInicio(): void { $this->resetPage(); }
    public function updatingFilterFechaFin(): void { $this->resetPage(); }
    public function updatingFilterCentroFacultad(): void { $this->filterDepartamento = null; $this->resetPage(); }
    public function updatingFilterDepartamento(): void { $this->resetPage(); }

    public function openView(int $id): void
    {
        $this->viewProyectoId = $id;
        $this->viewModal = true;
    }

    public function openFirmas(int $id): void
    {
        $this->firmasProyectoId = $id;

        $get = fn(string $cargo) => FirmaProyecto::join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
            ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
            ->where('firma_proyecto.firmable_type', Proyecto::class)
            ->where('firma_proyecto.firmable_id', $id)
            ->where('tipo_cargo_firma.nombre', $cargo)
            ->where('cargo_firma.descripcion', 'Proyecto')
            ->whereNull('firma_proyecto.flujo_aprobacion_etapa_id')
            ->select('firma_proyecto.*')
            ->first();

        $this->firmas_jefe_id    = $get('Jefe Departamento')?->empleado_id;
        $this->firmas_director_id = $get('Director centro')?->empleado_id;
        $this->firmas_enlace_id  = $get('Enlace Vinculacion')?->empleado_id;
        $this->firmasModal       = true;
    }

    public function saveFirmas(): void
    {
        $this->validate([
            'firmas_jefe_id'     => 'required|integer',
            'firmas_director_id' => 'required|integer',
            'firmas_enlace_id'   => 'required|integer',
        ]);

        $actualizar = function (string $cargo, ?int $nuevoId) {
            if (!$nuevoId) return;
            $firma = FirmaProyecto::join('cargo_firma', 'firma_proyecto.cargo_firma_id', '=', 'cargo_firma.id')
                ->join('tipo_cargo_firma', 'cargo_firma.tipo_cargo_firma_id', '=', 'tipo_cargo_firma.id')
                ->where('firma_proyecto.firmable_type', Proyecto::class)
                ->where('firma_proyecto.firmable_id', $this->firmasProyectoId)
                ->where('tipo_cargo_firma.nombre', $cargo)
                ->where('cargo_firma.descripcion', 'Proyecto')
                ->whereNull('firma_proyecto.flujo_aprobacion_etapa_id')
                ->select('firma_proyecto.*')
                ->first();
            if ($firma) {
                FirmaProyecto::where('id', $firma->id)->update(['empleado_id' => $nuevoId]);
            }
        };

        $actualizar('Jefe Departamento', $this->firmas_jefe_id);
        $actualizar('Director centro', $this->firmas_director_id);
        $actualizar('Enlace Vinculacion', $this->firmas_enlace_id);

        $this->firmasModal = false;
        Notification::make()->title('Firmas actualizadas')->body('Los responsables de firma fueron actualizados correctamente.')->success()->send();
    }

    public function openFlowModal(int $id): void
    {
        $this->authorizeWorkflowAdministration();
        $this->flowProyectoId = $id;
        $proyecto = Proyecto::with('adopcionFlujoLegacy')->findOrFail($id);
        $this->flowSelectedId = $proyecto?->flujo_aprobacion_id
            ?? FlujoAprobacion::defaultForProyectos($proyecto?->tipo_accion_id, $proyecto?->codigoFormularioFlujo())?->id
            ?? FlujoAprobacion::defaultForProyectos($proyecto?->tipo_accion_id)?->id
            ?? FlujoAprobacion::defaultForProyectos()?->id;
        $this->flowHasStarted = $proyecto->firma_proyecto()
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->exists();
        $this->flowExistingAdoption = $proyecto->adopcionFlujoLegacy
            ? [
                'modo' => $proyecto->adopcionFlujoLegacy->modo,
                'estado_origen' => $proyecto->adopcionFlujoLegacy->estado_origen,
                'orden_inicio' => $proyecto->adopcionFlujoLegacy->orden_inicio,
                'adoptado_en' => $proyecto->adopcionFlujoLegacy->adoptado_en?->format('d/m/Y H:i'),
            ]
            : [];
        $this->flowDiagnosis = [];
        $this->flowAdoptionMode = null;
        $this->flowStartStageId = null;
        $this->flowReviewers = [];
        $this->flowSubsanacionReason = '';
        $this->flowIsLegacyAdoption = app(ProyectoLegacyWorkflowAdoptionService::class)
            ->requiereAdopcion($proyecto);

        if ($this->flowIsLegacyAdoption && $this->flowSelectedId) {
            $this->refreshFlowDiagnosis(true);
        }

        $this->flowModal = true;
    }

    public function updatedFlowSelectedId(): void
    {
        if (! $this->flowModal || ! $this->flowIsLegacyAdoption) {
            return;
        }

        $this->flowAdoptionMode = null;
        $this->flowStartStageId = null;
        $this->flowReviewers = [];
        $this->refreshFlowDiagnosis(true);
    }

    public function updatedFlowAdoptionMode(): void
    {
        if (! $this->flowIsLegacyAdoption) {
            return;
        }

        $this->flowStartStageId = null;
        $this->flowReviewers = [];
        $this->refreshFlowDiagnosis(true);

        if ($this->flowAdoptionMode === ProyectoLegacyWorkflowAdoptionService::MODO_SUBSANACION
            && $this->flowSubsanacionReason === ''
        ) {
            $this->flowSubsanacionReason = (string) ($this->flowDiagnosis['estado_comentario'] ?? '');
        }
    }

    public function updatedFlowStartStageId(): void
    {
        if ($this->flowIsLegacyAdoption) {
            $this->flowReviewers = [];
            $this->refreshFlowDiagnosis(true);
        }
    }

    public function refreshFlowReviewerCandidates(): void
    {
        $this->authorizeWorkflowAdministration();

        if (! $this->flowModal || ! $this->flowIsLegacyAdoption) {
            return;
        }

        $this->resetErrorBag('flowReviewers');
        $this->refreshFlowDiagnosis();
    }

    public function saveFlow(): void
    {
        $this->authorizeWorkflowAdministration();
        $this->validate([
            'flowSelectedId' => ['required', 'exists:flujos_aprobacion,id'],
        ]);

        $proyecto = Proyecto::findOrFail($this->flowProyectoId);
        $flujo = FlujoAprobacion::findOrFail($this->flowSelectedId);
        $service = app(ProyectoLegacyWorkflowAdoptionService::class);

        try {
            if ($service->requiereAdopcion($proyecto)) {
                $this->validate([
                    'flowAdoptionMode' => ['required', Rule::in(array_keys($service->modos()))],
                    'flowStartStageId' => [
                        Rule::requiredIf(in_array($this->flowAdoptionMode, [
                            ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION,
                            ProyectoLegacyWorkflowAdoptionService::MODO_SUBSANACION,
                        ], true)),
                        'nullable',
                        'integer',
                    ],
                    'flowReviewers' => ['array'],
                    'flowSubsanacionReason' => [
                        Rule::requiredIf($this->flowAdoptionMode === ProyectoLegacyWorkflowAdoptionService::MODO_SUBSANACION),
                        'nullable',
                        'string',
                        'max:2000',
                    ],
                ]);

                $service->adoptar(
                    $proyecto,
                    $flujo,
                    (string) $this->flowAdoptionMode,
                    $this->flowStartStageId,
                    $this->flowReviewers,
                    auth()->user(),
                    $this->flowSubsanacionReason
                );

                $mensaje = match ($this->flowAdoptionMode) {
                    ProyectoLegacyWorkflowAdoptionService::MODO_EN_REVISION => 'El proyecto continúa desde la etapa seleccionada y el revisor fue notificado.',
                    ProyectoLegacyWorkflowAdoptionService::MODO_SUBSANACION => 'El proyecto quedó listo para volver al mismo punto cuando sea reenviado.',
                    ProyectoLegacyWorkflowAdoptionService::MODO_COMPLETADO => 'El flujo quedó fijado sin crear aprobaciones ficticias.',
                    default => 'El flujo quedó fijado y comenzará desde la primera etapa cuando se envíe.',
                };

                Notification::make()->title('Proyecto legacy adaptado')->body($mensaje)->success()->send();
            } else {
                $service->asignarFlujoSinAdopcion($proyecto, $flujo, auth()->user());
                Notification::make()->title('Flujo actualizado')->body('El flujo del proyecto se actualizó correctamente.')->success()->send();
            }
        } catch (\RuntimeException $exception) {
            Notification::make()
                ->title('No se pudo adaptar el flujo')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->flowModal = false;
        $this->resetFlowModalState();
    }

    private function refreshFlowDiagnosis(bool $resetReviewers = false): void
    {
        if (! $this->flowProyectoId || ! $this->flowSelectedId) {
            $this->flowDiagnosis = [];

            return;
        }

        try {
            $diagnostico = app(ProyectoLegacyWorkflowAdoptionService::class)->diagnosticar(
                Proyecto::findOrFail($this->flowProyectoId),
                FlujoAprobacion::findOrFail($this->flowSelectedId)
            );
            $this->flowDiagnosis = $diagnostico;
            $this->flowAdoptionMode = $diagnostico['modo'];
            $this->flowStartStageId = $diagnostico['etapa_inicio_id'];

            $anteriores = $resetReviewers ? [] : $this->flowReviewers;
            $this->flowReviewers = [];

            foreach ($diagnostico['etapas'] as $etapa) {
                if (! $etapa['en_nuevo_recorrido']) {
                    continue;
                }

                $candidatos = collect($etapa['candidatos'])->pluck('id')->map(fn ($id): int => (int) $id);
                $anterior = isset($anteriores[$etapa['id']]) ? (int) $anteriores[$etapa['id']] : null;
                $propuesto = $etapa['propuesto_usuario_id'] ? (int) $etapa['propuesto_usuario_id'] : null;

                if ($anterior && $candidatos->contains($anterior)) {
                    $this->flowReviewers[$etapa['id']] = $anterior;
                } elseif ($propuesto && $candidatos->contains($propuesto)) {
                    $this->flowReviewers[$etapa['id']] = $propuesto;
                }
            }
        } catch (\Throwable $exception) {
            report($exception);
            $this->flowDiagnosis = [
                'bloqueos' => ['No se pudo diagnosticar el proyecto con el flujo seleccionado.'],
                'etapas' => [],
            ];
        }
    }

    private function authorizeWorkflowAdministration(): void
    {
        $user = auth()->user();

        abort_unless($user && $user->hasRole(['admin', 'Director/Enlace']) && $user->can('proyectos.historial'), 403);
    }

    private function resetFlowModalState(): void
    {
        $this->flowProyectoId = null;
        $this->flowSelectedId = null;
        $this->flowIsLegacyAdoption = false;
        $this->flowHasStarted = false;
        $this->flowDiagnosis = [];
        $this->flowExistingAdoption = [];
        $this->flowAdoptionMode = null;
        $this->flowStartStageId = null;
        $this->flowReviewers = [];
        $this->flowSubsanacionReason = '';
    }

    public function exportExcel()
    {
        return AdminCsv::download('historial-vinculacion-' . now()->format('Y-m-d') . '.csv', [
            'Codigo',
            'Numero Dictamen',
            'Nombre',
            'Tipo',
            'Estado',
            'Fecha Inicio',
        ], function () {
            foreach ($this->historialRows() as $row) {
                yield [
                    $row['codigo'],
                    $row['secondary_code'],
                    $row['nombre'],
                    $row['tipo'],
                    $row['estado'],
                    $row['fecha'] ? \Carbon\Carbon::parse($row['fecha'])->format('Y-m-d') : null,
                ];
            }
        });
    }

    private function recordsQuery()
    {
        return Proyecto::query()
            ->whereNotIn('proyecto.id', function ($query) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->whereIn('tipo_estado_id', TipoEstado::whereIn('nombre', ['Borrador'])->pluck('id')->toArray())
                    ->where('es_actual', true);
            })
            ->leftJoin('proyecto_centro_facultad', 'proyecto_centro_facultad.proyecto_id', '=', 'proyecto.id')
            ->leftJoin('proyecto_depto_ac', 'proyecto_depto_ac.proyecto_id', '=', 'proyecto.id')
            ->leftJoin('estado_proyecto', function ($join) {
                $join->on('estado_proyecto.estadoable_id', '=', 'proyecto.id')
                    ->where('estado_proyecto.estadoable_type', Proyecto::class)
                    ->where('estado_proyecto.es_actual', true);
            })
            ->leftJoin('tipo_estado', 'estado_proyecto.tipo_estado_id', '=', 'tipo_estado.id')
            ->select('proyecto.*')
            ->when($this->search, fn($q) => $q->where(fn($q2) => $q2
                ->where('proyecto.nombre_proyecto', 'like', '%' . $this->search . '%')
                ->orWhere('proyecto.codigo_proyecto', 'like', '%' . $this->search . '%')
                ->orWhere('proyecto.numero_dictamen', 'like', '%' . $this->search . '%')
            ))
            ->when($this->filterEstado, fn($q) => $q->where('tipo_estado.id', $this->filterEstado))
            ->when($this->filterModalidad, fn($q) => $q->where('proyecto.modalidad_id', $this->filterModalidad))
            ->when($this->filterCategoria, fn($q) => $q->whereHas('categoria', fn($q2) => $q2->where('categorias.id', $this->filterCategoria)))
            ->when($this->filterOds, fn($q) => $q->whereHas('ods', fn($q2) => $q2->where('ods.id', $this->filterOds)))
            ->when($this->filterFechaInicio, fn($q) => $q->whereDate('proyecto.fecha_inicio', '>=', $this->filterFechaInicio))
            ->when($this->filterFechaFin, fn($q) => $q->whereDate('proyecto.fecha_finalizacion', '<=', $this->filterFechaFin))
            ->when($this->filterCentroFacultad, fn($q) => $q->where('proyecto_centro_facultad.centro_facultad_id', $this->filterCentroFacultad))
            ->when($this->filterDepartamento, fn($q) => $q->where('proyecto_depto_ac.departamento_academico_id', $this->filterDepartamento))
            ->distinct();
    }

    private function enfRecordsQuery()
    {
        return EnfAccion::query()
            ->with(['centroFacultad', 'departamentoAcademico', 'accionCatalogos.catalogo'])
            ->when($this->search, fn($q) => $q->where(fn($q2) => $q2
                ->where('nombre_accion', 'like', '%' . $this->search . '%')
                ->orWhere('codigo_formulario', 'like', '%' . $this->search . '%')
                ->orWhere('numero_registro', 'like', '%' . $this->search . '%')
            ))
            ->when($this->filterFechaInicio, fn($q) => $q->whereDate('fecha_inicio', '>=', $this->filterFechaInicio))
            ->when($this->filterFechaFin, fn($q) => $q->whereDate('fecha_finalizacion', '<=', $this->filterFechaFin))
            ->when($this->filterCentroFacultad, fn($q) => $q->where('centro_facultad_id', $this->filterCentroFacultad))
            ->when($this->filterDepartamento, fn($q) => $q->where('departamento_academico_id', $this->filterDepartamento))
            ->when($this->filterModalidad, fn($q) => $q->whereRaw('1 = 0'))
            ->when($this->filterCategoria, fn($q) => $q->whereRaw('1 = 0'))
            ->when($this->filterOds, fn($q) => $q->whereHas('ods', fn($odsQuery) => $odsQuery->where('ods.id', $this->filterOds)))
            ->when($this->filterEstado, function ($q) {
                $estadoNombre = TipoEstado::find($this->filterEstado)?->nombre;

                if (! $estadoNombre) {
                    return $q;
                }

                $normalized = str($estadoNombre)->ascii()->upper()->replace(' ', '_')->toString();

                return $q->where(function ($stateQuery) use ($estadoNombre, $normalized) {
                    $stateQuery
                        ->where('estado_flujo', $estadoNombre)
                        ->orWhere('estado_flujo', $normalized);
                });
            });
    }

    private function historialRows(): Collection
    {
        return collect($this->proyectoRows()->all())
            ->merge($this->enfRows()->all())
            ->sortByDesc(fn (array $row) => $row['sort_date']?->timestamp ?? 0)
            ->values();
    }

    private function proyectoRows(): Collection
    {
        return $this->recordsQuery()
            ->with(['estado_proyecto.tipoestado', 'tipoAccion', 'adopcionFlujoLegacy'])
            ->get()
            ->map(function (Proyecto $proyecto): array {
                $estadoActual = $proyecto->estado_proyecto->firstWhere('es_actual', true);

                return [
                    'kind' => 'proyecto',
                    'record' => $proyecto,
                    'codigo' => $proyecto->codigo_proyecto ?: '-',
                    'secondary_code' => $proyecto->numero_dictamen ?: null,
                    'nombre' => $proyecto->nombre_proyecto,
                    'tipo' => $proyecto->tipoAccion?->nombre ?: 'Proyecto de vinculación',
                    'estado' => $estadoActual?->tipoestado?->nombre ?? '',
                    'fecha' => $proyecto->fecha_inicio,
                    'sort_date' => $proyecto->created_at,
                    'flujo_adoptado' => $proyecto->adopcionFlujoLegacy !== null,
                    'flujo_iniciado' => $proyecto->firma_proyecto()
                        ->whereNotNull('flujo_aprobacion_etapa_id')
                        ->exists(),
                ];
            });
    }

    private function enfRows(): Collection
    {
        return $this->enfRecordsQuery()
            ->get()
            ->map(function (EnfAccion $accion): array {
                $tipoEnf = $accion->accionCatalogos
                    ->first(fn ($catalogo) => $catalogo->tipo === 'tipo_accion_enf')
                    ?->catalogo?->nombre;

                return [
                    'kind' => 'enf',
                    'record' => $accion,
                    'codigo' => $accion->codigo_formulario ?: ($accion->numero_registro ?: '#'.$accion->id),
                    'secondary_code' => $accion->numero_registro ?: null,
                    'nombre' => $accion->nombre_accion,
                    'tipo' => $tipoEnf ?: 'Educación no formal',
                    'estado' => str_replace('_', ' ', $accion->estado_flujo ?: '-'),
                    'fecha' => $accion->fecha_inicio ?: $accion->fecha_solicitud,
                    'sort_date' => $accion->created_at,
                ];
            });
    }

    private function paginateRows(Collection $rows): LengthAwarePaginator
    {
        $perPage = 10;
        $page = $this->getPage();

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    public function render(): View
    {
        $records = $this->paginateRows($this->historialRows());

        $viewProyecto = $this->viewProyectoId
            ? Proyecto::with(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye'])->find($this->viewProyectoId)
            : null;

        $estadosTipo     = TipoEstado::orderBy('nombre')->pluck('nombre', 'id');
        $centros         = \App\Models\UnidadAcademica\FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');
        $departamentos   = $this->filterCentroFacultad
            ? \App\Models\UnidadAcademica\DepartamentoAcademico::where('centro_facultad_id', $this->filterCentroFacultad)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();
        $empleados       = Empleado::orderBy('nombre_completo')->pluck('nombre_completo', 'id');
        $categorias      = Categoria::orderBy('nombre')->pluck('nombre', 'id');
        $modalidades     = Modalidad::orderBy('nombre')->pluck('nombre', 'id');
        $odsList         = Od::orderBy('nombre')->pluck('nombre', 'id');
        $flujos          = FlujoAprobacion::query()
            ->where('proceso', 'PROYECTO')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        $flowModes       = app(ProyectoLegacyWorkflowAdoptionService::class)->modos();

        return view('livewire.proyectos.vinculacion.list-proyectos-vinculacion', compact(
            'records', 'viewProyecto', 'estadosTipo', 'centros', 'departamentos',
            'empleados', 'categorias', 'modalidades', 'odsList', 'flujos', 'flowModes'
        ));
    }
}

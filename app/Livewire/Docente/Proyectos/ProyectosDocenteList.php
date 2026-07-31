<?php

namespace App\Livewire\Docente\Proyectos;

use App\Http\Controllers\Docente\VerificarConstancia;
use App\Models\ENF\EnfAccion;
use App\Models\Estado\TipoEstado;
use App\Models\PpsServicioSocial;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProyectosDocenteList extends Component
{
    use WithPagination;
    use WithFileUploads;

    public Empleado $docente;

    public string $search = '';
    public string $filterCategoria = '';
    public string $filterRol = '';
    public string $filterEstado = '';
    #[Url(as: 'tipo', except: 'todas')]
    public string $filterTipoAccion = 'todas';

    private const ACTION_TODAS = 'todas';
    private const ACTION_PROYECTOS = 'proyectos';
    private const ACTION_ENF = 'educacion_no_formal';
    private const ACTION_PPS = 'pps_servicio_social';
    private const ACTION_VOLUNTARIADO = 'voluntariado';

    public bool $informeIntermedioModal = false;
    public ?int $informeIntermedioProyectoId = null;
    public $informeIntermedioFile = null;

    public bool $deleteModal = false;
    public ?int $deleteProyectoId = null;
    public bool $deleteEnfModal = false;
    public ?int $deleteEnfAccionId = null;

    public function mount($docente = null): void
    {
        $this->docente = $docente ?? auth()->user()->empleado;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterTipoAccion(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategoria(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRol(): void
    {
        $this->resetPage();
    }

    public function updatingFilterEstado(): void
    {
        $this->resetPage();
    }

    public function openSubirIntermedio(int $proyectoId): void
    {
        $this->informeIntermedioProyectoId = $proyectoId;
        $this->informeIntermedioFile = null;
        $this->informeIntermedioModal = true;
    }

    public function subirInformeIntermedio(): void
    {
        $this->validate(['informeIntermedioFile' => 'required|file|mimes:pdf|max:10240']);

        $proyecto = Proyecto::findOrFail($this->informeIntermedioProyectoId);
        $path = $this->informeIntermedioFile->store('documentos', 'public');

        try {
            $proyecto->registrarDocumentoDesdeFlujo('Informe Intermedio', $path, auth()->user()->empleado);
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo enviar el informe')->body($e->getMessage())->danger()->send();
            return;
        }

        $this->informeIntermedioModal = false;
        $this->informeIntermedioFile = null;
        Notification::make()->title('Informe subido')->body('El informe intermedio fue enviado correctamente.')->success()->send();
    }

    public function constanciaInscripcion(int $proyectoId): mixed
    {
        $proyecto = Proyecto::findOrFail($proyectoId);
        $empleadoProyecto = $proyecto->docentes_proyecto()->where('empleado_id', $this->docente->id)->first();
        return VerificarConstancia::CrearPdfInscripcion($empleadoProyecto);
    }

    public function constanciaFinalizacion(int $proyectoId): mixed
    {
        $proyecto = Proyecto::findOrFail($proyectoId);
        $empleadoProyecto = $proyecto->docentes_proyecto()->where('empleado_id', $this->docente->id)->first();
        return VerificarConstancia::CrearPdfFinalizacion($empleadoProyecto);
    }

    public function openDelete(int $proyectoId): void
    {
        $this->deleteProyectoId = $proyectoId;
        $this->deleteModal = true;
    }

    public function deleteProyecto(): void
    {
        $proyecto = Proyecto::findOrFail($this->deleteProyectoId);
        $proyecto->delete();
        $this->deleteModal = false;
        $this->deleteProyectoId = null;
        Notification::make()->title('Proyecto eliminado')->body('El proyecto fue eliminado correctamente.')->success()->send();
    }

    public function openDeleteEnf(int $accionId): void
    {
        $accion = EnfAccion::findOrFail($accionId);

        abort_unless((int) $accion->creado_por_usuario_id === (int) auth()->id(), 403);

        $this->deleteEnfAccionId = $accionId;
        $this->deleteEnfModal = true;
    }

    public function deleteEnfAccion(): void
    {
        $accion = EnfAccion::findOrFail($this->deleteEnfAccionId);

        abort_unless((int) $accion->creado_por_usuario_id === (int) auth()->id(), 403);

        $accion->delete();
        $this->deleteEnfModal = false;
        $this->deleteEnfAccionId = null;
        Notification::make()->title('ENF eliminado')->body('La accion de Educacion No Formal fue eliminada correctamente.')->success()->send();
    }

    public function render(): View
    {
        $records = $this->paginateRows($this->historialRows());

        $categorias = \App\Models\Proyecto\Categoria::orderBy('nombre')->pluck('nombre', 'id');
        $estadosTipo = TipoEstado::orderBy('nombre')->pluck('nombre', 'id');

        return view('livewire.docente.proyectos.proyectos-docente-list', compact('records', 'categorias', 'estadosTipo'));
    }

    private function proyectosQuery(): Builder
    {
        return Proyecto::query()
            ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->join('estado_proyecto', function ($join) {
                $join->on('estado_proyecto.estadoable_id', '=', 'proyecto.id')
                    ->where('estado_proyecto.estadoable_type', '=', 'App\Models\Proyecto\Proyecto')
                    ->where('estado_proyecto.es_actual', '=', true);
            })
            ->join('tipo_estado', 'estado_proyecto.tipo_estado_id', '=', 'tipo_estado.id')
            ->select('proyecto.*')
            ->where('empleado_proyecto.empleado_id', $this->docente->id)
            ->where('tipo_estado.nombre', '!=', 'PendienteInformacion')
            ->whereNotExists(function ($query) {
                $query->select('*')
                    ->from('empleado_codigos_investigacion')
                    ->whereRaw('empleado_codigos_investigacion.codigo_proyecto = proyecto.codigo_proyecto')
                    ->where('tipo_estado.nombre', '=', 'Finalizado');
            })
            ->when($this->search, fn($q) => $q->where(fn($q2) => $q2
                ->where('proyecto.nombre_proyecto', 'like', '%' . $this->search . '%')
                ->orWhere('proyecto.codigo_proyecto', 'like', '%' . $this->search . '%')
                ->orWhere('proyecto.numero_dictamen', 'like', '%' . $this->search . '%')
            ))
            ->when($this->filterCategoria !== '', fn($q) => $q->whereHas(
                'categoria',
                fn($q2) => $q2->where('categorias.id', $this->filterCategoria)
            ))
            ->when($this->filterRol, fn($q) => $q->where('empleado_proyecto.rol', $this->filterRol))
            ->when($this->filterEstado, fn($q) => $q->where('tipo_estado.id', $this->filterEstado))
            ->when($this->filterTipoAccion === self::ACTION_VOLUNTARIADO, fn($q) => $q->whereHas(
                'tipoAccion',
                fn($t) => $t->where('codigo', 'VOLUNTARIADO')
            ))
            ->when($this->filterTipoAccion === self::ACTION_PROYECTOS, fn($q) => $q->whereDoesntHave(
                'tipoAccion',
                fn($t) => $t->where('codigo', 'VOLUNTARIADO')
            ))
            ->with(['estadoActual.tipoestado', 'tipoAccion', 'coordinador_proyecto.empleado'])
            ->distinct();
    }

    private function historialRows(): Collection
    {
        $rows = collect();

        if ($this->shouldIncludeProyectos()) {
            $rows = $rows->merge($this->proyectoRows());
        }

        if ($this->shouldIncludeAction(self::ACTION_ENF)) {
            $rows = $rows->merge($this->enfRows());
        }

        if ($this->shouldIncludeAction(self::ACTION_PPS)) {
            $rows = $rows->merge($this->ppsRows());
        }

        return $rows
            ->sortByDesc(fn (array $row) => $row['sort_date']?->timestamp ?? 0)
            ->values();
    }

    private function shouldIncludeAction(string $action): bool
    {
        return $this->filterTipoAccion === self::ACTION_TODAS || $this->filterTipoAccion === $action;
    }

    private function shouldIncludeProyectos(): bool
    {
        return in_array($this->filterTipoAccion, [
            self::ACTION_TODAS,
            self::ACTION_PROYECTOS,
            self::ACTION_VOLUNTARIADO,
        ], true);
    }

    private function proyectoRows(): Collection
    {
        return $this->proyectosQuery()
            ->get()
            ->map(function (Proyecto $proyecto): array {
                $estado = $proyecto->estado?->tipoestado?->nombre ?? '';
                $rolDocente = $proyecto->docentes_proyecto()->where('empleado_id', $this->docente->id)->first()?->rol ?? '-';

                return [
                    'kind' => self::ACTION_PROYECTOS,
                    'id' => 'proyecto-'.$proyecto->id,
                    'record' => $proyecto,
                    'codigo' => $proyecto->codigo_proyecto ?: '-',
                    'secondary_code' => $proyecto->numero_dictamen ?: null,
                    'nombre' => $proyecto->nombre_proyecto,
                    'descripcion' => $proyecto->tipoAccion?->nombre ?: 'Proyecto de vinculación',
                    'tipo_accion' => $this->projectActionLabel($proyecto),
                    'rol' => $rolDocente,
                    'estado' => $estado ?: '-',
                    'fecha' => $proyecto->fecha_inicio ?: $proyecto->created_at,
                    'sort_date' => $proyecto->created_at,
                ];
            });
    }

    private function enfRows(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        $ownIds = EnfAccion::query()
            ->where('creado_por_usuario_id', $user->id)
            ->pluck('id');

        $pendingIds = $this->enfPendingReviewQuery()
            ->pluck('id');

        $ids = $ownIds->merge($pendingIds)->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return EnfAccion::query()
            ->with(['tipoAccion', 'revisiones', 'accionCatalogos.catalogo'])
            ->whereIn('id', $ids)
            ->when($this->search, fn (Builder $query) => $query->where(function (Builder $subQuery): void {
                $subQuery
                    ->where('codigo_formulario', 'like', '%'.$this->search.'%')
                    ->orWhere('numero_registro', 'like', '%'.$this->search.'%')
                    ->orWhere('nombre_accion', 'like', '%'.$this->search.'%');
            }))
            ->get()
            ->map(function (EnfAccion $accion) use ($user): array {
                $isOwn = (int) $accion->creado_por_usuario_id === (int) $user->id;
                $isPending = $this->enfAccionPendienteParaUsuario($accion);
                $tipoEnf = $accion->accionCatalogos
                    ->first(fn ($catalogo) => $catalogo->tipo === 'tipo_accion_enf')
                    ?->catalogo?->nombre;

                return [
                    'kind' => self::ACTION_ENF,
                    'id' => 'enf-'.$accion->id,
                    'record' => $accion,
                    'codigo' => $accion->codigo_formulario ?: ($accion->numero_registro ?: '#'.$accion->id),
                    'secondary_code' => null,
                    'nombre' => $accion->nombre_accion,
                    'descripcion' => $tipoEnf ?: ($accion->tipoAccion?->nombre ?: 'Educacion no formal'),
                    'tipo_accion' => 'Educacion no formal',
                    'rol' => $isPending ? 'Pendiente por revisar' : ($isOwn ? 'Creador' : '-'),
                    'estado' => str_replace('_', ' ', $accion->estado_flujo ?: '-'),
                    'fecha' => $accion->fecha_solicitud ?: $accion->created_at,
                    'sort_date' => $accion->created_at,
                ];
            });
    }

    private function ppsRows(): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        $ownIds = PpsServicioSocial::query()
            ->where('created_by', $user->id)
            ->pluck('id');

        $pendingIds = $this->ppsPendingReviewQuery()
            ->pluck('id');

        $ids = $ownIds->merge($pendingIds)->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return PpsServicioSocial::query()
            ->with(['etapaActual.rolRevisor', 'etapaActual.usuarioResponsable'])
            ->whereIn('id', $ids)
            ->when($this->search, fn (Builder $query) => $query->where(function (Builder $subQuery): void {
                $subQuery
                    ->where('codigo_registro', 'like', '%'.$this->search.'%')
                    ->orWhere('nombre_estudiante', 'like', '%'.$this->search.'%')
                    ->orWhere('numero_cuenta', 'like', '%'.$this->search.'%')
                    ->orWhere('nombre_institucion', 'like', '%'.$this->search.'%');
            }))
            ->get()
            ->map(function (PpsServicioSocial $registro) use ($user): array {
                $isOwn = (int) $registro->created_by === (int) $user->id;
                $isPending = $this->ppsRegistroPendienteParaUsuario($registro);

                return [
                    'kind' => self::ACTION_PPS,
                    'id' => 'pps-'.$registro->id,
                    'record' => $registro,
                    'codigo' => $registro->codigo_registro ?: '#'.$registro->id,
                    'secondary_code' => $registro->numero_cuenta ?: null,
                    'nombre' => $registro->nombre_estudiante ?: $registro->nombre_institucion,
                    'descripcion' => $registro->nombre_institucion ?: $registro->tipo_pps_ss,
                    'tipo_accion' => 'PPS / Servicio Social',
                    'rol' => $isPending ? 'Pendiente por revisar' : ($isOwn ? 'Creador' : '-'),
                    'estado' => ucfirst(str_replace('_', ' ', $registro->estado ?: 'sin estado')),
                    'fecha' => $registro->fecha_envio ?: $registro->created_at,
                    'sort_date' => $registro->created_at,
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

    private function projectActionLabel(Proyecto $proyecto): string
    {
        $codigo = $proyecto->tipoAccion?->codigo;

        return match ($codigo) {
            'EDUCACION_NO_FORMAL' => 'Educacion no formal',
            'PPS_VOLUNTARIADO_GESTION_RIESGO' => 'PPS / Servicio Social',
            'VOLUNTARIADO' => 'Voluntariado Académico',
            default => 'Desarrollo Local y Regional',
        };
    }

    private function ppsPendingReviewQuery(): Builder
    {
        $user = auth()->user();

        if (! $user || empty($user->active_role_id)) {
            return PpsServicioSocial::query()->whereRaw('1 = 0');
        }

        $activeRole = $user->activeRole;

        if (! $activeRole) {
            return PpsServicioSocial::query()->whereRaw('1 = 0');
        }

        $activeRoleId = (int) $activeRole->id;
        $isActiveAdmin = $activeRole->name === 'admin';

        return PpsServicioSocial::query()
            ->whereNotNull('etapa_actual_id')
            ->whereNotNull('flujo_aprobacion_id')
            ->whereDoesntHave('estadoActual.tipoestado', fn ($sq) => $sq->whereIn('nombre', ['Aprobado', 'Rechazado']))
            ->whereHas('flujoAprobacion', fn (Builder $query) => $query
                ->where('proceso', PpsServicioSocial::PROCESO_FLUJO))
            ->whereHas('etapaActual', function (Builder $query) use ($user, $activeRoleId, $isActiveAdmin): void {
                $query
                    ->whereColumn('flujos_aprobacion_etapas.flujo_aprobacion_id', 'pps_servicio_social.flujo_aprobacion_id')
                    ->where('activo', true)
                    ->whereHas('flujo', fn (Builder $flujoQuery) => $flujoQuery
                        ->where('proceso', PpsServicioSocial::PROCESO_FLUJO));

                if ($isActiveAdmin) {
                    return;
                }

                $query->where(function (Builder $responsableQuery) use ($user, $activeRoleId): void {
                    $responsableQuery
                        ->where(function (Builder $asignacionQuery) use ($user, $activeRoleId): void {
                            $asignacionQuery
                                ->where('requiere_asignacion', true)
                                ->where('usuario_responsable_id', $user->id)
                                ->where(function (Builder $roleQuery) use ($activeRoleId): void {
                                    $roleQuery
                                        ->whereNull('rol_revisor_id')
                                        ->orWhere('rol_revisor_id', $activeRoleId);
                                });
                        })
                        ->orWhere(function (Builder $rolQuery) use ($activeRoleId): void {
                            $rolQuery
                                ->where('requiere_asignacion', false)
                                ->where('rol_revisor_id', $activeRoleId);
                        });
                });
            });
    }

    private function ppsRegistroPendienteParaUsuario(PpsServicioSocial $registro): bool
    {
        return $this->ppsPendingReviewQuery()
            ->whereKey($registro->id)
            ->exists();
    }

    private function enfPendingReviewQuery(): Builder
    {
        $user = auth()->user();
        $activeRole = $user?->activeRole;

        if (! $user || ! $activeRole) {
            return EnfAccion::query()->whereRaw('1 = 0');
        }

        $activeRoleName = $activeRole->name;
        $pendingStates = ['PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO'];

        return EnfAccion::query()
            ->where('estado_flujo', 'EN_REVISION')
            ->whereHas('revisiones', function (Builder $query) use ($user, $activeRoleName, $pendingStates): void {
                $query
                    ->whereIn('estado', $pendingStates)
                    ->whereColumn('enf_revisiones.revision_ciclo', 'enf_acciones.revision_ciclo')
                    ->whereNotExists(function ($previousQuery) use ($pendingStates): void {
                        $previousQuery
                            ->selectRaw('1')
                            ->from('enf_revisiones as enf_revisiones_anteriores')
                            ->whereColumn('enf_revisiones_anteriores.enf_accion_id', 'enf_revisiones.enf_accion_id')
                            ->whereColumn('enf_revisiones_anteriores.revision_ciclo', 'enf_revisiones.revision_ciclo')
                            ->whereColumn('enf_revisiones_anteriores.orden', '<', 'enf_revisiones.orden')
                            ->whereIn('enf_revisiones_anteriores.estado', $pendingStates);
                    })
                    ->where(function (Builder $responsableQuery) use ($user, $activeRoleName): void {
                        $responsableQuery
                            ->where(function (Builder $assignedQuery) use ($user, $activeRoleName): void {
                                $assignedQuery
                                    ->where('asignado_usuario_id', $user->id)
                                    ->where(function (Builder $roleQuery) use ($activeRoleName): void {
                                        $roleQuery
                                            ->whereNull('rol_requerido')
                                            ->orWhere('rol_requerido', $activeRoleName);
                                    });
                            })
                            ->orWhere(function (Builder $roleQuery) use ($activeRoleName): void {
                                $roleQuery
                                    ->whereNull('asignado_usuario_id')
                                    ->where('rol_requerido', $activeRoleName);
                            })
                            ->orWhere(function (Builder $assignmentQuery) use ($user, $activeRoleName): void {
                                $assignmentQuery
                                    ->where('responsable_usuario_id', $user->id)
                                    ->where(function (Builder $roleQuery) use ($activeRoleName): void {
                                        $roleQuery
                                            ->whereNull('rol_requerido')
                                            ->orWhere('rol_requerido', $activeRoleName);
                                    });
                            });
                    });
            });
    }

    private function enfAccionPendienteParaUsuario(EnfAccion $accion): bool
    {
        return $this->enfPendingReviewQuery()
            ->whereKey($accion->id)
            ->exists();
    }
}

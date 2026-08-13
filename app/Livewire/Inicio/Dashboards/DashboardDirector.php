<?php

namespace App\Livewire\Inicio\Dashboards;

use App\Concerns\ResolvesFirmasPendientes;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\PpsServicioSocial;
use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfRevision;
use App\Models\Proyecto\Proyecto;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DashboardDirector extends Component
{
    use WithPagination;
    use ResolvesFirmasPendientes;

    public int $perPage           = 5;
    public int $perPagePendientes = 5;
    public int $perPagePanel      = 3;

    public $selectedYear            = null;
    public array $projectsDataUser  = [];
    public int $totalProjectsYearUser = 0;
    public int $chartStartYear      = 2025;
    public bool $chartFullRange     = false;

    public function mount(): void
    {
        $this->selectedYear = now()->year;
        $this->updateChartDataUser();
    }

    public function loadMore(): void
    {
        $this->perPage += 5;
    }

    public function loadMorePendientes(): void
    {
        $this->perPagePendientes += 5;
    }

    public function loadMorePanel(): void
    {
        $this->perPagePanel += 3;
    }

    public function toggleChartRange(): void
    {
        $this->chartFullRange = !$this->chartFullRange;
        $this->updateChartDataUser();
    }

    public function updatedSelectedYear(): void
    {
        $this->updateChartDataUser();
    }

    public function updateChartDataUser(): void
    {
        $user = auth()->user();
        $empleadoId = $user?->empleado?->id;

        if (!$user || !$empleadoId) {
            $this->projectsDataUser      = [];
            $this->totalProjectsYearUser = 0;
            return;
        }

        $userProjects = Proyecto::join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->where('empleado_proyecto.empleado_id', $empleadoId)
            ->select('proyecto.*')
            ->get();

        $enfProjects = $this->queryMisEnf()
            ->get()
            ->map(fn (EnfAccion $accion): object => (object) [
                'nombre_proyecto' => $accion->nombre_accion ?: 'Educacion no formal',
                'created_at' => $accion->created_at,
            ]);

        $userProjects = $userProjects->concat($enfProjects);

        $end        = now()->year;
        $yearsRange = $this->chartFullRange
            ? range($this->chartStartYear, $end)
            : range(max($this->chartStartYear, $end - 3), $end);

        $userProjects = $userProjects->filter(
            fn ($p) => in_array((int) Carbon::parse($p->created_at)->format('Y'), $yearsRange)
        );

        $grouped = $userProjects->groupBy(fn ($p) => Carbon::parse($p->created_at)->format('Y'));

        $chartDataUser = [];
        foreach ($yearsRange as $year) {
            $ofYear                        = $grouped->get($year, collect());
            $chartDataUser[(string) $year] = [
                'count'    => $ofYear->count(),
                'projects' => $ofYear->pluck('nombre_proyecto')->toArray(),
            ];
        }

        $this->projectsDataUser      = $chartDataUser;
        $this->totalProjectsYearUser = array_sum(array_column($chartDataUser, 'count'));
        $this->dispatch('updateChart-Director', dataUser: $this->projectsDataUser);
    }

    // ── Rol activo → etiqueta mostrada junto al contador de pendientes ────

    private function estadoPendienteParaRol(): ?string
    {
        return auth()->user()->activeRole?->name;
    }

    // ── Proyectos propios ─────────────────────────────────────────────────

    private function queryMisProyectos()
    {
        $empleadoId = auth()->user()->empleado?->id;

        if (!$empleadoId) {
            return Proyecto::query()->whereRaw('1 = 0');
        }

        return Proyecto::query()
            ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->where('empleado_proyecto.empleado_id', $empleadoId)
            ->select('proyecto.*')
            ->distinct();
    }

    private function queryMisEnf(): Builder
    {
        $user = auth()->user();
        $empleadoId = $user?->empleado?->id;

        if (!$user) {
            return EnfAccion::query()->whereRaw('1 = 0');
        }

        return EnfAccion::query()
            ->whereIn('codigo_formulario', ['FORM-DVUS-016', 'FORM-DVUS-018'])
            ->where(function (Builder $query) use ($user, $empleadoId): void {
                $query->where('creado_por_usuario_id', $user->id)
                    ->orWhereHas('equipo', function (Builder $equipoQuery) use ($user, $empleadoId): void {
                        $equipoQuery->where('user_id', $user->id);

                        if ($empleadoId) {
                            $equipoQuery->orWhere('empleado_id', $empleadoId);
                        }
                    });

                if ($empleadoId) {
                    $query->orWhere('responsable_revision_id', $empleadoId);
                }
            });
    }

    private function misEnfPorEstado(array $estados): int
    {
        return $this->queryMisEnf()
            ->whereIn(DB::raw('UPPER(estado_flujo)'), array_map('strtoupper', $estados))
            ->count();
    }

    private function misProyectosUnificados()
    {
        $proyectos = $this->queryMisProyectos()
            ->with([
                'estadoActual.tipoestado',
                'firmasDeEtapa' => fn ($q) => $q->orderByDesc('revision_ciclo')->orderBy('orden_revision'),
            ])
            ->get()
            ->map(function (Proyecto $proyecto): object {
                return (object) [
                    'tipo' => 'proyecto',
                    'codigo' => $proyecto->codigo_proyecto,
                    'nombre' => $proyecto->nombre_proyecto,
                    'estado' => $proyecto->estadoActual?->tipoestado?->nombre,
                    'fecha_inicio' => $proyecto->fecha_inicio,
                    'sort_date' => $proyecto->created_at,
                    'proyecto' => $proyecto,
                ];
            });

        $enf = $this->queryMisEnf()
            ->with(['revisiones' => fn ($q) => $q->orderByDesc('revision_ciclo')->orderBy('orden')])
            ->get()
            ->map(function (EnfAccion $accion): object {
                return (object) [
                    'tipo' => 'enf',
                    'codigo' => $accion->codigo_formulario ?: $accion->numero_registro,
                    'nombre' => $accion->nombre_accion ?: 'Educacion no formal',
                    'estado' => $this->enfEstadoLabel($accion->estado_flujo),
                    'fecha_inicio' => $accion->fecha_solicitud ?: $accion->fecha_inicio,
                    'sort_date' => $accion->created_at,
                    'accion' => $accion,
                ];
            });

        return $proyectos->concat($enf)->sortByDesc('sort_date')->take($this->perPage)->values();
    }

    private function enfEstadoLabel(?string $estado): string
    {
        return match (strtoupper((string) $estado)) {
            'BORRADOR' => 'Borrador',
            'EN_REVISION' => 'En revision',
            'APROBADO' => 'Aprobado',
            'FINALIZADO' => 'Finalizado',
            'SUBSANACION', 'SUBSANACIÓN' => 'Subsanar',
            default => $estado ?: 'Educacion no formal',
        };
    }

    private function misProyectosPorEstado(string $estadoNombre): int
    {
        $empleadoId = auth()->user()->empleado?->id;
        if (!$empleadoId) return 0;

        $tipoEstado = TipoEstado::where('nombre', $estadoNombre)->first();
        if (!$tipoEstado) return 0;

        return Proyecto::query()
            ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->where('empleado_proyecto.empleado_id', $empleadoId)
            ->whereIn('proyecto.id', function ($sub) use ($tipoEstado) {
                $sub->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->where('tipo_estado_id', $tipoEstado->id)
                    ->where('es_actual', true);
            })
            ->distinct()
            ->count('proyecto.id');
    }

    private function misProyectosEnRevisionCount(array $estadoNames): int
    {
        $empleadoId     = auth()->user()->empleado?->id;
        $tipoEstadosIds = TipoEstado::whereIn('nombre', $estadoNames)->pluck('id');

        if (!$empleadoId || $tipoEstadosIds->isEmpty()) return 0;

        return Proyecto::query()
            ->whereIn('id', function ($sub) use ($tipoEstadosIds) {
                $sub->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->whereIn('tipo_estado_id', $tipoEstadosIds)
                    ->where('es_actual', true);
            })
            ->whereIn('id', function ($sub) use ($empleadoId) {
                $sub->select('proyecto_id')
                    ->from('empleado_proyecto')
                    ->where('empleado_id', $empleadoId);
            })
            ->distinct()
            ->count('id');
    }

    // ── Panel de estados (propios, paginados) ─────────────────────────────

    private function misProyectosPorEstadoPaginado(string $estadoNombre)
    {
        $empleadoId = auth()->user()->empleado?->id;
        $tipoEstado = TipoEstado::where('nombre', $estadoNombre)->first();

        if (!$empleadoId || !$tipoEstado) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPagePanel, 1);
        }

        return Proyecto::query()
            ->whereIn('id', function ($sub) use ($tipoEstado) {
                $sub->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->where('tipo_estado_id', $tipoEstado->id)
                    ->where('es_actual', true);
            })
            ->whereIn('id', function ($sub) use ($empleadoId) {
                $sub->select('proyecto_id')
                    ->from('empleado_proyecto')
                    ->where('empleado_id', $empleadoId);
            })
            ->orderBy('id', 'asc')
            ->paginate($this->perPagePanel);
    }

    private function misProyectosEnRevisionesPaginado(array $estadoNames)
    {
        $empleadoId     = auth()->user()->empleado?->id;
        $tipoEstadosIds = TipoEstado::whereIn('nombre', $estadoNames)->pluck('id');

        if (!$empleadoId || $tipoEstadosIds->isEmpty()) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPagePanel, 1);
        }

        return Proyecto::with('tipo_estado')
            ->whereIn('id', function ($sub) use ($tipoEstadosIds) {
                $sub->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->whereIn('tipo_estado_id', $tipoEstadosIds)
                    ->where('es_actual', true);
            })
            ->whereIn('id', function ($sub) use ($empleadoId) {
                $sub->select('proyecto_id')
                    ->from('empleado_proyecto')
                    ->where('empleado_id', $empleadoId);
            })
            ->orderBy('id', 'asc')
            ->paginate($this->perPagePanel);
    }

    // ── Pendientes de revisión según rol activo ───────────────────────────
    // Usa el mismo criterio que la bandeja de tareas del docente
    // (ResolvesFirmasPendientes::firmasDisponiblesQuery) para que ambos
    // lugares cuenten exactamente lo mismo.

    private function proyectosPendientesQuery()
    {
        $proyectoIds = $this->proyectoIdsConFirmaPendienteParaRolActivo();

        if ($proyectoIds->isEmpty()) {
            return Proyecto::query()->whereRaw('1 = 0');
        }

        return Proyecto::query()->whereIn('id', $proyectoIds);
    }

    // ── Pendientes de revisión PPS/SS (mismo criterio que la bandeja) ─────

    private function pendientesPpsQuery()
    {
        return PpsServicioSocial::pendientesParaUsuario(auth()->user());
    }

    // ── Cantidad de proyectos (propio empleado) ───────────────────────────

    private function getProjectsCountByEmployee()
    {
        $empleadoId = auth()->user()->empleado?->id;

        if (!$empleadoId) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 4, 1);
        }

        return Empleado::where('id', $empleadoId)->withCount('proyectos')->paginate(4);
    }

    // ── Actividades recientes ─────────────────────────────────────────────

    public function getLatestActivitiesUser(int $limit = 6): \Illuminate\Support\Collection
    {
        $empleadoId = auth()->user()->empleado?->id;

        $propiosIds = $empleadoId
            ? DB::table('empleado_proyecto')
                ->where('empleado_id', $empleadoId)
                ->pluck('proyecto_id')
                ->toArray()
            : [];

        $pendientesIds = $this->proyectoIdsConFirmaPendienteParaRolActivo()->toArray();

        $allIds = array_unique(array_merge($propiosIds, $pendientesIds));

        if (empty($allIds)) {
            return collect();
        }

        return EstadoProyecto::whereIn('estadoable_id', $allIds)
            ->where('estadoable_type', Proyecto::class)
            ->with(['tipoestado', 'estadoable'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($estado) {
                $estado->fecha_cambio    = $estado->created_at->format('d/m/Y H:i');
                $estado->nombre_elemento = $estado->estadoable->nombre_proyecto ?? 'Proyecto';
                $estado->tipo_elemento   = 'Proyecto';
                return $estado;
            });
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render()
    {
        $estadosRevision = [
            'Esperando documento', 'Subsanar documento', 'Enlace Vinculacion',
            'Coordinador Proyecto', 'Jefe Departamento', 'Director Centro',
            'En revision final', 'Aprobado', 'Subsanacion', 'Rechazado',
            'Inscrito', 'Cancelado', 'En revision',
        ];

        $estadoPendienteNombre = $this->estadoPendienteParaRol();

        // Conteos mis proyectos
        $totalMisProyectos = $this->queryMisProyectos()->count() + $this->queryMisEnf()->count();
        $finalizadosCount  = $this->misProyectosPorEstado('Finalizado') + $this->misEnfPorEstado(['FINALIZADO']);
        $subsanarCount     = $this->misProyectosPorEstado('Subsanacion') + $this->misEnfPorEstado(['SUBSANACION', 'SUBSANACIÓN']);
        $enCursoCount      = $this->misProyectosPorEstado('En curso') + $this->misEnfPorEstado(['APROBADO']);
        $borradorCount     = $this->misProyectosPorEstado('Borrador') + $this->misEnfPorEstado(['BORRADOR']);
        $enRevisionCount   = $this->misProyectosEnRevisionCount($estadosRevision) + $this->misEnfPorEstado(['EN_REVISION']);

        // Tabla mis proyectos
        $misProyectosTable = $this->misProyectosUnificados();

        // Panel de estados (proyectos propios)
        $panelBorrador    = $this->misProyectosPorEstadoPaginado('Borrador');
        $panelEnRevision  = $this->misProyectosEnRevisionesPaginado($estadosRevision);
        $panelEnCurso     = $this->misProyectosPorEstadoPaginado('En curso');
        $panelFinalizados = $this->misProyectosPorEstadoPaginado('Finalizado');

        // Pendientes según rol activo
        $totalPendientesProyectos = $this->proyectosPendientesQuery()->count();
        $pendientesTable = $this->proyectosPendientesQuery()
            ->with(['estadoActual.tipoestado'])
            ->orderBy('proyecto.created_at', 'desc')
            ->paginate($this->perPagePendientes);

        // Pendientes PPS/SS según rol activo (mismo criterio que la bandeja de tareas)
        $totalPendientesPps = $this->pendientesPpsQuery()->count();
        $pendientesPpsTable = $this->pendientesPpsQuery()
            ->with('etapaActual')
            ->orderByDesc('created_at')
            ->paginate($this->perPagePendientes, ['*'], 'pageuPps');

        $totalPendientes = $totalPendientesProyectos + $totalPendientesPps;

        $activitiesUser     = $this->getLatestActivitiesUser();
        $empleadosWithCount = $this->getProjectsCountByEmployee();

        return view('livewire.inicio.dashboards.dashboard-director', [
            'estadoPendienteNombre'  => $estadoPendienteNombre,
            // Mis proyectos — conteos
            'totalMisProyectos'      => $totalMisProyectos,
            'finalizadosCount'       => $finalizadosCount,
            'subsanarCount'          => $subsanarCount,
            'enCursoCount'           => $enCursoCount,
            'borradorCount'          => $borradorCount,
            'enRevisionCount'        => $enRevisionCount,
            // Mis proyectos — tabla
            'misProyectosTable'      => $misProyectosTable,
            // Panel de estados
            'panelBorrador'          => $panelBorrador,
            'panelEnRevision'        => $panelEnRevision,
            'panelEnCurso'           => $panelEnCurso,
            'panelFinalizados'       => $panelFinalizados,
            // Pendientes
            'totalPendientes'        => $totalPendientes,
            'pendientesTable'        => $pendientesTable,
            'pendientesPpsTable'     => $pendientesPpsTable,
            // Actividades y cantidad
            'activitiesUser'         => $activitiesUser,
            'empleadosWithCount'     => $empleadosWithCount,
            // Gráfico
            'chartDataUser'          => $this->projectsDataUser,
            'totalProjectsYearUser'  => $this->totalProjectsYearUser,
        ]);
    }
}

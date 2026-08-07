<?php

namespace App\Livewire\Inicio\Dashboards;

use App\Models\Estado\TipoEstado;
use App\Models\PpsServicioSocial;
use App\Models\Proyecto\Proyecto;
use App\Models\Personal\Empleado;
use App\Models\Estado\EstadoProyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfRevision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Livewire\Component;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

class DasboardDocente extends Component
{
    public $perPage = 3;
    public $selectedYear = null;
    public $projectsData = [];
    public $projectsDataUser = [];
    public $totalProjectsYear = 0;     // Total para el año seleccionado (admin)
    public $totalProjectsYearUser = 0;

    // Método para cargar más proyectos en la tabla
    public function loadMore()
    {
        $this->perPage += 3;
    }

    public function mount()
    {
        $this->selectedYear = now()->year;
        $this->updateChartData();
        $this->updateChartDataUser();
        // si el usuario autenticado tiene el permiso perfil.editar
        // redirigirlo a la pagina de configuracion de su perfil
        if (auth()->user()->can('perfil.editar')) {
            return redirect()->route('completar_perfil');
        };
    }

    // año fijo de inicio
    public $chartStartYear = 2025;
    // Nuevo: determina si se muestra el rango completo o solo los últimos 4 años (por defecto se muestran solo los últimos 4)
    public $chartFullRange = false;

    // Método para cambiar la opción de rango en la vista (por ejemplo: botón para ver años anteriores)
    public function toggleChartRange()
    {
        $this->chartFullRange = !$this->chartFullRange;
        $this->updateChartDataUser();
    }

    public function updatedSelectedYear()
    {
        $this->updateChartData();
        $this->updateChartDataUser();
    }

    public function updateChartData()
    {
        // Consulta los proyectos agrupados por trimestre para el año seleccionado
        $this->projectsData = Proyecto::selectRaw('QUARTER(created_at) as quarter, COUNT(*) as count')
            ->whereYear('created_at', $this->selectedYear)
            ->groupBy('quarter')
            ->orderBy('quarter')
            ->pluck('count', 'quarter')
            ->toArray();

        // Rellena los trimestres faltantes con 0
        $this->projectsData = array_replace([
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
        ], $this->projectsData);
        // Calcula el total de proyectos en el año seleccionado
        $this->totalProjectsYear = array_sum($this->projectsData);
        // Envía el array para que en el gráfico se utilicen las claves como categorías
        $this->dispatch('updateChart', data: array_values($this->projectsData));
    }

    public function updateChartDataUser()
    {
        $userId = auth()->user()->empleado->id;

        // Obtén los proyectos del usuario con sus datos completos
        $userProjects = Proyecto::query()->get();

        $enfProjects = EnfAccion::query()
            ->where(fn (Builder $query): Builder => $this->enfFormsQuery($query))
            ->get()
            ->map(function (EnfAccion $accion): object {
                return (object) [
                    'nombre_proyecto' => $accion->nombre_accion ?: 'Educacion no formal',
                    'created_at' => $accion->created_at,
                ];
            });

        $userProjects = $userProjects->concat($enfProjects);

        // Define el rango de años a mostrar según la opción seleccionada
        $end = now()->year;
        if ($this->chartFullRange) {
            // Rango completo: desde el año de inicio hasta el actual
            $yearsRange = range($this->chartStartYear, $end);
        } else {
            // Solo los últimos 4 años, respetando el mínimo de chartStartYear
            $startPeriod = max($this->chartStartYear, $end - 3);
            $yearsRange = range($startPeriod, $end);
        }

        // Filtra los proyectos que se encuentren en el rango determinado
        $userProjects = $userProjects->filter(function ($project) use ($yearsRange) {
            $year = Carbon::parse($project->created_at)->format('Y');
            return in_array((int) $year, $yearsRange);
        });

        // Agrupa los proyectos por año usando el campo created_at
        $grouped = $userProjects->groupBy(function ($project) {
            return Carbon::parse($project->created_at)->format('Y');
        });

        // Genera la estructura final con 'count' y 'projects'
        $chartDataUser = [];
        foreach ($yearsRange as $year) {
            $projectsOfYear = $grouped->get($year, collect());
            $chartDataUser[(string) $year] = [
                'count' => $projectsOfYear->count(),
                'projects' => $projectsOfYear->pluck('nombre_proyecto')->toArray(),
            ];
        }

        $this->projectsDataUser = $chartDataUser;
        $this->totalProjectsYearUser = array_sum(array_column($chartDataUser, 'count'));

        // Envío de datos al gráfico
        $this->dispatch('updateChart-User', dataUser: $this->projectsDataUser);
    }

    // propiedad para el término de búsqueda
    public $employeeSearch = '';

    // Modifica el método para filtrar por empleado (por ejemplo, filtrando por "nombre_completo")
    public function getProjectsCountByEmployees()
    {
        $userId = auth()->user()->empleado->id;
        $query = Empleado::query();
        $query->where('id', $userId);
        return $query->withCount('proyectos')->paginate(4);
    }

    public function empleadosVinculacion()
    {
        return Empleado::whereHas('proyectos')->count();
    }

    /**
     * Unifica en una sola lista "Mis proyectos" todos los formularios que el
     * docente tiene: Proyecto (Desarrollo Local/Voluntariado), PPS/Servicio
     * Social y ENF (Educación No Formal). Cada fila trae su propio stepper
     * de progreso, calculado según el flujo de aprobación real configurado
     * para ese formulario (no una lista fija de 5 etapas).
     */
    private function misFormularios(?int $empleadoId, ?int $userId): Collection
    {
        $rows = collect();

        if ($empleadoId) {
            Proyecto::query()
                ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
                ->where('empleado_proyecto.empleado_id', $empleadoId)
                ->select('proyecto.*')
                ->distinct()
                ->with(['categoria'])
                ->get()
                ->each(function (Proyecto $proyecto) use ($rows): void {
                    [$proceso, $documento] = $proyecto->procesoActivoParaStepper();

                    $rows->push([
                        'kind' => 'proyecto',
                        'nombre' => $proyecto->nombre_proyecto,
                        'categoria' => $proyecto->categoria->pluck('nombre')->implode(', ') ?: null,
                        'fecha_inicio' => $proyecto->fecha_inicio,
                        'fecha_fin' => $proyecto->fecha_finalizacion,
                        'fase' => $this->faseStepperLabel($proceso),
                        'stepper' => $this->stepperEstados($proyecto->firmasParaFicha($proceso, $documento)),
                        'sort_date' => $proyecto->created_at,
                    ]);
                });
        }

        if ($userId) {
            PpsServicioSocial::query()
                ->where('created_by', $userId)
                ->get()
                ->each(function (PpsServicioSocial $registro) use ($rows): void {
                    $rows->push([
                        'kind' => 'pps',
                        'nombre' => $registro->nombre_estudiante ?: $registro->nombre_institucion,
                        'categoria' => 'PPS / Servicio Social',
                        'fecha_inicio' => $registro->created_at,
                        'fecha_fin' => null,
                        'fase' => 'Aprobación',
                        'stepper' => $this->stepperEstados($registro->stepperDeAprobacion()),
                        'sort_date' => $registro->created_at,
                    ]);
                });

            EnfAccion::query()
                ->where(fn (Builder $query): Builder => $this->enfFormsQuery($query))
                ->get()
                ->each(function (EnfAccion $accion) use ($rows): void {
                    $rows->push([
                        'kind' => 'enf',
                        'nombre' => $accion->nombre_accion,
                        'categoria' => 'Educación no formal',
                        'fecha_inicio' => $accion->fecha_solicitud ?: $accion->created_at,
                        'fecha_fin' => null,
                        'fase' => 'Aprobación',
                        'stepper' => $this->enfStepper($accion),
                        'sort_date' => $accion->created_at,
                    ]);
                });
        }

        return $rows->sortByDesc('sort_date')->take(15)->values();
    }

    /**
     * Etiqueta legible de la fase que representa el stepper actual de un
     * Proyecto: aprobación inicial, informe intermedio o informe final. Solo
     * aplica a Proyecto — PPS/ENF tienen un único flujo (siempre "Aprobación").
     */
    private function faseStepperLabel(string $proceso): string
    {
        return match ($proceso) {
            Proyecto::FLUJO_INFORME_INTERMEDIO => 'Informe Intermedio',
            Proyecto::FLUJO_CIERRE_PROYECTO => 'Informe Final',
            default => 'Aprobación',
        };
    }

    /**
     * Convierte una colección [etapa, firma] (Proyecto::firmasParaFicha /
     * TieneFlujoPorEtapas::stepperDeAprobacion) en la forma plana que usa el
     * partial del stepper: ['nombre' => ..., 'estado' => aprobado|actual|pendiente|rechazado].
     */
    private function stepperEstados(Collection $filas): array
    {
        $actualMarcado = false;

        return $filas->map(function (array $fila) use (&$actualMarcado): array {
            $firma = $fila['firma'];
            $estado = 'pendiente';

            if ($fila['adoptada_antes'] ?? false) {
                $estado = 'adoptado';
            } elseif ($firma?->estado_revision === 'Aprobado') {
                $estado = 'aprobado';
            } elseif ($firma?->estado_revision === 'Rechazado') {
                $estado = 'rechazado';
            } elseif (! $actualMarcado) {
                $estado = 'actual';
                $actualMarcado = true;
            }

            return [
                'nombre' => $fila['etapa']->nombre,
                'estado' => $estado,
                'detalle' => $estado === 'adoptado'
                    ? $fila['etapa']->nombre.' — completada antes de la adopción al flujo'
                    : $fila['etapa']->nombre,
            ];
        })->all();
    }

    private function enfStepper(EnfAccion $accion): array
    {
        $proceso = EnfRevision::where('enf_accion_id', $accion->id)
            ->orderByDesc('id')
            ->value('proceso');

        if (! $proceso) {
            return [];
        }

        $actualMarcado = false;

        return EnfRevision::where('enf_accion_id', $accion->id)
            ->where('proceso', $proceso)
            ->orderByDesc('revision_ciclo')
            ->orderByDesc('id')
            ->get()
            ->unique('flujo_aprobacion_etapa_id')
            ->sortBy('orden')
            ->values()
            ->map(function (EnfRevision $revision) use (&$actualMarcado): array {
                $estado = 'pendiente';

                if ($revision->estado === 'APROBADO') {
                    $estado = 'aprobado';
                } elseif ($revision->estado === 'SUBSANACION') {
                    $estado = 'rechazado';
                } elseif (! $actualMarcado) {
                    $estado = 'actual';
                    $actualMarcado = true;
                }

                return ['nombre' => $revision->etapa_nombre, 'estado' => $estado];
            })
            ->all();
    }

    /**
     * Obtiene las últimas actividades del sistema.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getLatestActivities($limit = 4)
    {
        return Activity::query()
            ->latest()
            ->limit($limit)
            ->get();
    }

/**
 * Obtiene los últimos cambios de estado de proyectos del usuario autenticado.
 *
 * @param int $limit
 * @return \Illuminate\Support\Collection
 */
public function getLatestActivitiesUser($limit = 3)
{
    $user = auth()->user();
    $empleadoId = $user?->empleado?->id;

    // En el panel se muestran los formularios existentes, no solo los creados por el usuario.
    $proyectosIds = Proyecto::query()->pluck('id')->toArray();
        
    
    // Obtener IDs de los documentos asociados a estos proyectos
    $documentosIds = DocumentoProyecto::whereIn('proyecto_id', $proyectosIds)
        ->pluck('id')
        ->toArray();
    
    // Obtener todos los estados asociados a los proyectos y sus documentos
    $actividadesProyecto = EstadoProyecto::where(function ($query) use ($proyectosIds, $documentosIds) {
            // Estados de los proyectos
            if (! empty($proyectosIds)) {
                $query->where(function ($q) use ($proyectosIds) {
                    $q->where('estadoable_type', Proyecto::class)
                      ->whereIn('estadoable_id', $proyectosIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
            
            // Estados de los documentos (si existen)
            if (!empty($documentosIds)) {
                $query->orWhere(function ($q) use ($documentosIds) {
                    $q->where('estadoable_type', DocumentoProyecto::class)
                      ->whereIn('estadoable_id', $documentosIds);
                });
            }
        })
        ->with(['tipoestado', 'estadoable']) // Cargar relaciones para evitar múltiples consultas
        ->orderByDesc('created_at')
        ->limit($limit)
        ->get()
        ->map(function ($estado) {
            // Añadir información útil para la vista
            $estado->fecha_cambio = $estado->created_at->format('d/m/Y H:i');
            $estado->sort_timestamp = $estado->created_at;
            
            // Determinar el nombre del elemento (proyecto o documento)
            if ($estado->estadoable_type === Proyecto::class) {
                $estado->nombre_elemento = $estado->estadoable->nombre_proyecto ?? 'Proyecto';
                $estado->tipo_elemento = 'Proyecto';
            } else {
                $estado->nombre_elemento = $estado->estadoable->nombre ?? 'Documento';
                $estado->tipo_elemento = 'Documento';
            }
            
            return $estado;
        });

    return $actividadesProyecto
        ->concat($this->enfActividadesUser($user, $limit))
        ->sortByDesc('sort_timestamp')
        ->take($limit)
        ->values();
}

    private function enfActividadesUser($user, int $limit): Collection
    {
        if (! $user) {
            return collect();
        }

        $pendientesIds = $this->enfRevisionesDisponiblesQuery()
            ->limit(20)
            ->pluck('id');

        return EnfRevision::query()
            ->with('accion')
            ->whereHas('accion', fn (Builder $query): Builder => $this->enfFormsQuery($query))
            ->latest()
            ->limit($limit * 2)
            ->get()
            ->map(function (EnfRevision $revision) use ($pendientesIds): object {
                $esPendienteDelUsuario = $pendientesIds->contains($revision->id);
                $estado = new \stdClass();
                $estado->nombre = $esPendienteDelUsuario
                    ? ($revision->etapa_nombre ?: 'Pendiente de revision')
                    : $this->enfEstadoLabel($revision->accion?->estado_flujo ?: $revision->estado);

                return (object) [
                    'es_actual' => $esPendienteDelUsuario || in_array($revision->estado, ['ASIGNADO', 'EN_PROCESO', 'PENDIENTE'], true),
                    'tipo_elemento' => 'Proyecto',
                    'nombre_elemento' => $revision->accion?->nombre_accion ?: 'Educacion no formal',
                    'tipoestado' => $estado,
                    'comentario' => $this->enfActividadComentario($revision, $esPendienteDelUsuario),
                    'fecha_cambio' => $revision->updated_at?->format('d/m/Y H:i') ?: $revision->created_at?->format('d/m/Y H:i'),
                    'sort_timestamp' => $revision->updated_at ?: $revision->created_at,
                ];
            });
    }

    private function enfActividadComentario(EnfRevision $revision, bool $esPendienteDelUsuario): string
    {
        if ($esPendienteDelUsuario) {
            return 'Formulario de educacion no formal pendiente de revision en esta etapa.';
        }

        if (strtoupper((string) $revision->accion?->estado_flujo) === 'APROBADO') {
            return 'Todas las etapas del flujo de inscripcion ENF fueron aprobadas.';
        }

        return match ($revision->proceso) {
            EnfAccion::PROCESO_INFORME_INTERMEDIO => 'Informe intermedio enviado al flujo de revision.',
            EnfAccion::PROCESO_INFORME_FINAL => 'Informe final enviado al flujo de revision.',
            default => 'Formulario ENF enviado al flujo de revision.',
        };
    }

    private function enfAccionesUser(int $userId): Collection
    {
        return EnfAccion::query()
            ->where('creado_por_usuario_id', $userId)
            ->where(fn (Builder $query): Builder => $this->enfFormsQuery($query))
            ->get();
    }

    private function enfAccionesPanel(): Collection
    {
        return EnfAccion::query()
            ->where(fn (Builder $query): Builder => $this->enfFormsQuery($query))
            ->get();
    }

    private function enfFormsQuery(Builder $query): Builder
    {
        return $query->whereIn('codigo_formulario', ['FORM-DVUS-016', 'FORM-DVUS-018']);
    }

    private function enfEstadoLabel(?string $estado): string
    {
        return match (strtoupper((string) $estado)) {
            'BORRADOR' => 'Borrador',
            'EN_REVISION' => 'En revision',
            'APROBADO' => 'En curso',
            'FINALIZADO' => 'Finalizado',
            'SUBSANACION', 'SUBSANACIÓN' => 'Subsanar',
            default => $estado ?: 'Educacion no formal',
        };
    }

    private function enfRevisionesDisponiblesQuery(): Builder
    {
        $user = Auth::user();
        $activeRoleName = $user?->activeRole?->name;

        if (! $user || ! $activeRoleName) {
            return EnfRevision::query()->whereRaw('1 = 0');
        }

        $pendingStates = ['PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO'];

        return EnfRevision::query()
            ->whereHas('accion', fn (Builder $query): Builder => $this->enfFormsQuery($query))
            ->whereIn('estado', $pendingStates)
            ->whereNotExists(function ($previousQuery) use ($pendingStates): void {
                $previousQuery
                    ->selectRaw('1')
                    ->from('enf_revisiones as enf_revisiones_anteriores')
                    ->whereColumn('enf_revisiones_anteriores.enf_accion_id', 'enf_revisiones.enf_accion_id')
                    ->whereColumn('enf_revisiones_anteriores.proceso', 'enf_revisiones.proceso')
                    ->whereColumn('enf_revisiones_anteriores.revision_ciclo', 'enf_revisiones.revision_ciclo')
                    ->whereColumn('enf_revisiones_anteriores.orden', '<', 'enf_revisiones.orden')
                    ->whereIn('enf_revisiones_anteriores.estado', $pendingStates);
            })
            ->whereNotExists(function ($newerCycleQuery): void {
                $newerCycleQuery
                    ->selectRaw('1')
                    ->from('enf_revisiones as enf_revisiones_ciclo_nuevo')
                    ->whereColumn('enf_revisiones_ciclo_nuevo.enf_accion_id', 'enf_revisiones.enf_accion_id')
                    ->whereColumn('enf_revisiones_ciclo_nuevo.proceso', 'enf_revisiones.proceso')
                    ->whereColumn('enf_revisiones_ciclo_nuevo.revision_ciclo', '>', 'enf_revisiones.revision_ciclo');
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
    }

    /**
     * Obtiene los proyectos según el nombre del estado y los pagina.
     *
     * @param string $stateName 
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getProjectsByState($stateName)
{
    // Obtiene el objeto TipoEstado según el nombre
    $tipoEstado = TipoEstado::where('nombre', $stateName)->first();
    if (!$tipoEstado) {
        // Crear un paginador vacío en lugar de intentar paginar una colección
        return new \Illuminate\Pagination\LengthAwarePaginator(
            [], // Array vacío de elementos
            0,  // Total de elementos (0 ya que no hay ninguno)
            $this->perPage, // Elementos por página
            1   // Página actual
        );
    }

    // Consulta los proyectos que tienen asignado ese estado actual y los pagina
    return Proyecto::query()
        ->whereIn('id', function ($query) use ($tipoEstado) {
            $query->select('estadoable_id')
                ->from('estado_proyecto')
                ->where('estadoable_type', Proyecto::class)
                ->where('tipo_estado_id', $tipoEstado->id)
                ->where('es_actual', true);
        })
        ->orderBy('id', 'asc')
        ->paginate($this->perPage);
}

    /**
     * Obtiene proyectos cuyos estados se encuentren en la lista de nombres y los pagina.
     *
     * @param array $stateNames
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function proyectosEnRevisiones(array $stateNames)
    {
        $tipoEstadosIds = TipoEstado::whereIn('nombre', $stateNames)->pluck('id');

        return Proyecto::with('tipo_estado')
            ->whereIn('id', function ($query) use ($tipoEstadosIds) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->whereIn('tipo_estado_id', $tipoEstadosIds)
                    ->where('es_actual', true);
            })
            ->orderBy('id', 'asc')
            ->paginate($this->perPage);
    }

    /**
     * Obtiene los proyectos del usuario logueado según el nombre del estado y los pagina.
     *
     * @param string $stateName
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getProjectsByStateUser($stateName)
{
    $userId = auth()->user()->empleado->id;

    // Obtiene el objeto TipoEstado según el nombre
    $tipoEstado = TipoEstado::where('nombre', $stateName)->first();
    if (!$tipoEstado) {
        // Crear un paginador vacío en lugar de intentar paginar una colección
        return new \Illuminate\Pagination\LengthAwarePaginator(
            [], // Array vacío de elementos
            0,  // Total de elementos (0 ya que no hay ninguno)
            $this->perPage, // Elementos por página
            1   // Página actual
        );
    }

    // Consulta los proyectos que tienen asignado ese estado actual y pertenecen al usuario logueado
    return Proyecto::query()
        ->whereIn('id', function ($query) use ($tipoEstado) {
            $query->select('estadoable_id')
                ->from('estado_proyecto')
                ->where('estadoable_type', Proyecto::class)
                ->where('tipo_estado_id', $tipoEstado->id)
                ->where('es_actual', true);
        })
        ->whereIn('id', function ($query) use ($userId) {
            $query->select('proyecto_id')
                ->from('empleado_proyecto')
                ->where('empleado_id', $userId);
        })
        ->orderBy('id', 'asc')
        ->paginate($this->perPage);
}


/**
 * Obtiene proyectos del usuario logueado cuyos estados se encuentren en la lista de nombres y los pagina.
 *
 * @param array $stateNames
 * @param int $perPage
 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
 */
public function proyectosEnRevisionesUser(array $stateNames, $perPage = null)
{
    $userId = auth()->user()->empleado->id;
    $perPage = $perPage ?? $this->perPage;

    $tipoEstadosIds = TipoEstado::whereIn('nombre', $stateNames)->pluck('id');

    return Proyecto::with('tipo_estado')
        ->whereIn('id', function ($query) use ($tipoEstadosIds) {
            $query->select('estadoable_id')
                ->from('estado_proyecto')
                ->where('estadoable_type', Proyecto::class)
                ->whereIn('tipo_estado_id', $tipoEstadosIds)
                ->where('es_actual', true);
        })
        ->whereIn('id', function ($query) use ($userId) {
            $query->select('proyecto_id')
                ->from('empleado_proyecto')
                ->where('empleado_id', $userId);
        })
        ->orderBy('id', 'asc')
        ->paginate($perPage);
}

    public function render()
    {
        // empleados con su numero de proyectos
        $empleadosWithCount = $this->getProjectsCountByEmployees();
        // numero de empleados vinculados a proyectos
        $empleadosVinculacion = $this->empleadosVinculacion();
        // consultas de dashboards...
        $activities = $this->getLatestActivities();
        $activitiesUser = $this->getLatestActivitiesUser();
        // ADMIN DASHBOARD (consultas generales)
    
        $finalizados = collect(); // Default empty collection
        $tipoEstadoFinalizado = TipoEstado::where('nombre', 'Finalizado')->first();
        if ($tipoEstadoFinalizado) {
            $finalizados = Proyecto::query()
                ->whereIn('id', function ($query) use ($tipoEstadoFinalizado) {
                    $query->select('estadoable_id')
                        ->from('estado_proyecto')
                        ->where('estadoable_type', Proyecto::class)
                        ->where('tipo_estado_id', $tipoEstadoFinalizado->id)
                        ->where('es_actual', true);
                })->get();
        }

        $subsanacion = collect(); // Default empty collection
        $tipoEstadoSubsanacion = TipoEstado::where('nombre', 'Subsanacion')->first();
        if ($tipoEstadoSubsanacion) {
            $subsanacion = Proyecto::query()
                ->whereIn('id', function ($query) use ($tipoEstadoSubsanacion) {
                    $query->select('estadoable_id')
                        ->from('estado_proyecto')
                        ->where('estadoable_type', Proyecto::class)
                        ->where('tipo_estado_id', $tipoEstadoSubsanacion->id)
                        ->where('es_actual', true);
                })->get();
        }

        $ejecucion = collect(); // Default empty collection
        $tipoEstadoEnCurso = TipoEstado::where('nombre', 'En curso')->first();
        if ($tipoEstadoEnCurso) {
            $ejecucion = Proyecto::query()
                ->whereIn('id', function ($query) use ($tipoEstadoEnCurso) {
                    $query->select('estadoable_id')
                        ->from('estado_proyecto')
                        ->where('estadoable_type', Proyecto::class)
                        ->where('tipo_estado_id', $tipoEstadoEnCurso->id)
                        ->where('es_actual', true);
                })->get();
        }

        $borrador = collect(); // Default empty collection
        $tipoEstadoBorrador = TipoEstado::where('nombre', 'Borrador')->first();
        if ($tipoEstadoBorrador) {
            $borrador = Proyecto::query()
                ->whereIn('id', function ($query) use ($tipoEstadoBorrador) {
                    $query->select('estadoable_id')
                        ->from('estado_proyecto')
                        ->where('estadoable_type', Proyecto::class)
                        ->where('tipo_estado_id', $tipoEstadoBorrador->id)
                        ->where('es_actual', true);
                })->get();
        }

                $proyectos = Proyecto::query()
                    ->whereIn('id', function ($query) {
                        $query->select('estadoable_id')
                            ->from('estado_proyecto')
                            ->where('estadoable_type', Proyecto::class)
                            ->where('es_actual', true);
                    })->get();

                $empleados = Empleado::count();

                $proyectosTable = Proyecto::query()
                    ->whereIn('id', function ($query) {
                        $query->select('estadoable_id')
                            ->from('estado_proyecto')
                            ->where('estadoable_type', Proyecto::class)
                            ->where('es_actual', true);
                    })
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

                
        // USER DASHBOARD (filtrado por usuario autenticado con la tabla pivote empleado_proyecto)
        $authUserId = auth()->id();
        $userId = auth()->user()->empleado->id;
        $enfPendientesRevisionUser = $this->enfRevisionesDisponiblesQuery()->get();
        $enfAccionesUser = $this->enfAccionesPanel();

        // Obtén el id del estado "Finalizado"
        $finalizadosUser = collect(); // Default empty collection
        $tipoEstadoFinalizado = TipoEstado::where('nombre', 'Finalizado')->first();
        if ($tipoEstadoFinalizado) {
            $finalizadosUser = Proyecto::query()
                ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
                ->join('estado_proyecto', 'estado_proyecto.estadoable_id', '=', 'proyecto.id')
                ->where('empleado_proyecto.empleado_id', $userId)
                ->where('estado_proyecto.estadoable_type', Proyecto::class)
                ->where('estado_proyecto.tipo_estado_id', $tipoEstadoFinalizado->id)
                ->where('estado_proyecto.es_actual', true)
                ->distinct()
                ->get();
        }

        // Para el estado "Subsanacion"
        $proyectosSubsanar = collect(); // Default empty collection
        $tipoEstadoSubsanacion = TipoEstado::where('nombre', 'Subsanacion')->first();
        if ($tipoEstadoSubsanacion) {
            $proyectosSubsanar = Proyecto::query()
                ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
                ->join('estado_proyecto', 'estado_proyecto.estadoable_id', '=', 'proyecto.id')
                ->where('empleado_proyecto.empleado_id', $userId)
                ->where('estado_proyecto.estadoable_type', Proyecto::class)
                ->where('estado_proyecto.tipo_estado_id', $tipoEstadoSubsanacion->id)
                ->where('estado_proyecto.es_actual', true)
                ->distinct()
                ->get();
        }
        $ppsSubsanar = PpsServicioSocial::query()
            ->where('created_by', $authUserId)
            ->whereHas('estadoActual.tipoestado', fn ($q) => $q->whereIn('nombre', ['Rechazado', 'Subsanacion']))
            ->get();
        $enfSubsanarUser = $enfAccionesUser->filter(fn (EnfAccion $accion): bool => in_array(strtoupper((string) $accion->estado_flujo), ['SUBSANACION', 'SUBSANACIÓN'], true));
        $totalSubsanar = $proyectosSubsanar->count() + $ppsSubsanar->count() + $enfSubsanarUser->count();
        $subsanacionUser = $proyectosSubsanar->concat($ppsSubsanar);

        // Para el estado "Borrador"
        $borradorUser = collect(); // Default empty collection
        $tipoEstadoBorrador = TipoEstado::where('nombre', 'Borrador')->first();
        if ($tipoEstadoBorrador) {
            $borradorUser = Proyecto::query()
                ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
                ->join('estado_proyecto', 'estado_proyecto.estadoable_id', '=', 'proyecto.id')
                ->where('empleado_proyecto.empleado_id', $userId)
                ->where('estado_proyecto.estadoable_type', Proyecto::class)
                ->where('estado_proyecto.tipo_estado_id', $tipoEstadoBorrador->id)
                ->where('estado_proyecto.es_actual', true)
                ->distinct()
                ->get();
        }

        // Para el estado "En curso"
        $ejecucionUser = collect(); // Default empty collection
        $tipoEstadoEnCurso = TipoEstado::where('nombre', 'En curso')->first();
        if ($tipoEstadoEnCurso) {
            $ejecucionUser = Proyecto::query()
                ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
                ->join('estado_proyecto', 'estado_proyecto.estadoable_id', '=', 'proyecto.id')
                ->where('empleado_proyecto.empleado_id', $userId)
                ->where('estado_proyecto.estadoable_type', Proyecto::class)
                ->where('estado_proyecto.tipo_estado_id', $tipoEstadoEnCurso->id)
                ->where('estado_proyecto.es_actual', true)
                ->distinct()
                ->get();
        }

        $proyectosUser = Proyecto::query()->get();

        // Mis formularios: unifica Proyecto (Desarrollo Local/Voluntariado), PPS/SS
        // y ENF en una sola lista, cada fila con su stepper de progreso calculado
        // según el flujo de aprobación configurado (incluye informe intermedio/
        // final si el proyecto ya pasó la aprobación inicial y está en esa fase).
        $misFormularios = $this->misFormularios($userId, $authUserId);


        //obtener lista de años en los cuales hay proyectos creados
        $años = Proyecto::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // cObtener proyectos para los estados solicitados:
        $estadosUser = [
            'Esperando documento',
            'Subsanar documento',
            'Enlace Vinculacion',
            'Coordinador Proyecto',
            'Jefe Departamento',
            'Director Centro',
            'En revision final',
            'Aprobado',
            'Subsanacion',
            'Rechazado',
            'Inscrito',
            'Cancelado',
            'En revision'
        ];

        $estados = [
            'Esperando documento',
            'Subsanar documento',
            'Enlace Vinculacion',
            'Coordinador Proyecto',
            'Jefe Departamento',
            'Director Centro',
            'En revision final',
            'Aprobado',
            'Subsanacion',
            'Rechazado',
            'Inscrito',
            'Cancelado',
            'En revision'
        ];

        // Obtener proyectos en Revisión
        $enRevision = $this->proyectosEnRevisiones($estados);

        // Obtener proyectos Finalizados
        $enFinalizados = $this->getProjectsByState('Finalizado');

        // Obtener proyectos en Ejecución (En curso)
        $enEjecucion = $this->getProjectsByState('En curso');

        // Obtener proyectos en Borrador
        $enBorrador = $this->getProjectsByState('Borrador');

        //Panel Proyecto User
        // Obtener proyectos en Revisión
        $enRevisionUser = $this->proyectosEnRevisionesUser($estadosUser);

        // Obtener proyectos Finalizados
        $enFinalizadosUser = $this->getProjectsByStateUser('Finalizado');

        // Obtener proyectos en Ejecución (En curso)
        $enEjecucionUser = $this->getProjectsByStateUser('En curso');

        // Obtener proyectos en Borrador
        $enBorradorUser = $this->getProjectsByStateUser('Borrador');

        $totalProyectosUser = $proyectos->count() + $enfAccionesUser->count();
        $totalFinalizadosUser = $finalizados->count()
            + $enfAccionesUser->filter(fn (EnfAccion $accion): bool => strtoupper((string) $accion->estado_flujo) === 'FINALIZADO')->count();
        $totalEjecucionUser = $ejecucion->count()
            + $enfAccionesUser->filter(fn (EnfAccion $accion): bool => strtoupper((string) $accion->estado_flujo) === 'APROBADO')->count();
        $totalBorradorUser = $borrador->count()
            + $enfAccionesUser->filter(fn (EnfAccion $accion): bool => strtoupper((string) $accion->estado_flujo) === 'BORRADOR')->count();
        $totalRevisionUser = $enRevision->total()
            + $enfAccionesUser->filter(fn (EnfAccion $accion): bool => strtoupper((string) $accion->estado_flujo) === 'EN_REVISION')->count();
        $totalPendientesRevisionUser = $enfPendientesRevisionUser->count();

        return view('livewire.inicio.dashboards.dasboard-docente', [
            'empleadosWithCount' => $empleadosWithCount,
            'empleadosVinculacion' => $empleadosVinculacion,
            'activities' => $activities,
            'activitiesUser' => $activitiesUser,
            // admin dashboard
            'finalizados' => $finalizados,
            'subsanacion' => $subsanacion,
            'ejecucion' => $ejecucion,
            'proyectos' => $proyectos,
            'borrador' => $borrador,
            'empleados' => $empleados,
            'proyectosTable' => $proyectosTable,
            // User dashboard
            'finalizadosUser' => $finalizadosUser,
            'subsanacionUser' => $subsanacionUser,
            'proyectosSubsanar' => $proyectosSubsanar,
            'ppsSubsanar' => $ppsSubsanar,
            'totalSubsanar' => $totalSubsanar,
            'ejecucionUser' => $ejecucionUser,
            'borradorUser' => $borradorUser,
            'proyectosUser' => $proyectosUser,
            'misFormularios' => $misFormularios,
            'totalProyectosUser' => $totalProyectosUser,
            'totalFinalizadosUser' => $totalFinalizadosUser,
            'totalEjecucionUser' => $totalEjecucionUser,
            'totalBorradorUser' => $totalBorradorUser,
            'totalRevisionUser' => $totalRevisionUser,
            'totalPendientesRevisionUser' => $totalPendientesRevisionUser,
            //chartAdmin
            'chartData' => array_values($this->projectsData),
            'años' => $años,
            //chartUser
            'chartDataUser' => $this->projectsDataUser,
            //mostrar las colecciones por estado
            'enFinalizados' => $enFinalizados,
            'enFinalizadosCount'  => $enFinalizados->count(),  // Total de proyectos finalizados
            'enEjecucion' => $enEjecucion,
            'enEjecucionCount' => $enEjecucion->count(),  // Total de proyectos en ejecución
            'enBorrador' => $enBorrador,
            'enBorradorCount' => $enBorrador->count(),  // Total de proyectos en borrador
            'enRevision' => $enRevision,
            'enRevisionCount' => $enRevision->count(),  // Total de proyectos en revisión
            //mostrar panel de estados para user
            'enFinalizadosUser' => $enFinalizadosUser,  // Total de proyectos finalizados
            'enEjecucionUser' => $enEjecucionUser, // Total de proyectos en ejecución
            'enBorradorUser' => $enBorradorUser,// Total de proyectos en borrador
            'enRevisionUser' => $enRevisionUser,
          
        ]);
    }
}

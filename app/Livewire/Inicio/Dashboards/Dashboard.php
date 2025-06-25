<?php

namespace App\Livewire\Inicio\Dashboards;

use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Proyecto;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\DocumentoProyecto; 
use Livewire\Component;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

class Dashboard extends Component
{
    public $perPage = 5;
    public $selectedYear = null;
    public $projectsData = [];
    public $projectsDataUser = [];
    public $totalProjectsYear = 0;     // Total para el año seleccionado (admin)
    public $totalProjectsYearUser = 0;

    // Método para cargar más proyectos en la tabla
    public function loadMore()
    {
        $this->perPage += 5;
    }

    public function mount()
    {
        $this->selectedYear = now()->year;
        $this->updateChartData();
        $this->updateChartDataUser();
        // si el usuario autenticado tiene el permiso docente-cambiar-datos-personales
        // redirigirlo a la pagina de configuracion de su perfil
        if (auth()->user()->can('docente-cambiar-datos-personales')) {
            return redirect()->route('mi_perfil');
        };
    }

    // año fijo de inicio
    public $chartStartYear = 2020;
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
        $userProjects = Proyecto::join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->where('empleado_proyecto.empleado_id', $userId)
            ->select('proyecto.*')
            ->get();

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
        $query = Empleado::query();

        if ($this->employeeSearch) {
            $query->where('nombre_completo', 'like', '%' . $this->employeeSearch . '%');
        }

        return $query->withCount('proyectos')->paginate(4);
    }

    public function empleadosVinculacion()
    {
        return Empleado::whereHas('proyectos')->count();
    }

    /**
     * Obtiene los últimos cambios de estado de todos los proyectos.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getLatestActivities($limit = 3)
    {
        // Primero obtenemos todos los proyectos
        $proyectosIds = Proyecto::pluck('id')->toArray();
        
        // Luego obtenemos los documentos de esos proyectos
        $documentosIds = DocumentoProyecto::whereIn('proyecto_id', $proyectosIds)
            ->pluck('id')
            ->toArray();
        
        // Obtener todos los estados asociados a los proyectos y sus documentos
        return \App\Models\Estado\EstadoProyecto::where(function ($query) use ($proyectosIds, $documentosIds) {
                // Estados de los proyectos
                $query->where(function ($q) use ($proyectosIds) {
                    $q->where('estadoable_type', \App\Models\Proyecto\Proyecto::class)
                    ->whereIn('estadoable_id', $proyectosIds);
                });
                
                // Estados de los documentos (si existen)
                if (!empty($documentosIds)) {
                    $query->orWhere(function ($q) use ($documentosIds) {
                        $q->where('estadoable_type', \App\Models\Proyecto\DocumentoProyecto::class)
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
                
                // Determinar el nombre del elemento (proyecto o documento)
                if ($estado->estadoable_type === \App\Models\Proyecto\Proyecto::class) {
                    $estado->nombre_elemento = $estado->estadoable->nombre_proyecto ?? 'Proyecto';
                    $estado->tipo_elemento = 'Proyecto';
                } else {
                    $estado->nombre_elemento = $estado->estadoable->nombre ?? 'Documento';
                    $estado->tipo_elemento = 'Documento';
                }
                
                return $estado;
            });
    }

    /**
     * Obtiene las últimas actividades del usuario autenticado.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getLatestActivitiesUser($limit = 4)
    {
        $userId = auth()->id();

        return Activity::query()
            ->where('causer_id', $userId)
            ->latest()
            ->limit($limit)
            ->get();
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
        // Create an empty paginator instead of trying to paginate a collection
        return new \Illuminate\Pagination\LengthAwarePaginator(
            [], // Empty array of items
            0,  // Total items (0 since there are none)
            $this->perPage, // Items per page
            1   // Current page
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
     * Obtiene los proyectos del usuario logueado según el nombre del estado.
     *
     * @param string $stateName 
     * @return \Illuminate\Support\Collection
     */
    public function getProjectsByStateUser($stateName)
    {
        $userId = auth()->user()->empleado->id;

        // Obtiene el objeto TipoEstado según el nombre
        $tipoEstado = TipoEstado::where('nombre', $stateName)->first();
        if (!$tipoEstado) {
            return collect();
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
            ->get();
    }

    /**
     * Obtiene proyectos del usuario logueado cuyos estados se encuentren en la lista de nombres.
     *
     * @param array $stateNames
     * @return \Illuminate\Support\Collection
     */
    public function proyectosEnRevisionesUser(array $stateNames)
    {
        $userId = auth()->user()->empleado->id;

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
            ->get();
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
$finalizados = collect();  // Default empty collection
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

$subsanacion = collect();  // Default empty collection
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

$ejecucion = collect();  // Default empty collection
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

$borrador = collect();  // Default empty collection
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
$userId = auth()->user()->empleado->id;

// Obtén el id del estado "Finalizado"
$finalizadosUser = collect();  // Default empty collection
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
$subsanacionUser = collect();  // Default empty collection
$tipoEstadoSubsanacion = TipoEstado::where('nombre', 'Subsanacion')->first();
if ($tipoEstadoSubsanacion) {
    $subsanacionUser = Proyecto::query()
        ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
        ->join('estado_proyecto', 'estado_proyecto.estadoable_id', '=', 'proyecto.id')
        ->where('empleado_proyecto.empleado_id', $userId)
        ->where('estado_proyecto.estadoable_type', Proyecto::class)
        ->where('estado_proyecto.tipo_estado_id', $tipoEstadoSubsanacion->id)
        ->where('estado_proyecto.es_actual', true)
        ->distinct()
        ->get();
}

// Para el estado "Borrador"
$borradorUser = collect();  // Default empty collection
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
$ejecucionUser = collect();  // Default empty collection
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

        // Si necesitas obtener todos los proyectos asociados al usuario sin filtrar por estado:
        $proyectosUser = Proyecto::query()
            ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->where('empleado_proyecto.empleado_id', $userId)
            ->distinct()
            ->paginate(10);


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

        return view('livewire.inicio.dashboards.dashboard', [
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
            'ejecucionUser' => $ejecucionUser,
            'borradorUser' => $borradorUser,
            'proyectosUser' => $proyectosUser,
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
            'enFinalizadosUser' => $enFinalizadosUser,
            'enFinalizadosUserCount'  => $enFinalizadosUser->count(),  // Total de proyectos finalizados
            'enEjecucionUser' => $enEjecucionUser,
            'enEjecucionUserCount' => $enEjecucionUser->count(),  // Total de proyectos en ejecución
            'enBorradorUser' => $enBorradorUser,
            'enBorradorUserCount' => $enBorradorUser->count(),  // Total de proyectos en borrador
            'enRevisionUser' => $enRevisionUser,
            'enRevisionUserCount' => $enRevisionUser->count(),  // Total de proyectos en revisión
        ]);
    }
}

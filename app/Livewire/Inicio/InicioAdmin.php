<?php

namespace App\Livewire\Inicio;

use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Proyecto;
use App\Models\Personal\Empleado;
use Livewire\Component;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

class InicioAdmin extends Component
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

    // Nuevo: año fijo de inicio
    public $chartStartYear = 2020;
    // Nuevo: determina si se muestra el rango completo o solo los últimos 4 años (por defecto se muestran solo los últimos 4)
    public $chartFullRange = false;

    public function mount()
    {
        $this->selectedYear = now()->year;
        $this->updateChartData();
        $this->updateChartDataUser();
    }
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
            // Retornamos un paginador de colección vacía
            return collect()->paginate($this->perPage);
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
        $finalizados = Proyecto::query()
            ->whereIn('id', function ($query) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->where('tipo_estado_id', TipoEstado::where('nombre', 'Finalizado')->first()->id)
                    ->where('es_actual', true);
            })->get();

        $subsanacion = Proyecto::query()
            ->whereIn('id', function ($query) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->where('tipo_estado_id', TipoEstado::where('nombre', 'Subsanacion')->first()->id)
                    ->where('es_actual', true);
            })->get();

        $ejecucion = Proyecto::query()
            ->whereIn('id', function ($query) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->where('tipo_estado_id', TipoEstado::where('nombre', 'En curso')->first()->id)
                    ->where('es_actual', true);
            })->get();

        $borrador = Proyecto::query()
            ->whereIn('id', function ($query) {
                $query->select('estadoable_id')
                    ->from('estado_proyecto')
                    ->where('estadoable_type', Proyecto::class)
                    ->where('tipo_estado_id', TipoEstado::where('nombre', 'Borrador')->first()->id)
                    ->where('es_actual', true);
            })->get();

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
        $finalizadoEstadoId = TipoEstado::where('nombre', 'Finalizado')->first()->id;

        $finalizadosUser = Proyecto::query()
            ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->join('estado_proyecto', 'estado_proyecto.estadoable_id', '=', 'proyecto.id')
            ->where('empleado_proyecto.empleado_id', $userId)
            ->where('estado_proyecto.estadoable_type', Proyecto::class)
            ->where('estado_proyecto.tipo_estado_id', $finalizadoEstadoId)
            ->where('estado_proyecto.es_actual', true)
            ->distinct()
            ->get();

        // Para el estado "Subsanacion"
        $subsanacionEstadoId = TipoEstado::where('nombre', 'Subsanacion')->first()->id;

        $subsanacionUser = Proyecto::query()
            ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->join('estado_proyecto', 'estado_proyecto.estadoable_id', '=', 'proyecto.id')
            ->where('empleado_proyecto.empleado_id', $userId)
            ->where('estado_proyecto.estadoable_type', Proyecto::class)
            ->where('estado_proyecto.tipo_estado_id', $subsanacionEstadoId)
            ->where('estado_proyecto.es_actual', true)
            ->distinct()
            ->get();

        // Para el estado "Borrador"
        $borradorEstadoId = TipoEstado::where('nombre', 'Borrador')->first()->id;

        $borradorUser = Proyecto::query()
            ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->join('estado_proyecto', 'estado_proyecto.estadoable_id', '=', 'proyecto.id')
            ->where('empleado_proyecto.empleado_id', $userId)
            ->where('estado_proyecto.estadoable_type', Proyecto::class)
            ->where('estado_proyecto.tipo_estado_id', $borradorEstadoId)
            ->where('estado_proyecto.es_actual', true)
            ->distinct()
            ->get();

        // Para el estado "En curso"
        $ejecucionEstadoId = TipoEstado::where('nombre', 'En curso')->first()->id;
        $ejecucionUser = Proyecto::query()
            ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->join('estado_proyecto', 'estado_proyecto.estadoable_id', '=', 'proyecto.id')
            ->where('empleado_proyecto.empleado_id', $userId)
            ->where('estado_proyecto.estadoable_type', Proyecto::class)
            ->where('estado_proyecto.tipo_estado_id', $ejecucionEstadoId)
            ->where('estado_proyecto.es_actual', true)
            ->distinct()
            ->get();

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

        return view('livewire.inicio.inicio-admin', [
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

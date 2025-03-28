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
    public $selectedYear = null;
    public $projectsData = [];
    public $projectsDataUser = [];
    public $totalProjectsYear = 0;     // Total para el año seleccionado (admin)
    public $totalProjectsYearUser = 0;

    // Nuevo: año fijo de inicio
    public $chartStartYear = 2021;
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
        // Consulta los proyectos agrupados por año
        $this->projectsDataUser = Proyecto::join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->selectRaw('YEAR(proyecto.created_at) as year, COUNT(*) as count')
            ->where('empleado_proyecto.empleado_id', auth()->user()->empleado->id)
            ->groupBy('year')
            ->orderBy('year')
            ->pluck('count', 'year')
            ->toArray();

        $end = now()->year;
        if ($this->chartFullRange) {
            // Muestra desde el año de inicio hasta el año actual
            $yearsRange = range($this->chartStartYear, $end);
        } else {
            // Muestra solo los últimos 4 años, respetando el año de inicio mínimo
            $startPeriod = max($this->chartStartYear, $end - 3); // 4 años: $end-3, $end-2, $end-1, $end
            $yearsRange = range($startPeriod, $end);
        }

        // Rellena con 0 en los años faltantes en el rango determinado
        $this->projectsDataUser = array_replace(array_fill_keys($yearsRange, 0), $this->projectsDataUser);

        // Calcula el total de proyectos en el rango de años
        $this->totalProjectsYearUser = array_sum($this->projectsDataUser);

        // Envía el array asociativo para que en el gráfico se utilicen las claves como categorías
        $this->dispatch('updateChart-User', dataUser: $this->projectsDataUser);

    }

    public function getProjectsCountByEmployees()
    {
        return Empleado::withCount('proyectos')->paginate(4);
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
            ->get();

        //obtener lista de años en los cuales hay proyectos creados
        $años = Proyecto::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

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
        ]);
    }
}

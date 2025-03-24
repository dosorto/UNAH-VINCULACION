<?php

namespace App\Livewire\Inicio;

use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Proyecto;
use App\Models\Personal\Empleado;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class InicioAdmin extends Component
{
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
        $empleadosWithCount = $this->getProjectsCountByEmployees();
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
        $userId =  auth()->user()->empleado->id;

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

        // Si necesitas obtener todos los proyectos asociados al usuario sin filtrar por estado:
        $proyectosUser = Proyecto::query()
            ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->where('empleado_proyecto.empleado_id', $userId)
            ->distinct()
            ->get();

        return view('livewire.inicio.inicio-admin', [
            'empleadosWithCount' => $empleadosWithCount,
            'empleadosVinculacion' => $empleadosVinculacion,
            'activities' => $activities,
            'activitiesUser' => $activitiesUser,
            // admin dashboard
            'finalizados'        => $finalizados,
            'subsanacion'        => $subsanacion,
            'ejecucion'         => $ejecucion,
            'proyectos'          => $proyectos,
            'borrador'           => $borrador,
            'empleados'          => $empleados,
            'proyectosTable'     => $proyectosTable,
            // User dashboard
            'finalizadosUser'    => $finalizadosUser,
            'subsanacionUser'    => $subsanacionUser,
            'borradorUser'       => $borradorUser,
            'proyectosUser'      => $proyectosUser,
        ]);
    }
}

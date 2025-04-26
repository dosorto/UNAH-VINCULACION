<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\EstadoProyecto;
use App\Models\Proyecto\Proyecto;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class HistorialProyecto extends Component
{
    public $proyecto;
    public $logs = [];

    public function mount(Proyecto $proyecto)
    {
        $this->proyecto = $proyecto;
        
        // Obtener todos los logs relacionados con este proyecto
        $this->logs = Activity::where(function($query) use ($proyecto) {
                // Logs directos del proyecto
                $query->where('subject_type', Proyecto::class)
                      ->where('subject_id', $proyecto->id);
            })
            ->orWhere(function($query) use ($proyecto) {
                // Logs donde el proyecto aparece en los atributos
                $query->whereJsonContains('properties->attributes->proyecto_id', $proyecto->id)
                      ->orWhereJsonContains('properties->attributes->id', $proyecto->id);
            })
            ->orWhere(function($query) use ($proyecto) {
                // Logs de cambios de estado del proyecto
                $query->where('subject_type', EstadoProyecto::class)
                      ->whereIn('subject_id', function($subQuery) use ($proyecto) {
                          $subQuery->select('id')
                                  ->from('estado_proyecto')
                                  ->where('estadoable_type', Proyecto::class)
                                  ->where('estadoable_id', $proyecto->id);
                      });
            })
            ->with('causer') // Carga anticipada del usuario que realizó la acción
            ->orderByDesc('created_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.docente.proyectos.historial-proyecto', [
            'proyecto' => $this->proyecto,
            'logs' => $this->logs
        ]);
    }
}
<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\EstadoProyecto;
use App\Models\Proyecto\EmpleadoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\DocumentoProyecto;
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
    $empleadoProyecto = EmpleadoProyecto::where('proyecto_id', $proyecto->id)->firstOrFail();
    $this->authorize('view', $empleadoProyecto);

    // Obtener IDs de los documentos asociados al proyecto
    $documentosIds = DocumentoProyecto::where('proyecto_id', $proyecto->id)->pluck('id')->toArray();

    // Obtener todos los estados asociados al proyecto y a sus documentos (historial de movimientos)
    $this->estados = EstadoProyecto::where(function ($query) use ($proyecto, $documentosIds) {
            $query->where(function ($q) use ($proyecto) {
                $q->where('estadoable_type', Proyecto::class)
                  ->where('estadoable_id', $proyecto->id);
            });
            if (!empty($documentosIds)) {
                $query->orWhere(function ($q) use ($documentosIds) {
                    $q->where('estadoable_type', DocumentoProyecto::class)
                      ->whereIn('estadoable_id', $documentosIds);
                });
            }
        })
        ->orderByDesc('created_at')
        ->get();
}

    public function render()
    {
        return view('livewire.docente.proyectos.historial-proyecto', [
            'proyecto' => $this->proyecto,
            'estados' => $this->estados
        ]);
    }
}
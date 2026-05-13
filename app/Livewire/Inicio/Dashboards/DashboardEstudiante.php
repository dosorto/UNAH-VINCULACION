<?php

namespace App\Livewire\Inicio\Dashboards;

use App\Models\Proyecto\Proyecto;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class DashboardEstudiante extends Component
{
    use WithPagination;

    public function render(): View
    {
        $estudiante = auth()->user()?->estudiante;

        $proyectos = $estudiante
            ? Proyecto::with('estudianteProyecto')
                ->whereHas('estudianteProyecto', fn($q) => $q->where('estudiante_id', $estudiante->id))
                ->paginate(10)
            : collect()->paginate(10);

        return view('livewire.inicio.dashboards.dashboard-estudiante', [
            'proyectos'  => $proyectos,
            'estudiante' => $estudiante,
        ]);
    }
}

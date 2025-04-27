<?php

namespace App\Livewire\Inicio\Dashboards;

use Livewire\Component;
use App\Models\Proyecto;
use Illuminate\Support\Facades\Auth;

class DashboardEstudiante extends Component
{
    public $perPage = 10; // Número de proyectos por página

    public function render()
    {
        $estudiante = Auth::user()->estudiante; // Obtener el estudiante autenticado

        $proyectos = collect(); // Colección vacía por defecto

        if ($estudiante) {
            // Cargar los proyectos en los que participa el estudiante con paginación
            $proyectos = $estudiante->proyectos()->paginate($this->perPage);
        }

        return view('livewire.inicio.dashboards.dashboard-estudiante', [
            'proyectos' => $proyectos, // Pasar los proyectos a la vista
        ]);
    }
}
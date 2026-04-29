<?php

namespace App\Livewire\Proyectos\Vinculacion;

use Livewire\Component;
use App\Models\Proyecto\Proyecto;
use App\Support\Notification;
use Illuminate\Contracts\View\View;

class EditProyectoVinculacionForm extends Component
{
    public function mount(Proyecto $proyecto): void
    {
        if ($proyecto->coordinador_proyecto->first()?->empleado_id !== auth()->user()->empleado?->id) {
            Notification::make()->title('Sin permiso')->body('No tienes permisos para editar este proyecto')->danger()->send();
            redirect()->route('proyectosDocente');
            return;
        }
        redirect()->route('crearProyectoVinculacion', ['record' => $proyecto->id]);
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.edit-proyecto-vinculacion-form');
    }
}

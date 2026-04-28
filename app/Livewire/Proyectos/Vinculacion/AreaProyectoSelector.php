<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AreaProyectoSelector extends Component
{
    public function mostrarMensajeDesarrolloLocal(): void
    {
        Notification::make()
            ->title('Importante')
            ->body('Para registrar un proyecto de Vinculación, todos los integrantes deben estar registrados en NEXO.')
            ->warning()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.areas-proyecto-selector');
    }
}
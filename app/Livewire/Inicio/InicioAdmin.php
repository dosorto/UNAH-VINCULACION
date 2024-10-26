<?php

namespace App\Livewire\Inicio;

use Livewire\Component;

class InicioAdmin extends Component
{
    public function render()
    {
        return view('livewire.inicio.inicio-admin')
            ->layout('components.panel.modulos.inicio-admin');
    }
}

<?php

namespace App\Livewire\Proyectos\Vinculacion;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class CategoriaProyectoSelector extends Component
{
    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.categorias-proyecto-selector');
    }
}
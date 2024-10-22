<?php

namespace App\View\Components\Fichas;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FichaProyectoVinculacion extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.fichas.ficha-proyecto-vinculacion');
    }
}

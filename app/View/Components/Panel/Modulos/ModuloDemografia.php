<?php

namespace App\View\Components\Panel\Modulos;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ModuloDemografia extends Component
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
        return view('components.panel.modulos.modulo-demografia');
    }
}

<?php

namespace App\Livewire\Proyectos\Vinculacion;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CategoriaProyectoSelector extends Component
{
    public function render(): View
    {
        $tiposAccion = DB::table('vinculacion_tipos_accion')
            ->where('codigo', '!=', 'EDUCACION_NO_FORMAL')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('livewire.proyectos.vinculacion.categorias-proyecto-selector', [
            'tiposAccion' => $tiposAccion,
        ]);
    }
}

<?php

namespace App\Livewire\Inicio;

use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Proyecto;
use App\Models\Personal\Empleado;
use Livewire\Component;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

class InicioAdmin extends Component
{


    public function mount()
    {
        if (auth()->user()->can('cambiar-datos-personales'))
            return redirect()->route('mi_perfil');
    }



    public function render()
    {
        return view('livewire.inicio.inicio-admin');
    }
}
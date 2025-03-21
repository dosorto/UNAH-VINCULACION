<?php

namespace App\Livewire\Inicio;

use Livewire\Component;

class InicioAdmin extends Component
{

    public function mount()
    {

        // si el usuario autenticado tiene el permiso docente-cambiar-datos-personales
        // redirigirlo a la pagina de configuracion de su perfil
        if (auth()->user()->can('docente-cambiar-datos-personales')) {
            return redirect()->route('mi_perfil');
        }
;
    }

    public function render()
    {
        return view('livewire.inicio.inicio-admin');
    }
}

<?php

namespace App\Livewire\Personal\Perfil;

use Livewire\Component;

class EditPerfil extends Component
{

    public function render()
    {
        // verificar si el modelo empleado no es nulo 
        if (auth()->user()->empleado != null)
            $typeUser = 'Empleado';
        else if (auth()->user()->estudiante != null)
            $typeUser = 'Estudiante';

        return view('livewire.personal.perfil.edit-perfil', [
            'typeUser' => $typeUser,
        ]);
    }
}

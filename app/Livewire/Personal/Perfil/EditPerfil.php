<?php

namespace App\Livewire\Personal\Perfil;

use App\Support\ProfileCompletion;
use Livewire\Component;

class EditPerfil extends Component
{
    public function render()
    {
        $typeUser = null;

        if (auth()->user()->empleado != null) {
            $typeUser = 'Empleado';
        } elseif (auth()->user()->estudiante != null) {
            $typeUser = 'Estudiante';
        }

        return view('livewire.personal.perfil.edit-perfil', [
            'typeUser' => $typeUser,
            'profileCompletionRequired' => ProfileCompletion::isRequired(auth()->user()),
        ]);
    }
}

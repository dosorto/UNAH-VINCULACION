<?php

namespace App\Livewire\Inicio;

use Livewire\Component;

class InicioAdmin extends Component
{
    public function mount()
    {
        $activeRole = auth()->user()?->activeRole;

        if ($activeRole
            && str_starts_with($activeRole->name, 'DAFT ')
            && $activeRole->permissions()->where('name', 'daft.acceso')->exists()) {
            return redirect()->route('daft.dashboard');
        }

        if (auth()->user()->can('perfil.editar')) {
            return redirect()->route('completar_perfil');
        }
    }

    public function render()
    {
        return view('livewire.inicio.inicio-admin');
    }
}

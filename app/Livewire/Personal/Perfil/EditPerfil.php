<?php

namespace App\Livewire\Personal\Perfil;

use App\Models\Personal\Empleado;
use App\Services\Auth\NewUserOnboardingService;
use App\Support\ProfileCompletion;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EditPerfil extends Component
{
    public function mount(NewUserOnboardingService $onboarding): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Sin empleado ni estudiante no habría nada que completar y el usuario quedaría
        // bloqueado: se le crea el perfil de empleado, como al entrar por primera vez con
        // Microsoft. A quien tiene el rol de estudiante no, porque su perfil es otro, ni a
        // quien un administrador le borró el empleado: recrearlo desharía esa decisión.
        if ($onboarding->requiresEmployeeProfile($user)
            && ! $user->hasRole('estudiante')
            && ! Empleado::onlyTrashed()->where('user_id', $user->id)->exists()) {
            Auth::setUser($onboarding->prepareEmployeeProfile($user));
        }
    }

    public function render()
    {
        $user = auth()->user();
        $typeUser = null;

        if ($user->empleado != null) {
            $typeUser = 'Empleado';
        } elseif ($user->estudiante != null) {
            $typeUser = 'Estudiante';
        }

        $profileCompletionRequired = ProfileCompletion::isRequired($user);

        return view('livewire.personal.perfil.edit-perfil', [
            'typeUser' => $typeUser,
            'profileCompletionRequired' => $profileCompletionRequired,
            'faltantes' => $profileCompletionRequired && $typeUser ? ProfileCompletion::missingFields($user) : [],
        ]);
    }
}

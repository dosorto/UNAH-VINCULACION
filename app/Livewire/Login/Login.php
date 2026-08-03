<?php

namespace App\Livewire\Login;

use App\Models\Slide\Slide;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Login extends Component
{
    public function render(): View
    {
        $slides = Slide::where('estado', true)->get();
        $data = ['slides' => $slides];

        if (env('NUEVO_LOGIN') == false) {
            return view('livewire.login.login')->layout('aplicacion.login', $data);
        }

        return view('livewire.login.login')->layout('aplicacion.login2', $data);
    }
}

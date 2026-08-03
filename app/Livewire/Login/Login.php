<?php

namespace App\Livewire\Login;

use App\Models\Slide\Slide;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    protected array $rules = [
        'email' => ['required', 'email'],
        'password' => ['required'],
    ];

    protected array $messages = [
        'email.required' => 'El correo es obligatorio.',
        'email.email' => 'Ingresa un correo válido.',
        'password.required' => 'La contraseña es obligatoria.',
    ];

    public function create(): void
    {
        abort_unless($this->localPasswordLoginEnabled(), 404);

        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            request()->session()->regenerate();
            $this->redirect(route('inicio'));

            return;
        }

        Notification::make()
            ->title('Correo o contraseña incorrectos.')
            ->danger()
            ->send();
    }

    public function localPasswordLoginEnabled(): bool
    {
        return app()->environment('local');
    }

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

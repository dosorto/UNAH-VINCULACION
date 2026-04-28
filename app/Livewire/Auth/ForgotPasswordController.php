<?php

namespace App\Livewire\Auth;

use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ForgotPasswordController extends Component
{
    public string $email = '';

    protected array $rules = [
        'email' => 'required|email|max:255',
    ];

    protected array $messages = [
        'email.required' => 'El correo es obligatorio.',
        'email.email'    => 'Ingresa un correo válido.',
    ];

    public function submit(): void
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            Notification::make()
                ->title('Se ha enviado el mensaje de verificación a tu correo!')
                ->success()
                ->send();

            $this->email = '';
        } else {
            Notification::make()
                ->title('No existe el correo electrónico en el sistema!')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password-controller')->layout('components.layouts.login');
    }
}

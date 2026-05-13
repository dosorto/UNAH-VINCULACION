<?php

namespace App\Livewire\Auth;

use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

class ResetPasswordController extends Component
{
    public string $password = '';
    public string $password_confirmation = '';
    public string $token = '';
    public string $email = '';

    protected array $rules = [
        'password'              => 'required|min:8|confirmed',
        'password_confirmation' => 'required|min:8',
    ];

    protected array $messages = [
        'password.required'  => 'La contraseña es obligatoria.',
        'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
        'password.confirmed' => 'Las contraseñas no coinciden.',
    ];

    public function mount(string $token, Request $request): void
    {
        $this->token = $token;
        $this->email = $request->email ?? '';
    }

    public function submit(): mixed
    {
        $this->validate();

        $status = Password::reset(
            [
                'password'              => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'email'                 => $this->email,
                'token'                 => $this->token,
            ],
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Contraseña restablecida correctamente.');
        }

        Notification::make()
            ->title('No se pudo recuperar la contraseña.')
            ->danger()
            ->send();

        return null;
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password-controller')->layout('components.layouts.login');
    }
}

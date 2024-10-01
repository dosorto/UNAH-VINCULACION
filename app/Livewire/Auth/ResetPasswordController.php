<?php

namespace App\Livewire\Auth;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordValidation;

class ResetPasswordController extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public $token, $email;

    public function mount($token, Request $request)
    {
        // Almacena el token y el email desde la URL y el request
        $this->token = $token;
        $this->email = $request->email; // Obtener el email pasado en la URL

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('password')
                    ->password()
                    ->required()
                    ->label('Contraseña')
                    ->minLength(8) // Mínimo de 8 caracteres
                    // ->dehydrateStateUsing(fn ($state) => bcrypt($state)) // Para encriptar la contraseña
                    ->same('password_confirmation') // Validar que coincida con el campo de confirmación
                    ->rules(['confirmed']), // Reglas de validación de Laravel

                TextInput::make('password_confirmation')
                    ->password()
                    ->required()
                    ->label('Confirmar Contraseña')
                    ->minLength(8),
            ])
            ->statePath('data'); 
    }

    public function submit()
    {
        $data = $this->form->getState();
    
        $status = Password::reset(
            [
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
                'email' => $this->email,
                'token' => $this->token,
            ],
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );
    
        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Contraseña restablecida correctamente.');
        } else {
            Notification::make()
                ->title('No se pudo recuperar la contraseña!')
                ->danger()
                ->send();
        }
    }
    

    public function render(): View
    {
        // dd($this->email);
        return view('livewire.auth.reset-password-controller')->layout('components.layouts.login');
    }
}
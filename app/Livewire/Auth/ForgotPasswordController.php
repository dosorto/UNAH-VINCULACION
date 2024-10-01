<?php

namespace App\Livewire\Auth;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class ForgotPasswordController extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount()
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->required()
                    ->maxLength(255),
                //
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        // Intentar enviar el correo de restablecimiento
        $status = Password::sendResetLink(
            $data
        );

        // dd($status, Password::RESET_LINK_SENT);

        // Retornar respuesta dependiendo del estado
        if ($status === Password::RESET_LINK_SENT) {
            Notification::make()
                ->title('Se ha enviado el mensaje de verificacion a tu correo!')
                ->success()
                ->send();
        } else {

            Notification::make()
                ->title('No existe el correo electronico en el sistema!')
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
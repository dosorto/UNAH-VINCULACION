<?php

namespace App\Livewire\Login;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class Login extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Correo institucional')
                    ->required(),
                TextInput::make('Contraseña')
                    ->revealable()
                    ->password()
                    ->required()
            ])
            ->statePath('data')
            ->model(User::class);
    }

    public function create()
    {
        $data = $this->form->getState();

        if (Auth::attempt(['email' => $data['email'], 'password' => $data['Contraseña']])) {
            return redirect(route('inicio'));
        }

        Notification::make()
            ->title('Correo o contraseña incorrectas!')
            ->danger()
            ->send();
    }

    public function render()
    {
     
        return view('livewire.login.login')->layout('components.layouts.login2');
    }
}

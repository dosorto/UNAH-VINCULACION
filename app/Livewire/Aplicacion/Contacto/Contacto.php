<?php

namespace App\Livewire\Aplicacion\Contacto;

use App\Models\Personal\Contacto as PersonalContacto;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;





class Contacto extends Component implements HasForms
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
                TextInput::make('nombres')
                    ->label('Nombres')
                    ->required()
                    ->maxLength(255),
                TextInput::make('apellidos')
                    ->label('Apellidos')
                    ->required()
                    ->maxLength(255),
                TextInput::make('institucion')
                    ->label('Institucion')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->email()
                    ->maxLength(255),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->required()
                    ->tel()
                    ->maxLength(15),

                Textarea::make('mensaje')
                    ->columnSpanFull()
                    
                    ->rows(5)
                    ->required()
                    ->cols(15)
                
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        PersonalContacto::create($data);
        Notification::make()
            ->title('¡Éxito!')
            ->body('su mensaje ha sido enviado correctamente.')
            ->success()
            ->send();

        $this->js('location.reload();');

        //
    }

    public function render(): View
    {
        return view('livewire.aplicacion.contacto.contacto');
    }
}

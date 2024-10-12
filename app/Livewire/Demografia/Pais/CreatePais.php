<?php

namespace App\Livewire\Demografia\Pais;

use App\Models\Demografia\Pais;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;

class CreatePais extends Component implements HasForms
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


                Section::make('Crear un nuevo Pais')
                    ->description('Crea un nuevo pais con sus datos asociados.')
                    ->schema([
                        TextInput::make('codigo_area')
                            ->numeric()
                            ->required(),
                        TextInput::make('codigo_iso')
                            ->required(),
                        TextInput::make('codigo_iso_numerico')
                            ->numeric()
                            ->required(),
                        TextInput::make('codigo_iso_alpha_2')
                            ->required(),
                        TextInput::make('nombre')

                            ->required(),
                        TextInput::make('gentilicio')

                            ->required(),
                    ])
                    ->columns(2)
            ])
            ->columns(2)
            ->statePath('data')
            ->model(Pais::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $record = Pais::create($data);
        $this->form->model($record)->saveRelationships();

        Notification::make()
            ->title('Exito!')
            ->body('Pais creado correctamente.')
            ->success()
            ->send();
        $this->js('location.reload();');
        // limpiar formulario
        $this->data = [];
    }

    public function render(): View
    {
        return view('livewire.demografia.create-pais')
        ->layout('components.panel.modulos.modulo-demografia');
    }
}

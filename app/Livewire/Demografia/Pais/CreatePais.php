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
                Section::make('Crear un nuevo País')
                    ->description('Crea un nuevo país con sus datos asociados.')
                    ->schema([
                        TextInput::make('codigo_area')
                            ->label('Código de área')
                            ->numeric()
                            ->required(),
                        TextInput::make('codigo_iso')
                            ->label('Código ISO')
                            ->required(),
                        TextInput::make('codigo_iso_numerico')
                            ->label('Código ISO numérico')
                            ->numeric()
                            ->required(),
                        TextInput::make('codigo_iso_alpha_2')
                            ->label('Código ISO alpha 2')
                            ->required(),
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required(),
                        TextInput::make('gentilicio')
                            ->label('Gentilicio')
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
            ->title('¡Éxito!')
            ->body('Pais creado correctamente.')
            ->success()
            ->send();
        //$this->js('location.reload();');
        // limpiar formulario
        $this->data = [];
    }

    public function render(): View
    {
        return view('livewire.demografia.create-pais')
        ->layout('components.panel.modulos.modulo-demografia');
    }
}

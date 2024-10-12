<?php

namespace App\Livewire\Demografia\Ciudad;

use App\Models\Demografia\Ciudad;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use App\Models\Demografia\Municipio;
use Filament\Forms\Components\Section;

class CreateCiudad extends Component implements HasForms
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
                Section::make('Crear una nueva ciudad')
                    ->description('Crea una nueva ciudad con sus datos asociados.')
                    ->schema([
                        Select::make('municipio_id')
                            ->label('Municipio') 
                            ->options(
                                Municipio::all()
                                    ->pluck('nombre', 'id')
                            )
                            ->required(),
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required(), 
                        TextInput::make('codigo_postal')
                            ->label('Código Postal') 
                            ->required(), 
                ])
                ->columns(2)

            ])
            
            ->statePath('data')
            ->model(Ciudad::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $record = Ciudad::create($data);

        $this->form->model($record)->saveRelationships();

        Notification::make()
            ->title('¡Éxito!')
            ->body('Ciudad creada correctamente.')
            ->success()
            ->send();

        //$this->js('location.reload();');
        $this->data = [];
    }

    public function render(): View
    {
        return view('livewire.demografia.ciudad.create-ciudad')
        ->layout('components.panel.modulos.modulo-demografia');
    }
}
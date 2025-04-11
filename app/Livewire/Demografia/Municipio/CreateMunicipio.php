<?php

namespace App\Livewire\Demografia\Municipio;

use App\Models\Demografia\Municipio;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use App\Models\Demografia\Departamento;

class CreateMunicipio extends Component implements HasForms
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
                Section::make('Crear un nuevo municipio')
                    ->description('Crea un nuevo municipio con sus datos asociados.')
                    ->schema([
                        Select::make('departamento_id')
                            ->label('Departamento')
                            ->options(
                                Departamento::all()
                                    ->pluck('nombre', 'id')
                            ),
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required(), 
                        TextInput::make('codigo_municipio')
                            ->label('Código del Municipio')
                            ->required(), 
                ])
                ->columns(2)
                //
            ])
            
            ->statePath('data')
            ->model(Municipio::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $record = Municipio::create($data);

        $this->form->model($record)->saveRelationships();

        Notification::make()
            ->title('¡Éxito!')
            ->body('Municipio creado correctamente.')
            ->success()
            ->send();
        //$this->js('location.reload();');
        // limpiar formulario
        $this->data = [];
    }

    public function render(): View
    {
        return view('livewire.demografia.municipio.create-municipio')
        ;//->layout('components.panel.modulos.modulo-demografia');
    }
}
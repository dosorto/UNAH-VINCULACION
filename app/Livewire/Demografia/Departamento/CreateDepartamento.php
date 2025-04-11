<?php

namespace App\Livewire\Demografia\Departamento;

use App\Models\Demografia\Departamento;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

use Filament\Forms\Components\Select;
use App\Models\Demografia\Pais;

use Filament\Forms\Components\Section;

class CreateDepartamento extends Component implements HasForms
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
                Section::make('Crear un nuevo Departamento')
                    ->description('Crea un nuevo departamento asociado a un país.')
                    ->schema([
                        Select::make('pais_id')
                            ->label('País')
                            ->options(
                                Pais::all()
                                    ->pluck('nombre', 'id')
                            )
                            ->required()
                            ->searchable()
                            ->columnSpanFull(),
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required(),
                        TextInput::make('codigo_departamento')
                            ->label('Código del Departamento')
                            ->required(),
                    ])

                    ->columns(2)
            ])
            ->statePath('data')
            ->model(Departamento::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $record = Departamento::create($data);
        
        $this->form->model($record)->saveRelationships();

        Notification::make()
            ->title('¡Éxito!')
            ->body('Departamento creado correctamente.')
            ->success()
            ->send();

        //$this->js('location.reload();');
        $this->data = [];
    }

    public function render(): View
    {
        return view('livewire.demografia.departamento.create-departamento')
        ;//->layout('components.panel.modulos.modulo-demografia');
    }
}

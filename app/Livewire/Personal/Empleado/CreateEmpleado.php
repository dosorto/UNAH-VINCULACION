<?php

namespace App\Livewire\Personal\Empleado;

use App\Livewire\User\Users;
use App\Models\Personal\Empleado;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use app\Models\User;

class CreateEmpleado extends Component implements HasForms
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
                Section::make('Crear un nuevo empleado')
                    ->description('Crea un nuevo empleado con sus datos asociados.')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('numero_empleado')
                            ->required()
                            ->maxLength(255),
                        Select::make('user_id')
                            ->label('Usuario')
                            ->options(
                                User::all()
                                    ->pluck('email', 'id')
                            )
                            ->required()
                            ->searchable()
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('fecha_contratacion')
                            ->required(),
                        Forms\Components\TextInput::make('salario')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('supervisor')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('jornada')
                            ->required(),
                    ])
                    ->columns(2)
            ])

            ->statePath('data')
            ->model(Empleado::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $record = Empleado::create($data);

        $this->form->model($record)->saveRelationships();

        Notification::make()
            ->title('Exito!')
            ->body('Empleado creado correctamente.')
            ->success()
            ->send();
        $this->js('location.reload();');
        // limpiar formulario
        $this->data = [];
    }

    public function render(): View
    {
        return view('livewire.personal.empleado.create-empleado')
            ->layout('components.panel.modulos.modulo-empleado');
    }
}

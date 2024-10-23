<?php

namespace App\Livewire\Personal\Empleado;

use Filament\Forms;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Personal\Empleado;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\FileUpload;
use App\Models\Personal\FirmaSelloEmpleado;
use Filament\Forms\Concerns\InteractsWithForms;

class EditPerfil extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Empleado $record;

    public function mount(): void
    {
        // dd(Empleado::where('user_id', auth()->user()->id)->first());
        $this->record = Empleado::where('user_id', auth()->user()->id)->first();
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Crear un nuevo empleado')
                ->description('Crea un nuevo empleado con sus datos asociados.')
                ->schema([
                    FileUpload::make('firma.firma')
                    ->label('Firma')
                    ->image() // Para asegurarse de que es una imagen
                    ->nullable(), // Permitir que no sea obligatorio
                    Forms\Components\TextInput::make('nombre_completo')
                    ->required()
                    ->maxLength(255),
                    Forms\Components\TextInput::make('numero_empleado')
                    ->required()
                    ->maxLength(255),
                    Forms\Components\TextInput::make('celular')
                    ->required()
                    ->numeric()
                    ->maxLength(255),
                    Forms\Components\TextInput::make('categoria')
                    ->required()
                    ->maxLength(255),
                    Select::make('campus_id')
                    ->label('Campus')
                    ->relationship('campus', 'nombre_campus') // Define la relación y el campo que quieres mostrar
                    ->required(),
                    Select::make('departamento_academico_id')
                    ->label('Departamento Academico')
                    ->relationship('DepartamentoAcademico', 'nombre') // Define la relación y el campo que quieres mostrar
                    ->required(),
                ])
            ])
            ->statePath('data')
            ->model($this->record);
    }


    public function save(): void
    {
        FirmaSelloEmpleado::where('empleado_id', $this->record->id)
            ->where('tipo', 'firma')
            ->update(['estado' => false]);
        $data = $this->form->getState();
        
        $this->record->update($data);
        FirmaSelloEmpleado::updateOrCreate(
            [
                'empleado_id' => $this->record->id,
                'tipo' => 'firma',
            ],
            [
                'ruta_storage' => $data['firma'],
                'estado' => true, // Nuevo estado activo
            ]
        );
    }

    public function render(): View
    {
        return view('livewire.personal.empleado.edit-perfil');
    }
}

<?php

namespace App\Livewire\Personal\Empleado;

use Filament\Forms;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Personal\Empleado;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Actions\CreateAction;

class EditPerfil extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public Empleado $record;
    public bool $modalVisible = false;

    public function mount(): void
    {
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
                        // Repeater para la firma
                        Repeater::make('firma')
                            ->relationship()
                            ->deletable(false)
                            ->schema([
                                FileUpload::make('ruta_storage')
                                    ->label('Firma')
                                    ->disk('public')
                                    ->directory('firmas_sellos')
                                    ->image()
                                    ->nullable(),
                                Hidden::make('tipo')
                                    ->default('firma'),
                            ])
                            ->minItems(0)
                            ->maxItems(1)
                            ->defaultItems(1)
                            ->columns(1),

                        // Repeater para el sello
                        Repeater::make('sello')
                            ->relationship()
                            ->deletable(false)
                            ->schema([
                                FileUpload::make('ruta_storage')
                                    ->label('Sello')
                                    ->image()
                                    ->nullable(),
                                Hidden::make('tipo')
                                    ->default('sello'),
                            ])
                            ->minItems(0)
                            ->maxItems(1)
                            ->defaultItems(1)
                            ->columns(1),

                        // Otros campos del formulario
                        TextInput::make('nombre_completo')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('numero_empleado')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('celular')
                            ->required()
                            ->numeric()
                            ->maxLength(255),
                        TextInput::make('categoria')
                            ->required()
                            ->maxLength(255),
                        Select::make('campus_id')
                            ->label('Campus')
                            ->relationship('campus', 'nombre_campus')
                            ->required(),
                        Select::make('departamento_academico_id')
                            ->label('Departamento Academico')
                            ->relationship('DepartamentoAcademico', 'nombre')
                            ->required(),
                    ])
            ])
            ->statePath('data')
            ->model($this->record);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $this->record->update($data);
    }

    public function render(): View
    {
        return view('livewire.personal.empleado.edit-perfil', [
            'record' => $this->record,
        ]);
    }
}

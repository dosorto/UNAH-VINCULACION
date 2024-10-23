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
// use Filament\Pages\Actions\CreateAction;
// use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Contracts\HasActions;
use App\Models\Personal\FirmaSelloEmpleado;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;

class EditPerfil extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public ?array $data = [];
    public ?array $data_firma = [];
    public Empleado $record;

    public function mount(): void
    {
        $this->record = Empleado::where('user_id', auth()->user()->id)->first();
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Editar Oerfil de Empleado')
                    ->description('Editar los datos de empleado Asociado.')
                    ->schema([
                        // Section para la firma
                        Section::make('Firma')
                            ->description('Visualizar o agregar una nueva Firma.')
                            ->headerActions([
                                Action::make('create')
                                    ->label('Crear Nueva Firma')
                                    ->form([
                                        // ...
                                        Hidden::make('empleado_id')
                                            ->default($this->record->id),
                                        FileUpload::make('ruta_storage')
                                            ->label('Firma')
                                            ->disk('public')
                                            ->directory('firmas_sellos')
                                            ->image()
                                            ->required(),
                                        Hidden::make('tipo')
                                            ->default('firma'),
                                    ])
                                    ->action(function (array $data) {
                                        // dd($data);
                                        FirmaSelloEmpleado::create($data);
                                        $this->js('window.location.reload()');
                                    })
                            ])
                            ->schema([
                                // ...
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
                            ]),

                        // Section para el sello
                        Section::make('Sello')
                            ->description('Visualizar o agregar una nueva Firma.')
                            ->headerActions([
                                Action::make('create')
                                    ->label('Crear Nuevo Sello')
                                    ->form([
                                        // ...
                                        Hidden::make('empleado_id')
                                            ->default($this->record->id),
                                        FileUpload::make('ruta_storage')
                                            ->label('Firma')
                                            ->disk('public')
                                            ->directory('firmas_sellos')
                                            ->image()
                                            ->required(),
                                        Hidden::make('tipo')
                                            ->default('sello'),
                                    ])
                                    ->action(function (array $data) {
                                        // dd($data);
                                        FirmaSelloEmpleado::create($data);
                                        $this->js('window.location.reload()');
                                    })
                            ])
                            ->schema([
                                // ...
                                Repeater::make('sello')
                                    ->relationship()
                                    ->deletable(false)
                                    ->schema([
                                        FileUpload::make('ruta_storage')
                                            ->label('Sello')
                                            ->disk('public')
                                            ->directory('firmas_sellos')
                                            ->image()
                                            ->nullable(),
                                        Hidden::make('tipo')
                                            ->default('sello'),
                                    ])
                                    ->minItems(0)
                                    ->maxItems(1)
                                    ->defaultItems(1)
                                    ->columns(1),
                            ]),

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

    public function createAction(): Action
    {
        return Action::make('create')
            ->label('Crear Nueva Firma')
            ->form([
                // ...
                FileUpload::make('ruta_storage')
                    ->label('Firma')
                    ->disk('public')
                    ->directory('firmas_sellos')
                    ->image()
                    ->nullable(),
                Hidden::make('tipo')
                    ->default('firma'),
            ])
            // ...
            ->action(function (array $arguments) {
                
            });
    }

    public function Action(): Action
    {
        return Action::make('create')
            ->label('Crear Nuevo Sello')
            ->form([
                // ...
                FileUpload::make('ruta_storage')
                    ->label('Sello')
                    ->disk('public')
                    ->directory('firmas_sellos')
                    ->image()
                    ->nullable(),
                Hidden::make('tipo')
                    ->default('sello'),
            ])
            // ...
            ->action(function (array $arguments) {
                
            });
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

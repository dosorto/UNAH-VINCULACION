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
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Contracts\HasActions;
use App\Models\Personal\FirmaSelloEmpleado;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
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
        $this->record = Empleado::firstOrCreate(
            ['user_id' => auth()->user()->id],
            ['user_id' => auth()->user()->id]
        );
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Editar Perfil de Empleado')
                    ->description('Editar los datos de Empleado Asociado.')
                    ->schema([
                        // Otros campos del formulario
                        TextInput::make('nombre_completo')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('numero_empleado')
                            ->label('Número de Empleado')
                            ->required()
                            ->numeric()
                            ->maxLength(255),
                        TextInput::make('celular')
                            ->required()
                            ->numeric()
                            ->maxLength(255),
                        Select::make('categoria_id')
                            ->label('Categoría')
                            ->relationship('categoria', 'nombre')
                            ->required(),
                        Select::make('centro_facultad_id')
                            ->label('Campus')
                            ->relationship('centro_facultad', 'nombre')
                            ->required(),
                        Select::make('departamento_academico_id')
                            ->label('Departamento Académico')
                            ->relationship('DepartamentoAcademico', 'nombre')
                            ->required(),

                        // Section para la firma
                        Section::make('Firma')
                            ->description('Visualizar o agregar una nueva Firma.')
                            ->headerActions([
                                Action::make('create')
                                    ->label('Crear Nueva Firma')
                                    ->icon('heroicon-c-arrow-up-on-square')
                                    ->form([
                                        // ...
                                        Hidden::make('empleado_id')
                                            ->default($this->record->id),
                                        FileUpload::make('ruta_storage')
                                            ->label('Firma')
                                            ->disk('public')
                                            ->directory('images/firmas')
                                            ->image()
                                            ->required(),
                                        Hidden::make('tipo')
                                            ->default('firma'),
                                    ])
                                    ->action(function (array $data) {
                                        // dd($data);
                                        FirmaSelloEmpleado::create($data);
                                        $this->mount();
                                    })
                            ])
                            ->schema([
                                // ...
                                Repeater::make('firma')
                                    ->label('')
                                    ->relationship()
                                    ->deletable(false)
                                    ->schema([
                                        FileUpload::make('ruta_storage')
                                            ->label('')
                                            ->disk('public')
                                            ->directory('images/firmas')
                                            ->image()
                                            ->disabled()
                                            ->nullable(),
                                        Hidden::make('tipo')
                                            ->default('firma'),
                                    ])
                                    ->minItems(0)
                                    ->maxItems(1)
                                    ->defaultItems(1)
                                    ->addable(false)
                                    ->columns(1),
                            ]),

                        // Section para el sello
                        Section::make('Sello')
                            ->description('Visualizar o agregar un nuevo Sello.')
                            ->headerActions([
                                Action::make('create')
                                    ->label('Crear Nuevo Sello')
                                    ->icon('heroicon-c-arrow-up-on-square')
                                    ->form([
                                        // ...
                                        Hidden::make('empleado_id')
                                            ->default($this->record->id),
                                        FileUpload::make('ruta_storage')
                                            ->label('Sello')
                                            ->disk('public')
                                            ->directory('images/firmas')
                                            ->image()
                                            ->required(),
                                        Hidden::make('tipo')
                                            ->default('sello'),
                                    ])
                                    ->action(function (array $data) {
                                        // dd($data);
                                        FirmaSelloEmpleado::create($data);
                                        $this->mount();
                                    })
                            ])
                            ->schema([
                                // ...
                                Repeater::make('sello')
                                    ->label('')
                                    ->relationship()
                                    ->deletable(false)
                                    ->schema([
                                        FileUpload::make('ruta_storage')
                                            ->label('')
                                            ->disk('public')
                                            ->directory('images/firmas')
                                            ->image()
                                            ->disabled()
                                            ->nullable(),
                                        Hidden::make('tipo')
                                            ->default('sello'),
                                    ])
                                    ->minItems(0)
                                    ->maxItems(1)
                                    ->defaultItems(1)
                                    ->addable(false)
                                    ->columns(1),
                            ]),
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

    public function save()
    {
        $data = $this->form->getState();
        
       // dd($data);
        // validar que el docente tenga almenos una firma y un sello
       

        
        $this->record->update($data);



        $this->record->user->assignRole('docente')->save();
        Notification::make()
            ->title('Exito!')
            ->body('Perfil de ' . $data['nombre_completo'] . ' actualizado correctamente.')
            ->success()
            ->send();
        return redirect()->route('inicio');
    }

    public function render(): View
    {
        return view('livewire.personal.empleado.edit-perfil', [
            'record' => $this->record,
        ]);
    }
}

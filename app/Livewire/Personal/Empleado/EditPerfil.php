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
use Filament\Forms\Get;
use Filament\Forms\Set;

// importar modelo role de spatie
use Spatie\Permission\Models\Role;

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
                            ->default($this->record->user->name)
                            ->maxLength(255),
                        TextInput::make('numero_empleado')
                            ->label('Número de Empleado')
                            ->unique('empleado', 'numero_empleado', ignoreRecord: true)
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
                            ->label('Facultades o Centros')
                            ->searchable()
                            ->live()
                            ->relationship(name: 'centro_facultad', titleAttribute: 'nombre')
                            ->afterStateUpdated(function (Set $set) {
                                $set('departamento_academico_id', null);
                            })
                            ->required()
                            ->preload(),



                        Select::make('departamento_academico_id')
                            ->label('Departamentos Académicos')
                            ->searchable()
                            ->relationship(
                                name: 'departamento_academico',
                                titleAttribute: 'nombre',
                                modifyQueryUsing: fn($query, Get $get) => $query->where('centro_facultad_id', $get('centro_facultad_id'))
                            )
                            ->visible(fn(Get $get) => !empty($get('centro_facultad_id')))
                            ->live()
                            ->required()
                            ->preload(),
                    ])
                    ->visible($this->record->firma()->exists()),


                // Section para la firma
                Section::make('Firma (Requerido)')
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
                            ->minItems(1)
                            ->maxItems(1)
                            ->defaultItems(1)
                            ->addable(false)
                            ->columns(1),
                    ]),

                // Section para el sello
                Section::make('Sello (opcional)')
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
            ->statePath('data')
            ->model($this->record);
    }



    public function save()
    {
        $data = $this->form->getState();

        // dd($data);
        // validar que el docente tenga almenos una firma y un sello



        $this->record->update($data);
        $this->record->user->assignRole('docente')->save();
        $this->record->user->active_role_id =  Role::where('name', 'docente')->first()->id;
        $this->record->user->save();
        Notification::make()
            ->title('Exito!')
            ->body('Perfil  actualizado correctamente.')
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

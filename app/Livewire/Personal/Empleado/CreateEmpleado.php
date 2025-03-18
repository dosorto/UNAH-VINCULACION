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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
// use Filament\Pages\Actions\CreateAction;
// use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Contracts\HasActions;
use App\Models\Personal\FirmaSelloEmpleado;
use Filament\Forms\Components\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\CheckboxList;
// importar modelo role de spatie
use Spatie\Permission\Models\Role;

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



                Section::make('user')
                    ->schema([
                        TextInput::make('usuario.name')
                            ->label('Nombre de Usuario')
                            ->required()
                            ->unique('users', 'name')
                            ->maxLength(255),
                        TextInput::make('usuario.email')
                            ->label('Correo Electrónico')
                            ->required()
                            ->unique('users', 'email')
                            ->email()
                            ->maxLength(255),

                    ])
                    ->columnSpanFull(),



                Section::make('Empleado')
                    ->schema([
                        TextInput::make('empleado.nombre_completo')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('empleado.numero_empleado')
                            ->label('Número de Empleado')
                            ->unique('empleado', 'numero_empleado')
                            ->required()
                            ->numeric()
                            ->maxLength(255),
                        TextInput::make('empleado.celular')
                            ->required()
                            ->numeric()
                            ->maxLength(255),
                        Select::make('empleado.categoria_id')
                            ->label('Categoría')
                            ->relationship('categoria', 'nombre')
                            ->required(),
                        Select::make('empleado.centro_facultad_id')
                            ->label('Facultades o Centros')
                            ->searchable()
                            ->live()
                            ->relationship(name: 'centro_facultad', titleAttribute: 'nombre')
                            ->afterStateUpdated(function (Set $set) {
                                $set('empleado.empleado.departamento_academico_id', null);
                            })
                            ->required()
                            ->preload(),
                        Select::make('empleado.departamento_academico_id')
                            ->label('Departamentos Académicos')
                            ->searchable()
                            ->relationship(
                                name: 'departamento_academico',
                                titleAttribute: 'nombre',
                                modifyQueryUsing: fn($query, Get $get) => $query->where('centro_facultad_id', $get('empleado.centro_facultad_id'))
                            )
                            ->visible(fn(Get $get) => !empty($get('empleado.centro_facultad_id')))
                            ->live()
                            ->required()
                            ->preload(),

                    ])
                    ->columns(2),
                Section::make('roles')
                    ->schema([
                        CheckboxList::make('roles.roles')
                            ->label('Roles')
                        
                            ->columns(3)
                            ->options(Role::all()->pluck('name', 'name')->toArray())

                    ])
                    
                    ->columnSpanFull(),
            ])

            ->statePath('data')
            ->model(Empleado::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $user = User::create($data['usuario']);
        $user->assignRole($data['roles']['roles']);
        $record = $user->empleado()->create($data['empleado']);
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

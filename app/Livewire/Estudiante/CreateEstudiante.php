<?php

namespace App\Livewire\Estudiante;

use App\Models\Estudiante\Estudiante;
use App\Models\Proyecto\Proyecto;
use App\Models\Estudiante\EstudianteProyecto;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\User\Users;
use app\Models\User;
// importar modelo role de spatie
use Spatie\Permission\Models\Role;
use Filament\Forms\Components\Radio;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Get;
use Filament\Forms\Set;

class CreateEstudiante extends Component implements HasForms
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
                Section::make('Usuario Estudiante')
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

                    Section::make('Datos de Estudiante')
                    ->schema([
                        TextInput::make('estudiante.nombre')
                            ->label('Nombres')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('estudiante.apellido')
                            ->label('Apellidos')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('estudiante.cuenta')
                            ->label('Número de Cuenta')
                            ->unique('estudiante', 'cuenta')
                            ->required()
                            ->numeric()
                            ->maxLength(255),

                        Select::make('estudiante.centro_facultad_id')
                            ->label('Facultades o Centros')
                            ->searchable()
                            ->live()
                            ->relationship(name: 'centro_facultad', titleAttribute: 'nombre')
                            ->afterStateUpdated(function (Set $set) {
                                $set('estudiante.estudiante.departamento_academico_id', null);
                            })
                            ->live()
                            ->required()
                            ->preload(),

                        Select::make('estudiante.departamento_academico_id')
                            ->label('Departamentos Académicos')
                            ->searchable()
                            ->relationship(
                                name: 'departamento_academico',
                                titleAttribute: 'nombre',
                                modifyQueryUsing: fn($query, Get $get) => $query->where('centro_facultad_id', $get('estudiante.centro_facultad_id'))
                            )
                            ->visible(fn(Get $get) => !empty($get('estudiante.centro_facultad_id')))
                            ->live()
                            ->required()
                            ->preload(),

                    ])
                    ->columns(2),

                    Section::make('Asignar Proyecto a Estudiante')
                    ->schema([
                       /* Select::make('estudiante_id')
                            ->label('Estudiante')
                            ->relationship('estudiante', 'nombre')
                            ->searchable()
                            ->preload()
                            ->required(),*/

                        Select::make('estudiante.proyecto_id')
                            ->label('Proyecto')
                            ->searchable()
                            ->live()
                            ->relationship(name: 'proyecto', titleAttribute:'nombre_proyecto')
                            ->afterStateUpdated(function (Set $set) {
                                $set('estudiante.estudiante.proyecto_id', null);
                            })
                            ->live()
                            ->preload(),

                        Radio::make('tipo_participacion_estudiante')
                            ->label('Tipo de Participación')
                            ->options([
                                'Servicio Social Universitario' => 'Servicio Social Universitario',
                                'Practica Profesional' => 'Práctica Profesional',
                                'Voluntariado' => 'Voluntariado',
                                'Practica de Clase' => 'Práctica de Clase',
                            ])
                            ->inline(),
                        ])    
                        ->columnSpanFull(),                   

                Section::make('Roles')
                    ->schema([
                        CheckboxList::make('roles.roles')
                            ->label('Roles')
                        
                            ->columns(3)
                            ->options(Role::all()->pluck('name', 'name')->toArray())

                    ])
                    
                    ->columnSpanFull(),
                    ])
                

            ->statePath('data')
            ->model(Estudiante::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();
    
        // Crear usuario primero
        $user = User::create($data['usuario']);
        $user->assignRole($data['roles']['roles']);
        $user->active_role_id = $user->roles->first()->id;
        $user->save();
    
        // Crear el estudiante vinculado al usuario
        $estudianteData = $data['estudiante'];
        $estudianteData['user_id'] = $user->id; // Asociar el usuario con el estudiante
    
        $record = Estudiante::create($estudianteData);
    
        // Guardar relaciones adicionales si es necesario
        $this->form->model($record)->saveRelationships();
    
        // Notificación de éxito
        Notification::make()
            ->title('¡Éxito!')
            ->body('Estudiante creado correctamente.')
            ->success()
            ->send();
    
        // Limpiar el formulario
        $this->data = [];
    }
    
   
    public function render(): View
    {
        return view('livewire.estudiante.create-estudiante');
    }
}

<?php

namespace App\Livewire\Estudiante;

use App\Models\Estudiante\Estudiante;
use App\Models\Proyecto\Proyecto;
use App\Models\Estudiante\EstudianteProyecto;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\Carrera;
use App\Models\User\Users;
use Spatie\Permission\Models\Role;
use Filament\Forms\Components\Radio;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\User;

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
                        Select::make('carrera_id')
                            ->label('Carrera')
                            ->searchable()
                            ->required()
                            ->options(Carrera::all()->pluck('nombre', 'id'))
                            ->preload(),
                    ])
                    ->columns(2),

                Section::make('Asignar Proyecto a Estudiante')
                    ->schema([
                        Select::make('estudiante.proyecto_id')
                            ->label('Proyecto')
                            ->searchable()
                            ->live()
                            ->relationship(name: 'proyecto', titleAttribute:'nombre_proyecto')
                            ->afterStateUpdated(function (Set $set) {
                                $set('estudiante.estudiante.proyecto_id', null);
                            })
                            ->preload(),

                        Radio::make('tipo_participacion_estudiante')
                            ->label('Tipo de Participación')
                            ->options([
                                'Servicio Social Universitario' => 'Servicio Social Universitario',
                                'Práctica Profesional' => 'Práctica Profesional',
                                'Voluntariado' => 'Voluntariado',
                                'Práctica de Clase' => 'Práctica de Clase',
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

        $user = User::create($data['usuario']);
        $user->assignRole($data['roles']['roles']);
        $user->active_role_id = $user->roles->first()->id;
        $user->save();

        $estudianteData = $data['estudiante'];
        $estudianteData['user_id'] = $user->id;

        $estudiante = Estudiante::create($estudianteData);

        $this->form->model($estudiante)->saveRelationships();

        if (!empty($data['estudiante']['proyecto_id']) && !empty($data['tipo_participacion_estudiante'])) {
            EstudianteProyecto::create([
                'estudiante_id' => $estudiante->id,
                'proyecto_id' => $data['estudiante']['proyecto_id'],
                'tipo_participacion_estudiante' => $data['tipo_participacion_estudiante'], // se guarda como string
            ]);
        }

        Notification::make()
            ->title('¡Éxito!')
            ->body('Estudiante y proyecto asignado correctamente.')
            ->success()
            ->send();

        $this->data = [];
    }

    public function render(): View
    {
        return view('livewire.estudiante.create-estudiante');
    }
}

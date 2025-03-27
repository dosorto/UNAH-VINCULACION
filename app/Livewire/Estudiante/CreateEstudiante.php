<?php

namespace App\Livewire\Estudiante;

use App\Models\Estudiante\Estudiante;
use App\Models\Proyecto\Proyecto;
use App\Models\Estudiante\EstudianteProyecto;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
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
                Section::make('Usuario')
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

                Section::make('Estudiante')
                    ->schema([
                        TextInput::make('nombre')
                            ->label('Nombres')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('apellido')
                            ->label('Apellidos')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('cuenta')
                            ->label('Número de Cuenta')
                            ->unique('estudiantes', 'cuenta')
                            ->required()
                            ->numeric()
                            ->maxLength(15),

                        Select::make('centro_facultad_id')
                            ->label('Facultades o Centros')
                            ->searchable()
                            ->live()
                            ->relationship(name: 'centroFacultad', titleAttribute: 'nombre')
                            ->afterStateUpdated(fn(Set $set) => $set('departamento_academico_id', null))
                            ->required()
                            ->preload(),

                        Select::make('departamento_academico_id')
                            ->label('Departamentos Académicos')
                            ->searchable()
                            ->relationship(
                                name: 'departamentoAcademico',
                                titleAttribute: 'nombre',
                                modifyQueryUsing: fn($query, Get $get) => $query->where('centro_facultad_id', $get('centro_facultad_id'))
                            )
                            ->visible(fn(Get $get) => !empty($get('centro_facultad_id')))
                            ->live()
                            ->required()
                            ->preload(),
                    ])
                    ->columnSpanFull(),

                Section::make('Asignar Estudiante a Proyecto')
                    ->description('Asocia este estudiante a uno o varios proyectos.')
                    ->schema([
                        Repeater::make('proyectos')
                            ->relationship('estudianteProyectos')
                            ->schema([
                                Select::make('proyecto_id')
                                    ->label('Proyecto')
                                    ->options(Proyecto::pluck('nombre', 'id')->toArray())
                                    ->required(),

                                Select::make('tipo_participacion_estudiante')
                                    ->label('Tipo de Participación')
                                    ->options([
                                        'Servicio Social Universitario' => 'Servicio Social Universitario',
                                        'Practica Profesional' => 'Práctica Profesional',
                                        'Voluntariado' => 'Voluntariado',
                                        'Practica de Clase' => 'Práctica de Clase',
                                    ])
                                    ->required(),
                            ])
                            ->columns(2)
                            ->minItems(1)
                            ->maxItems(5)
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->statePath('data')
            ->model(Estudiante::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        // Crear estudiante
        $estudiante = Estudiante::create([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'cuenta' => $data['cuenta'],
            'centro_facultad_id' => $data['centro_facultad_id'],
            'departamento_academico_id' => $data['departamento_academico_id'],
        ]);

        // Asignar proyectos
        $proyectos = collect($data['proyectos'])->mapWithKeys(fn ($p) => [
            $p['proyecto_id'] => ['tipo_participacion_estudiante' => $p['tipo_participacion_estudiante']]
        ]);

        $estudiante->proyectos()->sync($proyectos);

        Notification::make()
            ->title('¡Éxito!')
            ->body('El estudiante fue registrado y asignado a los proyectos correctamente.')
            ->success()
            ->send();

        // Limpiar formulario
        $this->data = [];
        $this->dispatch('estudianteCreado');
    }

    public function render(): View
    {
        return view('livewire.estudiante.create-estudiante');
    }
}

<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use Filament\Forms;
use App\Models\User;
use Filament\Forms\Get;

use App\Models\Personal\Empleado;

use App\Models\Estudiante\Estudiante;
use App\Models\UnidadAcademica\FacultadCentro;
use Faker\Provider\ar_EG\Text;
use Filament\Forms\Components\Hidden;

use Filament\Forms\Components\Select;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Set;


class PrimeraParte
{
    public static function form(): array
    {
        return [
            TextInput::make('nombre_proyecto')
                ->label('Nombre del proyecto')
                ->minLength(2)
                ->maxLength(255)
                ->columnSpanFull()
                ->required()
                ->maxLength(255),
            Select::make('modalidad_id')
                ->label('Modalidad')
                ->columnSpanFull()
                ->relationship(name: 'modalidad', titleAttribute: 'nombre')
                ->required()
                ->preload(),
            Select::make('facultades_centros')
                ->label('Facultades o Centros')
                ->searchable()
                ->multiple()
                ->live()
                ->relationship(name: 'facultades_centros', titleAttribute: 'nombre')
                ->afterStateUpdated(function (Set $set) {
                    $set('departamentos_academicos', null);
                })
                ->required()
                ->preload(),
            Select::make('departamentos_academicos')
                ->label('Departamentos Académicos')
                ->searchable()
                ->multiple()
                ->relationship(
                    name: 'departamentos_academicos',
                    titleAttribute: 'nombre',
                    modifyQueryUsing: fn($query, Get $get) => $query->whereIn('centro_facultad_id', $get('facultades_centros'))
                )
                ->visible(fn(Get $get) => !empty($get('facultades_centros')))
                ->live()
                ->required()
                ->preload(),
            Repeater::make('coordinador_proyecto')
                ->label('Coordinador')
                ->schema([
                    TextInput::make('nombre_completo')
                        ->minLength(2)
                        ->maxLength(255)
                        ->label('')
                        ->default(
                            fn() => optional(Empleado::where('user_id', auth()->user()->id)->first())
                                ->nombre_completo
                        )
                        ->disabled(),
                    Hidden::make('rol')
                        ->default('Coordinador'),
                    Hidden::make('empleado_id')
                        ->default(fn() => optional(Empleado::where('user_id', auth()->id())->first())->id),

                ])
                ->columnSpanFull()
                ->relationship()
                ->minItems(1)
                ->maxItems(1)
                ->deletable(false)
                ->defaultItems(1),
            Repeater::make('empleado_proyecto')
                ->label('Integrantes ')
                ->schema([
                    Select::make('empleado_id')
                        ->label('Integrante')
                        ->searchable(['nombre_completo', 'numero_empleado'])
                        ->relationship(
                            name: 'empleado',
                            titleAttribute: 'nombre_completo',
                            // QUITAR EL ID DEL USUARIO LOGUEADO DE LA LISTA DE EMPLEADOS
                            modifyQueryUsing: fn(Builder $query) =>  $query->where('user_id', '!=', auth()->id())
                        )

                        ->createOptionForm([

                            // campus o facultad del empleado

                            Select::make('centro_facultad_id')
                                ->label('Facultad o Centro')
                                ->searchable()
                                ->relationship(name: 'centro_facultad', titleAttribute: 'nombre')
                                ->required()
                                ->afterStateUpdated(function (?string $state, ?string $old, Set $set) {
                                    $set('departamento_academico_id', null);
                                })
                                ->live()
                                ->preload(),

                            // departamento academico del empleado

                            Select::make('departamento_academico_id')
                                ->label('Departamento Académico')
                                ->searchable()
                                ->live()
                                ->preload()
                                ->visible(fn(Get $get): bool => !is_null($get('centro_facultad_id')))
                                ->relationship(
                                    name: 'departamento_academico',
                                    titleAttribute: 'nombre',
                                    modifyQueryUsing: fn($query, Get $get) => $query->where('centro_facultad_id', $get('centro_facultad_id'))
                                )
                                ->required(),



                            Forms\Components\TextInput::make('nombre_completo')
                                ->minLength(2)
                                ->maxLength(255)
                                ->label('Nombre completo')
                                ->required(),

                            Forms\Components\TextInput::make('numero_empleado')
                                ->minLength(2)
                                ->maxLength(255)
                                ->label('Número de empleado')
                                ->unique('empleado', 'numero_empleado')
                                ->required(),

                            Select::make('categoria_id')
                                ->label('Categoría')
                                ->relationship(name: 'categoria', titleAttribute: 'nombre')
                                ->required()
                                ->preload(),

                            Forms\Components\TextInput::make('email')
                                ->minLength(2)
                                ->maxLength(255)
                                ->label('Correo electrónico ')
                                ->required()
                                ->email()
                                ->unique('users', 'email')
                                ->email(), //->required(),

                            Forms\Components\TextInput::make('celular')
                                ->minLength(2)
                                ->maxLength(255)
                                ->label('Celular')
                                ->required(),

                        ])
                        ->required()
                        ->createOptionUsing(function (array $data) {
                            $user = User::create([
                                'email' => $data['email'],
                                'name' => $data['nombre_completo']
                            ]);
                            $user->empleado()->create([
                                'user_id' => $user->id,
                                'nombre_completo' => $data['nombre_completo'],
                                'numero_empleado' => $data['numero_empleado'],
                                'celular' => $data['celular'],
                                'categoria_id' => $data['categoria_id'],
                                'departamento_academico_id' => $data['departamento_academico_id'],

                            ]);
                        }),

                    TextInput::make('rol')
                        ->label('Rol')
                        ->disabled()
                        ->required()
                        ->default('Integrante'),
                    Hidden::make('rol')
                        ->default('Integrante'),
                    //->required,

                ])
                ->relationship()
                ->columnSpanFull()
                ->defaultItems(0)
                ->itemLabel('Empleado')
                ->addActionLabel('Agregar empleado')
                ->grid(2),
            Repeater::make('estudiante_proyecto')
                ->schema([
                    Select::make('estudiante_id')
                        ->label('Estudiante')
                        ->required()
                        ->searchable(['cuenta'])
                        ->relationship(
                            name: 'estudiante',
                            titleAttribute: 'cuenta'
                        )
                        ->createOptionForm([
                            TextInput::make('email')
                                ->label('Correo electrónico Académico del Estudiante')
                                ->required()
                                ->unique('users', 'email')
                                ->email()
                                ->required(),
                            TextInput::make('nombre')
                                ->label('Nombre ')
                                ->required(),
                            TextInput::make('apellido')
                                ->label('Apellidos del Estudiante')
                                ->required(),
                            Select::make('centro_facultad_id')
                                ->label('Facultad o Centro')
                                ->searchable()
                                ->options(
                                    FacultadCentro::all()->pluck('nombre', 'id')
                                )
                                ->preload(),
                            TextInput::make('numero_cuenta')
                                ->maxLength(255)
                                ->label('Número de cuenta del Estudiante')
                                ->required()
                                ->unique('estudiante', 'cuenta')
                                ->numeric()
                                ->required()

                        ])
                        ->required()
                        ->createOptionUsing(function (array $data) {
                            $user = User::create([
                                'email' => $data['email'],
                                'name' => $data['nombre'] . ' ' . $data['apellido']
                            ]);
                            $user->estudiante()->create([
                                'user_id' => $user->id,
                                'correo' => $data['email'],
                                'cuenta' => $data['numero_cuenta'],
                                'centro_facultad_id' => $data['centro_facultad_id'],


                            ]);
                        })
                        ->required(),
                    Select::make('tipo_participacion_estudiante')
                        ->label('Tipo de participación')
                        ->required()
                        ->label('Tipo de participación')
                        ->options([
                            'Servicio Social Universitario' => 'Servicio Social Universitario',
                            'Practica Profesional' => 'Practica Profesional',
                            'Voluntariado' => 'Voluntariado',
                            'Practica de Clase' => 'Practica de Clase',
                        ])
                        ->required(),
                ])
                ->label('Estudiantes')
                ->relationship()
                ->defaultItems(0)
                ->columnSpanFull()
                ->grid(2)
                ->addActionLabel('Agregar estudiante'),
            // actividades
        ];
    }
}

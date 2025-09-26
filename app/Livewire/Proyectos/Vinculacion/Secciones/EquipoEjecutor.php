<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioDocente;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioEstudiante;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioIntegranteInternacional;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioAsignatura;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioPeriodoAcademico;

use Filament\Forms;
use App\Models\User;
use Filament\Forms\Get;

use App\Models\Personal\Empleado;
use App\Models\Proyecto\IntegranteInternacional;

use App\Models\Estudiante\Estudiante;
use App\Models\UnidadAcademica\FacultadCentro;
use Faker\Provider\ar_EG\Text;
use Filament\Forms\Components\Hidden;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Set;


class EquipoEjecutor
{
    public static function form(): array
    {
        return [
            Repeater::make('coordinador_proyecto')
                ->label('Coordinador')
                ->schema([
                    Select::make('empleado_id')
                    ->label('')
                    ->required()
                    ->searchable(['nombre_completo', 'numero_empleado'])
                    ->relationship(name: 'empleado', titleAttribute: 'nombre_completo')
                    ->default(fn() => optional(Empleado::where('user_id', auth()->id())->first())->id)
                        ->disabled(),
                    Hidden::make('rol')
                        ->default('Coordinador'),
                    Hidden::make('empleado_id')

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
                        ->distinct()
                        ->searchable(['nombre_completo', 'numero_empleado'])
                        ->relationship(
                            name: 'empleado',
                            titleAttribute: 'nombre_completo',
                            // QUITAR EL ID DEL USUARIO LOGUEADO DE LA LISTA DE EMPLEADOS
                            modifyQueryUsing: fn(Builder $query) =>  $query->where('user_id', '!=', auth()->id())
                        )

                        ->createOptionForm(
                            FormularioDocente::form()
                        )
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
                                'sexo' => $data['sexo'],
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
                        ->searchable(['cuenta', 'nombre', 'apellido'])
                        ->relationship(
                            name: 'estudiante',
                            titleAttribute: 'nombre'
                        )
                        ->createOptionForm(
                            FormularioEstudiante::form()
                        )
                        ->required()
                        ->createOptionUsing(function (array $data) {
                            $user = User::create([
                                'email' => $data['email'],
                                'name' => $data['nombre'] . ' ' . $data['apellido']
                            ]);
                            $user->estudiante()->create([
                                'user_id' => $user->id,
                                'nombre' => $data['nombre'],
                                'apellido' => $data['apellido'],
                                'cuenta' => $data['cuenta'],
                                'sexo' => $data['sexo'],
                                'centro_facultad_id' => $data['centro_facultad_id'],
                                'carrera_id' => $data['carrera_id'],


                            ]);
                        })
                        ->required(),
                    Select::make('tipo_participacion_estudiante')
                        ->label('Tipo de participación')
                        ->required()
                        ->live()
                        ->options([
                            //'Practica Profesional' => 'Práctica Profesional',
                            'Servicio Social o PPS' => 'Servicio Social o PPS',
                            'Voluntariado' => 'Voluntariado',
                            'Practica Asignatura' => 'Práctica Asignatura',
                        ])
                        ->required(),
                    Select::make('asignatura_id')
                        ->label('Asignatura')
                        ->searchable(['codigo', 'nombre'])
                        ->relationship(
                            name: 'asignatura',
                            titleAttribute: 'nombre'
                        )
                        ->visible(fn ($get) => $get('tipo_participacion_estudiante') === 'Practica Asignatura')
                        ->createOptionForm(
                            FormularioAsignatura::form()
                        ),
                    Select::make('periodo_academico_id')
                        ->label('Periodo Académico')
                        ->required()
                        ->live()
                        ->options([
                            'Primer Periodo' => 'Primer Periodo',
                            'Segundo Periodo' => 'Segundo Periodo',
                            'Tercer Periodo' => 'Tercer Periodo',
                            'Primer Semestre' => 'Primer Semestre',
                            'Segundo Semestre' => 'Segundo Semestre',
                        ])
                        ->visible(fn ($get) => $get('tipo_participacion_estudiante') === 'Practica Asignatura'),
           
                ])
                ->label('Estudiantes')
                ->relationship()
                ->defaultItems(0)
                ->columnSpanFull()
                ->grid(2)
                ->addActionLabel('Agregar estudiante'),

            Repeater::make('integrante_internacional_proyecto')
                ->label('Integrantes de cooperación internacional')
                ->schema([
                    Select::make('integrante_internacional_id')
                        ->label('Integrante Internacional')
                        ->distinct()
                        ->searchable(['nombre_completo', 'pais', 'institucion'])
                        ->relationship(
                            name: 'integranteInternacional',
                            titleAttribute: 'nombre_completo'
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nombre_completo} ({$record->pais} - {$record->institucion})")
                        ->createOptionForm(
                            FormularioIntegranteInternacional::form()
                        )
                        ->required()
                        ->createOptionUsing(function (array $data) {
                            return IntegranteInternacional::create($data);
                        }),

    
                    TextInput::make('rol')
                        ->label('Rol')
                        ->disabled()
                        ->required()
                        ->default('Cooperante Internacional'),
                    Hidden::make('rol')
                        ->default('Cooperante Internacional'),
                ])
                ->relationship()
                ->columnSpanFull()
                ->defaultItems(0)
                ->itemLabel('Integrante Internacional')
                ->addActionLabel('Agregar integrante internacional')
                ->grid(2),
             // actividades
        ];
    }
}

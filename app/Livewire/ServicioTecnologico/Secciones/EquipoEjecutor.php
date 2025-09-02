<?php

namespace App\Livewire\ServicioTecnologico\Secciones;

use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioDocente;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioEstudiante;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioIntegranteInternacional;

use Filament\Forms;
use App\Models\User;
use Filament\Forms\Get;

use App\Models\Personal\Empleado;
use App\Models\ServicioInfraestructura\EmpleadoServicio;
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
            Repeater::make('coordinador_servicio')
                ->label('Coordinador')
                ->relationship('coordinador_servicio') // ✅ Especificar relación
                ->schema([
                    Select::make('empleado_id')
                        ->label('')
                        ->required()
                        ->searchable(['nombre_completo', 'numero_empleado'])
                        ->relationship(name: 'empleado', titleAttribute: 'nombre_completo')
                        ->default(fn() => optional(Empleado::where('user_id', auth()->id())->first())->id)
                        ->disabled(),
                    Hidden::make('rol')->default('Coordinador'),
                ])
                ->columnSpanFull()
                ->minItems(1)
                ->maxItems(1)
                ->deletable(false)
                ->defaultItems(1),

            Repeater::make('empleado_servicio')
                ->label('Integrantes')
                ->relationship('empleado_servicio') // ✅ Especificar relación
                ->schema([
                    Select::make('empleado_id')
                        ->label('Integrante')
                        ->distinct()
                        ->searchable(['nombre_completo', 'numero_empleado'])
                        ->relationship(
                            name: 'empleado',
                            titleAttribute: 'nombre_completo',
                            modifyQueryUsing: fn(Builder $query) => $query->where('user_id', '!=', auth()->id())
                        )
                        ->createOptionForm(FormularioDocente::form())
                        ->required()
                        ->createOptionUsing(function (array $data) {
                            $user = User::create([
                                'email' => $data['email'],
                                'name' => $data['nombre_completo'],
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

                    Hidden::make('rol')->default('Integrante'),
                ])
                ->relationship()
                ->columnSpanFull()
                ->defaultItems(0)
                ->itemLabel('Empleado')
                ->addActionLabel('Agregar empleado')
                ->grid(2),

                Repeater::make('estudiante_servicio')
                ->label('Estudiantes')
                ->relationship('estudiante_servicio')
                ->schema([
                    Select::make('estudiante_id')
                        ->label('Estudiante')
                        ->required()
                        ->searchable(['cuenta', 'nombre', 'apellido'])
                        ->relationship(
                            name: 'estudiante',
                            titleAttribute: 'cuenta'
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
                        ->options([
                            'Servicio Social o PPS' => 'Servicio Social o PPS',
                            'Horas artículo 140 Normas Académicas' => 'Horas artículo 140 Normas Académicas',
                            'Práctica de asignatura' => 'Práctica de asignatura',
                        ])
                        ->required(),
                ])
                ->label('Estudiantes')
                ->relationship()
                ->defaultItems(0)
                ->columnSpanFull()
                ->grid(2)
                ->addActionLabel('Agregar estudiante'),
        ];
    }
}


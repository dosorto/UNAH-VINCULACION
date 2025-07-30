<?php
namespace App\Livewire\Proyectos\Actualizacion\Secciones;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioDocente;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioEstudiante;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioIntegranteInternacional;
use \App\Models\Proyecto\EquipoEjecutorBaja;

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

class PrimeraParte extends Component implements Forms\Contracts\HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public $proyecto_id;

    public function mount($proyecto_id)
    {
        $this->proyecto_id = $proyecto_id;
    }
    

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
                ])
                ->relationship()
                ->columnSpanFull()
                ->defaultItems(0)
                ->itemLabel('Empleado')
                ->addActionLabel('Agregar empleado')
                ->grid(2)
                ->afterStateUpdated(function ($state, $livewire, $set) {
                    $original = $livewire->data['empleado_proyecto_original'] ?? [];
                    $actual = $state ?? [];
                    $bajas = collect($original)->pluck('empleado_id')->diff(collect($actual)->pluck('empleado_id'));
                    foreach ($bajas as $id) {
                        \App\Models\Proyecto\EquipoEjecutorBaja::create([
                            'proyecto_id' => $livewire->proyecto_id,
                            'tipo_integrante' => 'empleado',
                            'integrante_id' => $id,
                            'fecha_baja' => now(),
                            'motivo_baja' => 'Eliminado desde el formulario',
                        ]);
                    }
                    $set('empleado_proyecto_original', $actual);
                }),
            Repeater::make('estudiante_proyecto')
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
                            'Practica Profesional' => 'Práctica Profesional',
                            'Servicio Social o PPS' => 'Servicio Social o PPS',
                            'Voluntariado' => 'Voluntariado',
                        ])
                        ->required(),
                ])
                ->label('Estudiantes')
                ->relationship()
                ->defaultItems(0)
                ->columnSpanFull()
                ->grid(2)
                ->addActionLabel('Agregar estudiante')
                ->afterStateUpdated(function ($state, $livewire, $set) {
                    $original = $livewire->data['estudiante_proyecto_original'] ?? [];
                    $actual = $state ?? [];
                    $bajas = collect($original)->pluck('estudiante_id')->diff(collect($actual)->pluck('estudiante_id'));
                    foreach ($bajas as $id) {
                        \App\Models\Proyecto\EquipoEjecutorBaja::create([
                            'proyecto_id' => $livewire->proyecto_id,
                            'tipo_integrante' => 'estudiante',
                            'integrante_id' => $id,
                            'fecha_baja' => now(),
                            'motivo_baja' => 'Eliminado desde el formulario',
                        ]);
                    }
                    $set('estudiante_proyecto_original', $actual);
                }),
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
                ->grid(2)
                ->afterStateUpdated(function ($state, $livewire, $set) {
                    $original = $livewire->data['integrante_internacional_proyecto_original'] ?? [];
                    $actual = $state ?? [];
                    $bajas = collect($original)->pluck('integrante_internacional_id')->diff(collect($actual)->pluck('integrante_internacional_id'));
                    foreach ($bajas as $id) {
                        \App\Models\Proyecto\EquipoEjecutorBaja::create([
                            'proyecto_id' => $livewire->proyecto_id,
                            'tipo_integrante' => 'internacional',
                            'integrante_id' => $id,
                            'fecha_baja' => now(),
                            'motivo_baja' => 'Eliminado desde el formulario',
                        ]);
                    }
                    $set('integrante_internacional_proyecto_original', $actual);
                }),
            // actividades
        ];
    }
}

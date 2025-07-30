<?php
namespace App\Livewire\Proyectos\Actualizacion\Secciones;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioDocente;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioEstudiante;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioIntegranteInternacional;

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

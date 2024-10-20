<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Proyecto\Proyecto;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Wizard;

use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\EntidadAcademica;
use App\Models\UnidadAcademica\Carrera;


use Filament\Forms\Components\Select;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;

use Filament\Forms\Components\Toggle;

use Filament\Forms\Components\TextInput;

use Filament\Forms\Components\Fieldset;
use App\Models\Demografia\Departamento;
use App\Models\Demografia\Municipio;
use App\Models\Demografia\Ciudad;
use App\Models\Demografia\Aldea;
use Filament\Forms\Get;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

use App\Models\User;
use App\Models\Personal\Empleado;
use App\Models\Estudiante\Estudiante;
use App\Models\Proyecto\Modalidad;

use Filament\Notifications\Notification;

class CreateProyectoVinculacion extends Component implements HasForms
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
                Wizard::make([
                    Wizard\Step::make('I.')
                        ->description('Información general del proyecto')
                        ->schema([
                            Forms\Components\TextInput::make('nombre_proyecto')
                                ->required()
                                ->columnSpanFull()
                                ->maxLength(255),
                            Select::make('facultades_centros')
                                ->label('Facultades o Centros')
                                ->searchable()
                                ->multiple()
                                ->live()
                                ->relationship(name: 'facultades_centros', titleAttribute: 'nombre')
                                ->preload()
                                ->required(),
                            Select::make('departamentos_academicos')
                                ->label('Departamentos Académicos')
                                ->searchable()
                                ->multiple()
                                ->relationship(
                                    name: 'departamentos_academicos',
                                    titleAttribute: 'nombre',
                                    modifyQueryUsing: fn($query, Get $get) => $query->whereIn('centro_facultad_id', $get('facultades_centros'))
                                )
                                ->preload()
                                ->required(),
                            Select::make('modalidad_id')
                                ->label('Modalidad')
                                ->required()
                                ->searchable()
                                ->relationship(name: 'modalidad', titleAttribute: 'nombre')
                                ->preload(),
                            //->required,
                            Select::make('coordinador_id')
                                ->label('Coordinador')
                                ->required()
                                ->searchable()
                                // ->relationship(name: 'coordinador', titleAttribute: 'user.nombre_completo')
                                ->getSearchResultsUsing(fn(string $search): array =>
                                Empleado::join('users', 'empleado.user_id', '=', 'users.id')
                                    ->where('users.email', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('users.email', 'empleado.id')
                                    ->toArray())
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('email')
                                        ->label('Correo electrónico Académico del coordinador')
                                        ->email()
                                        ->required(),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    $user = User::create(['email' => $data['email']]);
                                    $user->empleado()->create(['user_id' => $user->id]);
                                }),
                            //->required,

                            Repeater::make('empleado_proyecto')
                                ->label('Integrantes ')
                                ->schema([
                                    Select::make('empleado_id')
                                        ->label('Integrante')
                                        ->searchable()
                                        ->getSearchResultsUsing(fn(string $search): array =>
                                        Empleado::join('users', 'empleado.user_id', '=', 'users.id')
                                            ->where('users.email', 'like', "%{$search}%")
                                            ->limit(50)
                                            ->pluck('users.email', 'empleado.id')
                                            ->toArray())
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('email')
                                                ->label('Correo electrónico Académico del coordinador')
                                                ->email()
                                                ->required(),
                                        ])
                                        ->required()
                                        ->createOptionUsing(function (array $data) {
                                            $user = User::create(['email' => $data['email']]);
                                            $user->empleado()->create(['user_id' => $user->id]);
                                        }),
                                    //->required,
                                    Repeater::make('actividades')
                                        ->relationship()
                                        ->schema([
                                            Forms\Components\TextInput::make('descripcion')
                                                ->required()
                                                //->required
                                                ->columnSpanFull(),
                                            Datepicker::make('fecha_ejecucion')
                                                ->required()
                                                ->columnSpan(1)
                                        ])
                                        ->label('Actividades')
                                        ->defaultItems(0)
                                        ->itemLabel('Actividad')
                                        ->addActionLabel('Agregar actividad')
                                        ->collapsed()
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
                                        ->searchable()
                                        ->searchable()
                                        ->getSearchResultsUsing(fn(string $search): array =>
                                        Estudiante::join('users', 'estudiante.user_id', '=', 'users.id')
                                            ->where('users.email', 'like', "%{$search}%")
                                            ->limit(50)
                                            ->pluck('users.email', 'estudiante.id')
                                            ->toArray())
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('email')
                                                ->label('Correo electrónico Académico del Estudiante')
                                                ->email()
                                                ->required(),
                                        ])
                                        ->required()
                                        ->createOptionUsing(function (array $data) {
                                            $user = User::create(['email' => $data['email']]);
                                            $user->estudiante()->create(['user_id' => $user->id]);
                                        }),
                                    //->required,
                                    Select::make('tipo_participacion_estudiante')
                                        ->label('Tipo de participación')
                                        ->required()
                                        ->label('Tipo de participación')
                                        ->options([
                                            'voluntario' => 'Voluntario',
                                            'practica' => 'Práctica',
                                            'profesional' => 'Profesional',
                                        ]),
                                    //->required,
                                ])
                                ->label('Estudiantes')
                                ->relationship()
                                ->defaultItems(0)
                                ->columnSpanFull()
                                ->grid(2)
                                ->addActionLabel('Agregar estudiante')
                        ])
                        ->columns(2),

                    Wizard\Step::make('II.')
                        ->description('INFORMACIÓN DE LA ENTIDAD CONTRAPARTE DEL PROYECTO (en caso de contar con una contraparte).')
                        ->schema([
                            Repeater::make('entidad_contraparte')
                                ->schema([
                                    Forms\Components\TextInput::make('nombre'),
                                    //->required


                                    Toggle::make('es_internacional')
                                        ->inline(false)
                                        ->label('Internacional')
                                        ->default(false)
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('nombre_contacto'),
                                    //->required,
                                    Forms\Components\TextInput::make('telefono'),
                                    //->required,
                                    Forms\Components\TextInput::make('aporte')
                                        ->label('Aporte')
                                        ->columnSpan(1),
                                    //->required,
                                    Forms\Components\TextInput::make('instrumento_formalizacion')
                                        ->label('Instrumento de formalización')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('correo')
                                        ->label('Correo de contacto')
                                        ->columnSpan(1),
                                ])
                                ->label('Entidades contraparte')
                                ->relationship()
                                ->itemLabel('Entidad contraparte')
                                ->columns(2)
                                ->defaultItems(0)
                                ->addActionLabel('Agregar entidad contraparte')
                        ]),
                    Wizard\Step::make('III.')
                        ->description('DATOS DEL PROYECTO')
                        ->schema([
                            TextInput::make('resumen')
                                ->label('Resumen')
                                ->columnSpanFull(),
                            TextInput::make('objetivo_general')
                                ->label('Objetivo general')
                                ->columnSpanFull(),
                            TextInput::make('objetivos_especificos')
                                ->label('Objetivos específicos')
                                ->columnSpanFull(),

                            Fieldset::make('Fechas')
                                ->columns(2)
                                ->schema([
                                    DatePicker::make('fecha_inicio')
                                        ->label('Fecha de inicio')
                                        ->columnSpan(1),
                                    //->required,
                                    DatePicker::make('fecha_finalizacion')
                                        ->label('Fecha de finalización')
                                        ->columnSpan(1),
                                    //->required,
                                    DatePicker::make('evaluacion_intermedia')
                                        ->label('Evaluación intermedia')
                                        ->columnSpan(1),
                                    //->required,
                                    DatePicker::make('evaluacion_final')
                                        ->label('Evaluación final')
                                        ->columnSpan(1),
                                    //->required,
                                ])
                                ->columnSpanFull()
                                ->label('Fechas'),
                            TextInput::make('poblacion_participante')
                                ->label('Población participante')
                                ->columnSpan(1),


                            Select::make('modalidad_ejecucion')
                                ->label('Modalidad de ejecución')
                                ->options([
                                    'Distancia' => 'Distancia',
                                    'Presencial' => 'Presencial',
                                    'Bimodal' => 'Bimodal',
                                ])
                                ->in(['Distancia', 'Presencial', 'Bimodal'])
                                ->required()
                                ->live()
                                ->columnSpan(1),

                            Select::make('departamento_id')
                                ->label('Departamento')
                                ->searchable()
                                ->visible(fn(Get $get): bool
                                => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial')
                                ->relationship(name: 'departamento', titleAttribute: 'nombre')
                                ->live()
                                ->preload(),

                            Select::make('municipio_id')
                                ->label('Municipio')
                                ->searchable()
                                ->visible(fn(Get $get): bool
                                => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial')
                                ->relationship(
                                    name: 'municipio',
                                    titleAttribute: 'nombre',
                                    modifyQueryUsing: fn($query, Get $get) => $query->where('departamento_id', $get('departamento_id'))
                                )
                                ->preload(),

                            Select::make('ciudad_id')
                                ->label('Ciudad')
                                ->searchable()
                                ->visible(fn(Get $get): bool
                                => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial')
                                ->relationship(name: 'ciudad', titleAttribute: 'nombre')
                                ->createOptionForm([
                                    Select::make('departamento_id')
                                        ->label('Departamento')
                                        ->searchable()
                                        ->options(
                                            Departamento::pluck('nombre', 'id')

                                        )
                                        ->live()
                                        ->preload(),
                                    Select::make('municipio_id')
                                        ->label('Municipio')
                                        ->searchable()
                                        ->relationship(
                                            name: 'municipio',
                                            titleAttribute: 'nombre',
                                            modifyQueryUsing: fn($query, Get $get) => $query->where('departamento_id', $get('departamento_id'))
                                        )
                                        ->preload(),
                                    Forms\Components\TextInput::make('nombre')
                                        ->required(),
                                    Forms\Components\TextInput::make('codigo_postal')
                                        ->required()
                                ])
                                ->preload(),

                            TextInput::make('aldea')
                                ->visible(fn(Get $get): bool
                                => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial'),

                            TextInput::make('resultados_esperados')
                                ->label('Resultados esperados'),
                            TextInput::make('indicadores_medicion_resultados')
                                ->label('Indicadores de medición de resultados'),
                            Fieldset::make('Presupuesto')
                                ->schema([
                                    TextInput::make('presupuesto.aporte_estudiantes')
                                        ->label('Aporte de estudiantes')
                                        ->columnSpan(1),
                                    TextInput::make('presupuesto.aporte_profesores')
                                        ->label('Aporte de profesores')
                                        ->columnSpan(1),
                                    TextInput::make('presupuesto.aporte_academico_unah')
                                        ->label('Aporte académico UNAH')
                                        ->columnSpan(1),
                                    TextInput::make('presupuesto.aporte_transporte_unah')
                                        ->label('Aporte de transporte UNAH')
                                        ->columnSpan(1),
                                    TextInput::make('presupuesto.aporte_contraparte')
                                        ->label('Aporte de contraparte')
                                        ->columnSpan(1),
                                    TextInput::make('presupuesto.aporte_comunidad')
                                        ->label('Aporte de comunidad')
                                        ->columnSpan(1),

                                    TextInput::make('total_unah')
                                        ->label('Total UNAH')
                                        ->disabled()
                                        ->columnSpan(1),

                                    Repeater::make('superavit')
                                        ->schema([
                                            Forms\Components\TextInput::make('inversion')
                                                ->label('Inversión')
                                                ->numeric()
                                                ->columnSpan(1),
                                            //->required
                                            Forms\Components\TextInput::make('monto'),
                                            //->required
                                        ])
                                        ->label('Superávit')
                                        ->columnSpanFull()
                                        ->defaultItems(0)
                                        ->relationship()
                                ])
                                ->columns(2),

                        ])
                        ->columns(2),
                    Wizard\Step::make('IV.')
                        ->description('DATOS DEL PROYECTO')
                        ->schema([

                            Select::make('categoria')
                                ->label('Categoría')
                                ->multiple()
                                ->searchable()
                                ->relationship(name: 'categoria', titleAttribute: 'nombre')
                                ->preload(),

                            Select::make('ods')
                                ->label('ODS')
                                ->multiple()
                                ->searchable()
                                ->relationship(name: 'ods', titleAttribute: 'nombre')
                                ->preload(),
                        ]),


                ])->submitAction(new HtmlString(Blade::render(<<<BLADE
                <x-filament::button
                    type="submit"
                    size="sm"
                    color="info"
                >
                 Guardar
                </x-filament::button>
            BLADE))),


            ])
            ->statePath('data')
            ->model(Proyecto::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $data['fecha_registro'] = now();
        $record = Proyecto::create($data);
        $this->form->model($record)->saveRelationships();

        Notification::make()
        ->title('¡Éxito!')
        ->body('Proyecto creado correctamente')
        ->success()
        ->send();
    $this->js('location.reload();');
    
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.create-proyecto-vinculacion')
            ->layout('components.panel.modulos.modulo-proyectos');
    }
}

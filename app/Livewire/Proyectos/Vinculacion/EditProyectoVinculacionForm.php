<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Proyecto\Proyecto;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;


use App\Models\User;
use Filament\Forms\Get;
use App\Models\Demografia\Aldea;
use App\Models\Demografia\Ciudad;
use App\Models\Estado\TipoEstado;

use App\Models\Personal\Empleado;
use App\Models\Proyecto\Modalidad;
use Illuminate\Support\HtmlString;


use App\Models\Proyecto\CargoFirma;

use App\Models\Demografia\Municipio;

use App\Models\Estudiante\Estudiante;

use Filament\Forms\Components\Hidden;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Section;

use App\Models\Demografia\Departamento;

use App\Models\UnidadAcademica\Carrera;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;

use Filament\Notifications\Notification;

use Filament\Forms\Components\DatePicker;

use Filament\Forms\Components\FileUpload;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\EntidadAcademica;
use App\Models\UnidadAcademica\DepartamentoAcademico;

use App\Models\Personal\EmpleadoProyecto;

class EditProyectoVinculacionForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Proyecto $record;

    public function mount(Proyecto $proyecto): void
    {
        $this->record = $proyecto;
        $this->form->fill($this->record->attributesToArray());
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
                                    //->required()
                                    ->columnSpanFull()
                                    ->maxLength(255),
                                Select::make('facultades_centros')
                                    ->label('Facultades o Centros')
                                    ->searchable()
                                    ->multiple()
                                    ->live()
                                    ->relationship(name: 'facultades_centros', titleAttribute: 'nombre')
                                    ->preload(),
                                // ->required(),
                                Select::make('departamentos_academicos')
                                    ->label('Departamentos Académicos')
                                    ->searchable()
                                    ->multiple()
                                    ->relationship(
                                        name: 'departamentos_academicos',
                                        titleAttribute: 'nombre',
                                        modifyQueryUsing: fn($query, Get $get) => $query->whereIn('centro_facultad_id', $get('facultades_centros'))
                                    )
                                    ->preload(),
    
                                //->required(),
                                Select::make('modalidad_id')
                                    ->label('Modalidad')
                                    // ->required()
                                    ->searchable()
                                    ->relationship(name: 'modalidad', titleAttribute: 'nombre')
                                    ->preload(),
                                //->required,
    
                                /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                Repeater::make('coordinador_proyecto')
                                    ->label('Coordinador')
                                    ->schema([
                                        Select::make('empleado_id')
                                            ->label('Coordinador')
                                            // ->required()
                                            ->searchable(['nombre_completo', 'numero_empleado'])
                                            ->relationship(name: 'empleado', titleAttribute: 'nombre_completo')
                                            ->default(
                                                fn() => optional(Empleado::where('user_id', auth()->id())->first())->id
                                            )
                                            ->createOptionForm([
    
                                                // campus o facultad del empleado
    
                                                Select::make('centro_facultad_id')
                                                    ->label('Facultad o Centro')
                                                    ->searchable()
                                                    ->relationship(name: 'centro_facultad', titleAttribute: 'nombre')
                                                    ->live()
                                                    ->preload(),
    
                                                // departamento academico del empleado
    
                                                Select::make('departamento_academico_id')
                                                    ->label('Departamento Académico')
                                                    ->searchable()
                                                    ->visible(fn(Get $get): bool => $get('centro_facultad_id') !== null)
                                                    ->relationship(
                                                        name: 'departamento_academico',
                                                        titleAttribute: 'nombre',
                                                        modifyQueryUsing: fn($query, Get $get) => $query->where('centro_facultad_id', $get('centro_facultad_id'))
                                                    )
                                                    ->live()
                                                    ->preload(),
                                                Forms\Components\TextInput::make('nombre_completo')
                                                    ->label('Nombre completo')
                                                    ->required(),
    
                                                Forms\Components\TextInput::make('numero_empleado')
                                                    ->label('Número de empleado')
                                                    ->required(),
    
                                                Select::make('categoria_id')
                                                    ->label('Categoría')
                                                    ->searchable()
                                                    ->relationship(name: 'categoria', titleAttribute: 'nombre')
                                                    ->preload(),
    
                                                Forms\Components\TextInput::make('email')
                                                    ->label('Correo electrónico ')
                                                    ->email()
                                                    ->required(),
    
                                                Forms\Components\TextInput::make('celular')
                                                    ->label('Celular'), //->required(),
    
                                            ])
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
                                            })
                                            ->disabled(),
                                        //->required,
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
    
                                /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    
                                Repeater::make('empleado_proyecto')
                                    ->label('Integrantes ')
                                    ->schema([
                                        Select::make('empleado_id')
                                            ->label('Integrante')
                                            ->searchable(['nombre_completo', 'numero_empleado'])
                                            ->relationship(name: 'empleado', titleAttribute: 'nombre_completo')
                                            ->createOptionForm([
    
                                                // campus o facultad del empleado
    
                                                Select::make('centro_facultad_id')
                                                    ->label('Facultad o Centro')
                                                    ->searchable()
                                                    ->relationship(name: 'centro_facultad', titleAttribute: 'nombre')
                                                    ->live()
                                                    ->preload(),
    
                                                // departamento academico del empleado
    
                                                Select::make('departamento_academico_id')
                                                    ->label('Departamento Académico')
                                                    ->searchable()
                                                    ->visible(fn(Get $get): bool => $get('centro_facultad_id') !== null)
                                                    ->relationship(
                                                        name: 'departamento_academico',
                                                        titleAttribute: 'nombre',
                                                        modifyQueryUsing: fn($query, Get $get) => $query->where('centro_facultad_id', $get('centro_facultad_id'))
                                                    )
                                                    ->live()
                                                    ->preload(),
                                                Forms\Components\TextInput::make('nombre_completo')
                                                    ->label('Nombre completo'), //->required(),
    
                                                Forms\Components\TextInput::make('numero_empleado')
                                                    ->label('Número de empleado'), //->required(),
    
                                                Select::make('categoria_id')
                                                    ->label('Categoría')
                                                    ->searchable()
                                                    ->relationship(name: 'categoria', titleAttribute: 'nombre')
                                                    ->preload(),
    
                                                Forms\Components\TextInput::make('email')
                                                    ->label('Correo electrónico ')
                                                    ->email(), //->required(),
    
                                                Forms\Components\TextInput::make('celular')
                                                    ->label('Celular'), //->required(),
    
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
    
                                        Select::make('rol')
                                            ->label('Rol')
                                            ->options([
                                                'Subcoordinador' => 'Subcoordinador',
                                                'Integrante' => 'Integrante',
                                            ])
                                            ->required()
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
                                                    ->email(), //->required(),
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
                                    ->addActionLabel('Agregar estudiante'),
                                // actividades
                                
    
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
                                        Repeater::make('instrumento_formalizacion')
                                            ->schema([
                                                FileUpload::make('documento_url')
                                                    ->label('Documentos de Formalizacion')
                                                    ->disk('public')
                                                    ->directory('instrumento_formalizacion')
                                                    ->required()
                                            ])
                                            ->relationship()
                                            ->columns(2),
    
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
                            ->description('Cronograma de actividades.')
                            ->schema([
                                // actividades en proyecto
                                Repeater::make('actividades')
                                ->relationship()
                                ->schema([
                                        Forms\Components\TextInput::make('descripcion')
                                            ->label('Descripción')
                                            // ->required()
                                            //->required
                                            ->columnSpanFull(),
                                        Select::make('empleados')
                                            ->label('Responsables')
                                            ->relationship(
                                                name: 'empleados', 
                                                titleAttribute: 'nombre_completo', 
                                                modifyQueryUsing: function (\Illuminate\Database\Eloquent\Builder $query, Get $get) {
                                                    $data = $get('../../empleado_proyecto');
                                                    $ids = array_map(function ($item) {
                                                        return $item['empleado_id'];
                                                    }, $data);
                                                    
                                                    $query->whereIn('empleado.id', $ids)
                                                            ->with(['actividades']);
                                                }
                                            )
                                            ->preload()
                                            ->multiple()
                                            ->searchable()
                                            ->columnSpanFull(),
                                        Datepicker::make('fecha_ejecucion')
                                            ->label('Fecha de ejecución')
                                            // ->required()
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('objetivos')
                                            ->label('Objetivos')
                                            // ->required()
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('horas')
                                            ->label('Horas')
                                            // ->required()
                                            ->numeric(),
                                    ])
                                    ->label('Actividades')
                                    ->defaultItems(0)
                                    ->itemLabel('Actividad')
                                    ->addActionLabel('Agregar actividad')
                                    ->grid(2)
                                    ->collapsed(),
                            ]),
                        Wizard\Step::make('IV.')
                            ->description('DATOS DEL PROYECTO')
                            ->schema([
                                Forms\Components\Textarea::make('resumen')
                                    ->label('Resumen')
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('objetivo_general')
                                    ->label('Objetivo general')
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('objetivos_especificos')
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
                                    ->numeric()
                                    ->columnSpan(1),
    
    
                                Select::make('modalidad_ejecucion')
                                    ->label('Modalidad de ejecución')
                                    ->options([
                                        'Distancia' => 'Distancia',
                                        'Presencial' => 'Presencial',
                                        'Bimodal' => 'Bimodal',
                                    ])
                                    ->in(['Distancia', 'Presencial', 'Bimodal'])
                                    //->required()
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
                                        Forms\Components\TextInput::make('nombre'), //->required(),
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
                                            ->numeric()
                                            ->columnSpan(1),
                                        TextInput::make('presupuesto.aporte_profesores')
                                            ->label('Aporte de profesores')
                                            ->numeric()
                                            ->columnSpan(1),
                                        TextInput::make('presupuesto.aporte_academico_unah')
                                            ->label('Aporte académico UNAH')
                                            ->numeric()
                                            ->columnSpan(1),
                                        TextInput::make('presupuesto.aporte_transporte_unah')
                                            ->label('Aporte de transporte UNAH')
                                            ->numeric()
                                            ->columnSpan(1),
                                        TextInput::make('presupuesto.aporte_contraparte')
                                            ->label('Aporte de contraparte')
                                            ->numeric()
                                            ->columnSpan(1),
                                        TextInput::make('presupuesto.aporte_comunidad')
                                            ->label('Aporte de comunidad')
                                            ->numeric()
                                            ->columnSpan(1),
    
                                        TextInput::make('total_unah')
                                            ->label('Total UNAH')
                                            ->disabled()
                                            ->numeric()
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
                        Wizard\Step::make('V.')
                            ->description('Firmas')
                            ->schema([
    
                                Repeater::make('firma_proyecto_jefe')
                                    ->id('firma_proyecto_1')
                                    ->label('Jefe de departamento')
                                    ->schema([
                                        Select::make('empleado_id')
                                            ->label('')
                                            ->searchable(['nombre_completo', 'numero_empleado'])
                                            ->relationship(name: 'empleado', titleAttribute: 'nombre_completo'),
                                        Select::make('cargo_firma_id')
                                            ->label('')
                                            ->searchable()
                                            ->relationship(name: 'cargo_firma', titleAttribute: 'nombre')
                                            ->default(
                                                CargoFirma::where('nombre', 'Jefe departamento')->first()->id
                                            )
                                            ->disabled()
                                            ->preload(),
                                        Hidden::make('cargo_firma_id')
                                            ->default(
                                                CargoFirma::where('nombre', 'Jefe departamento')->first()->id
                                            ),
                                        Hidden::make('estado_revision')
                                            ->default('Pendiente'),
    
                                    ])
                                    ->addable(false)
                                    ->deletable(false)
                                    ->relationship()
                                    ->columns(2),
                                Repeater::make('firma_proyecto_decano')
                                    ->id('firma_proyecto_1')
                                    ->label('Decano de facultad o director de centro')
                                    ->schema([
                                        Select::make('empleado_id')
                                            ->label('')
                                            ->searchable(['nombre_completo', 'numero_empleado'])
                                            ->relationship(name: 'empleado', titleAttribute: 'nombre_completo'),
                                        Select::make('cargo_firma_id')
                                            ->label('')
                                            ->searchable()
                                            ->relationship(name: 'cargo_firma', titleAttribute: 'nombre')
                                            ->default(
                                                CargoFirma::where('nombre', 'Director centro')->first()->id
                                            )
                                            ->disabled()
                                            ->preload(),
                                        Hidden::make('cargo_firma_id')
                                            ->default(
                                                CargoFirma::where('nombre', 'Director centro')->first()->id
                                            ),
                                        Hidden::make('estado_revision')
                                            ->default('Pendiente'),
    
                                    ])
                                    ->deletable(false)
                                    ->addable(false)
                                    ->relationship()
                                    ->columns(2),
    
    
                                // repeter para el firma_proyecto_enlace
                                Repeater::make('firma_proyecto_enlace')
                                    ->label('Enlace de Vinculación')
                                    ->schema([
                                        Select::make('empleado_id')
                                            ->label('')
                                            ->searchable(['nombre_completo', 'numero_empleado'])
                                            ->relationship(name: 'empleado', titleAttribute: 'nombre_completo'),
                                        Select::make('cargo_firma_id')
                                            ->label('')
                                            ->searchable()
                                            ->relationship(name: 'cargo_firma', titleAttribute: 'nombre')
                                            ->default(
                                                CargoFirma::where('nombre', 'Enlace Vinculacion')->first()->id
                                            )
                                            ->disabled()
                                            ->preload(),
                                        Hidden::make('cargo_firma_id')
                                            ->default(
                                                CargoFirma::where('nombre', 'Enlace Vinculacion')->first()->id
                                            ),
                                        Hidden::make('estado_revision')
                                            ->default('Pendiente'),
    
                                    ])
                                    ->deletable(false)
                                    ->addable(false)
                                    ->relationship()
                                    ->columns(2),
    
                            ])


                    // Wizard\Step::make('IV.')
                    //    ->description('DATOS DEL PROYECTO')
                    //    ->schema([

                    //       Select::make('categoria')
                    //          ->label('Categoría')
                    //          ->multiple()
                    //          ->searchable()
                    //          ->relationship(name: 'categoria', titleAttribute: 'nombre')
                    //          ->preload(),

                    //    Select::make('ods')
                    //       ->label('ODS')
                    //      ->multiple()
                    //     ->searchable()
                    //     ->relationship(name: 'ods', titleAttribute: 'nombre')
                    //     ->preload(),
                    // ]),


                ])->submitAction(new HtmlString(Blade::render(<<<BLADE
             <x-filament::button
                    type="submit"
                    size="sm"
                    color="info"
                >
                 Enviar a Firmar
                </x-filament::button>

                <x-filament::button
                   wire:click="borrador"
                    size="sm"
                    color="success"
                >
                 Guardar Borrador
                </x-filament::button>
            BLADE))),


            ])
            ->statePath('data')
            ->model($this->record);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->record->update($data);

        $this->record->firma_proyecto()->create([
            'empleado_id' => auth()->user()->empleado->id,
            'cargo_firma_id' => CargoFirma::where('nombre', 'Coordinador Proyecto')->first()->id,
            'estado_revision' => 'Aprobado',
            'hash' => 'hash'
        ]);

        $this->record->estado_proyecto()->create([
            'empleado_id' => auth()->user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Esperando firma de Jefe de Departamento')->first()->id,
            'fecha' => now(),
            'comentario' => 'Proyecto creado',
        ]);

        Notification::make()
            ->title('¡Éxito!')
            ->body('El proyecto ha sido enviado a firmar exitosamente')
            ->success()
            ->send();
        $this->js('location.reload();');
    }

    public function borrador(): void
    {
        $data = $this->form->getState();

        $this->record->update($data);
        $this->record->save();
        Notification::make()
            ->title('¡Éxito!')
            ->body('Cambios guardados')
            ->success()
            ->send();
        $this->js('location.reload();');
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.edit-proyecto-vinculacion-form')
            ->layout('components.panel.modulos.modulo-proyectos');
    }
}
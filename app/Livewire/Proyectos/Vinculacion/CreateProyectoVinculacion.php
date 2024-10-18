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
                                //->required
                                ->columnSpanFull()
                                ->maxLength(255),
                            Select::make('facultades_centros')
                                ->label('Facultades o Centros')
                                ->searchable()
                                ->multiple()
                                ->relationship(name: 'facultades_centros', titleAttribute: 'nombre')
                                ->preload(),
                            //->required,
                            Select::make('departamentos_academicos')
                                ->label('Departamentos Académicos')
                                ->searchable()
                                ->multiple()
                                ->relationship(name: 'departamentos_academicos', titleAttribute: 'nombre')
                                ->preload(),
                            //->required,
                            Select::make('modalidad')
                                ->label('Modalidad')
                                ->searchable()
                                ->relationship(name: 'modalidad', titleAttribute: 'nombre')
                                ->preload(),
                            //->required,
                            Select::make('coordinador')
                                ->label('Coordinador')
                                ->searchable()
                                ->relationship(name: 'coordinador', titleAttribute: 'nombre_completo')
                                ->preload(),
                            //->required,

                            Repeater::make('empleado_proyecto')
                                ->label('Integrantes ')
                                ->schema([
                                    Select::make('empleado_id')
                                        ->label('Empleado')
                                        ->searchable()
                                        ->relationship(name: 'empleado', titleAttribute: 'nombre_completo')
                                        ->preload(),
                                    //->required,
                                    Repeater::make('actividades')
                                        ->relationship()
                                        ->schema([
                                            Forms\Components\TextInput::make('descripcion')
                                                //->required
                                                ->columnSpanFull(),
                                            Datepicker::make('fecha_ejecucion')
                                                //->required
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
                                        ->searchable()
                                        ->relationship(name: 'estudiante', titleAttribute: 'nombre')
                                        ->preload(),
                                    //->required,
                                    Select::make('tipo_participacion')
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


                                    Toggle::make('nacional')
                                        ->inline(false)
                                        ->label('Nacional')
                                        ->default(true)
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('nombre_contacto'),
                                    //->required,
                                    Forms\Components\TextInput::make('telefono'),
                                    //->required,
                                    Forms\Components\TextInput::make('aporte')
                                        ->label('Aporte')
                                        ->columnSpan(1),
                                    //->required,
                                    Forms\Components\TextInput::make('intrumento_formalizacion')
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
                                ->in(
                                    'Distancia',
                                    'Presencial',
                                    'Bimodal'
                                )
                                ->live()
                                ->columnSpan(1),

                            Select::make('departamento_id')
                                ->label('Departamento')
                                ->searchable()
                                ->visible(fn(Get $get): bool
                                => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial')
                                ->relationship(name: 'departamento', titleAttribute: 'nombre')
                                ->preload(),

                            Select::make('municipio_id')
                                ->label('Municipio')
                                ->searchable()
                                ->visible(fn(Get $get): bool
                                => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial')
                                ->relationship(name: 'municipio', titleAttribute: 'nombre')
                                ->preload(),

                            Select::make('ciudad_id')
                                ->label('Ciudad')
                                ->searchable()
                                ->visible(fn(Get $get): bool
                                => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial')
                                ->relationship(name: 'ciudad', titleAttribute: 'nombre')
                                ->preload(),

                            TextInput::make('aldea')
                                ->visible(fn(Get $get): bool
                                => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial')

                        ])
                        ->columns(2),
                    Wizard\Step::make('IV.')
                        ->description('DATOS DEL PROYECTO')
                        ->schema([]),


                ]),


            ])
            ->statePath('data')
            ->model(Proyecto::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $record = Proyecto::create($data);

        $this->form->model($record)->saveRelationships();
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.create-proyecto-vinculacion')
            ->layout('components.panel.modulos.modulo-proyectos');
    }
}

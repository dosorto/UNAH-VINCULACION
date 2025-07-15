<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use Filament\Forms;
use Filament\Forms\Get;

use Filament\Forms\Components\Select;


use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;


use Filament\Forms\Components\DatePicker;
use Filament\Forms\Set;

class CuartaParte
{
    public static function form(): array
    {
        return [
            Forms\Components\Textarea::make('resumen')
                ->label('Descripción del proyecto: (Explicar brevemente en qué consiste el proyecto, los antecedentes que dieron su origen y la importancia que tiene para los objetivos estratégicos de la UNAH)')
                ->rows(10)
                ->cols(30)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('descripcion_participantes')
                ->label('Descripción de las participantes del proyecto (Descripción breve de las unidades académicas participantes y su alineamiento con la estrategia de vinculación de la unidad. También se realizará una breve descripción de las contrapartes participantes, a qué se dedican y cómo se alinea el proyecto a los planes estratégicos)')
                ->rows(10)
                ->cols(30)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('definicion_problema')
                ->label('Definición del problema:  Breve descripción del problema que se desea resolver, indicando línea base que se tendrá en consideración para la definición de los resultados del proyecto')
                ->rows(10)
                ->cols(30)
                ->required()
                ->columnSpanFull(),

            Fieldset::make('Fechas')
                ->columns(2)
                ->schema([
                    DatePicker::make('fecha_inicio')
                        ->label('Fecha de inicio')
                        ->columnSpan(1)
                        ->required(),
                    DatePicker::make('fecha_finalizacion')
                        ->label('Fecha de finalización')
                        ->columnSpan(1)
                        ->required(),
                    DatePicker::make('evaluacion_intermedia')
                        ->label('Evaluación intermedia')
                        ->columnSpan(1)
                        ->required(),
                    DatePicker::make('evaluacion_final')
                        ->label('Evaluación final')
                        ->columnSpan(1)
                        ->required(),
                ])
                ->columnSpanFull()
                ->label('Fechas'),


            Fieldset::make('Beneficiarios')
                ->columns(2)
                ->schema([
                    TextInput::make('poblacion_participante')
                        ->label('Población participante (número aproximado)')
                        ->numeric()
                        ->columnSpan(1)
                        ->required(),
                    TextInput::make('hombres')
                        ->label('Hombres')
                        ->numeric()
                        ->columnSpan(1)
                        ->required(),
                    TextInput::make('mujeres')
                        ->label('Mujeres')
                        ->numeric()
                        ->columnSpan(1)
                        ->required(),
                    TextInput::make('otros')
                        ->label('Otros (Indicar número)')
                        ->numeric()
                        ->columnSpan(1)
                        ->required(),
                    
                    Fieldset::make('Distribución por etnia')
                        ->columns(3)
                        ->schema([
                            // Indígenas
                            Fieldset::make('Indígenas')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('indigenas_hombres')
                                        ->label('Hombres')
                                        ->numeric()
                                        ->default(0)
                                        ->columnSpan(1),
                                    TextInput::make('indigenas_mujeres')
                                        ->label('Mujeres')
                                        ->numeric()
                                        ->default(0)
                                        ->columnSpan(1),
                                ])
                                ->columnSpan(1),
                            
                            // Afroamericanos
                            Fieldset::make('Afroamericanos')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('afroamericanos_hombres')
                                        ->label('Hombres')
                                        ->numeric()
                                        ->default(0)
                                        ->columnSpan(1),
                                    TextInput::make('afroamericanos_mujeres')
                                        ->label('Mujeres')
                                        ->numeric()
                                        ->default(0)
                                        ->columnSpan(1),
                                ])
                                ->columnSpan(1),
                            
                            // Mestizos
                            Fieldset::make('Mestizos')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('mestizos_hombres')
                                        ->label('Hombres')
                                        ->numeric()
                                        ->default(0)
                                        ->columnSpan(1),
                                    TextInput::make('mestizos_mujeres')
                                        ->label('Mujeres')
                                        ->numeric()
                                        ->default(0)
                                        ->columnSpan(1),
                                ])
                                ->columnSpan(1),
                        ])
                        ->columnSpanFull(),
                ])
                ->columnSpanFull()
                ->label('Beneficiarios directos (número aproximado)'),


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

            Select::make('pais')
                ->label('País')
                ->searchable()
                ->multiple()
                ->options([
                    'Honduras' => 'Honduras',
                    'Guatemala' => 'Guatemala',
                    'El Salvador' => 'El Salvador',
                    'Nicaragua' => 'Nicaragua',
                    'Costa Rica' => 'Costa Rica',
                    'Panama' => 'Panamá',
                    'Belice' => 'Belice',
                    'Mexico' => 'México',
                    'Estados Unidos' => 'Estados Unidos',
                    'Otro' => 'Otro',
                ])
                ->default(['Honduras'])
                ->placeholder('Seleccione uno o más países'),

            Select::make('region')
                ->label('Región')
                ->searchable()
                ->multiple()
                ->options([
                    'Region Central' => 'Región Central',
                    'Region Norte' => 'Región Norte', 
                    'Region Sur' => 'Región Sur',
                    'Region Oriental' => 'Región Oriental',
                    'Region Occidental' => 'Región Occidental',
                    'Region Atlantida' => 'Región Atlántida',
                ])
                ->placeholder('Seleccione una o más regiones'),

            Select::make('departamento')
                ->label('Departamento')
                ->multiple()
                ->searchable()
                ->relationship(name: 'departamento', titleAttribute: 'nombre')
                ->live()
                ->afterStateUpdated(function (Set $set) {
                    $set('municipio', null);
                })
                ->live()
                ->required()
                ->preload(),

            Select::make('municipio')
                ->label('Municipio')
                ->searchable()
                ->required()
                ->multiple()
                ->relationship(
                    name: 'municipio',
                    titleAttribute: 'nombre',
                    modifyQueryUsing: fn($query, Get $get) => $query->whereIn('departamento_id', $get('departamento'))
                )
                ->live()
                ->preload(),

            Select::make('caserio')
                ->label('Caserío')
                ->searchable()
                ->multiple()
                ->options([
                    'La Esperanza' => 'La Esperanza',
                    'San Juan' => 'San Juan',
                    'Santa Cruz' => 'Santa Cruz',
                    'San Pedro' => 'San Pedro',
                ])
                ->placeholder('Seleccione uno o más caseríos'),

            Forms\Components\Textarea::make('aldea')
                ->rows(10)
                ->cols(30)
                ->label('Barrio/Aldea (opcional)')
                ->columnSpanFull(),

            

            // Select::make('ciudad_id')
            //     ->label('Ciudad')
            //     ->searchable()
            //     // ->visible(fn(Get $get): bool
            //     // => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial')
            //     ->relationship(name: 'ciudad', titleAttribute: 'nombre')
            //     ->createOptionForm([
            //         Select::make('departamento_id')
            //             ->label('Departamento')
            //             ->searchable()
            //             ->options(
            //                 Departamento::pluck('nombre', 'id')
            //             )
            //             ->live()
            //             ->preload(),
            //         Select::make('municipio_id')
            //             ->label('Municipio')
            //             ->searchable()
            //             ->relationship(
            //                 name: 'municipio',
            //                 titleAttribute: 'nombre',
            //                 modifyQueryUsing: fn($query, Get $get) => $query->where('departamento_id', $get('departamento'))
            //             )
            //             ->preload(),
            //         Forms\Components\TextInput::make('nombre'), //->required(),
            //         Forms\Components\TextInput::make('codigo_postal')
            //             ->required()
            //     ])
            //     ->preload(),

            Forms\Components\Textarea::make('impacto_deseado')
                ->cols(30)
                ->rows(4)
                ->label('Impacto que se desea generar en el proyecto ')
                ->required(),
            Forms\Components\Textarea::make('metodologia')
                ->cols(30)
                ->rows(4)
                ->label('Metodología')
                ->required(),
            Forms\Components\Textarea::make('bibliografia')
                ->cols(30)
                ->rows(4)
                ->label('Bibliografía')
                ->required(),

            Fieldset::make('Presupuesto del proyecto (indicado en lempiras)')
                ->schema([
                    TextInput::make('aporte_estudiantes')
                        ->label('Aporte de estudiantes')
                        ->numeric()
                        ->required(),

                    TextInput::make('aporte_profesores')
                        ->label('Aporte de profesores')
                        ->numeric()
                        ->required(),

                    TextInput::make('aporte_internacionales')
                        ->label('Aporte fondos internacionales')
                        ->numeric()
                        ->required(),

                    TextInput::make('aporte_otras_universidades')
                        ->label('Aporte otras universidades')
                        ->numeric()
                        ->required(),

                    TextInput::make('aporte_academico_unah')
                        ->label('Aporte académico UNAH')
                        ->numeric()
                        ->required(),

                    TextInput::make('aporte_transporte_unah')
                        ->label('Aporte de transporte UNAH')
                        ->numeric()
                        ->required(),

                    TextInput::make('otros_aportes')
                        ->label('Otros aportes')
                        ->numeric()
                        ->required(),

                    TextInput::make('aporte_contraparte')
                        ->label('Aporte de contraparte')
                        ->numeric()
                        ->required(),

                    TextInput::make('aporte_comunidad')
                        ->label('Aporte de beneficiarios')
                        ->numeric()
                        ->required(),
                ])
                ->relationship('presupuesto') // Relación hasOne
                ->columnSpanFull()
                ->columns(2),

            Repeater::make('superavit')
                ->label('Superávit (En caso de existir)')
                ->schema([
                    Forms\Components\TextInput::make('inversion')
                        ->label('Inversión (Descripción de la inversión realizada)')
                        ->maxLength(255)
                        ->columnSpan(1)
                        ->required(),
                    Forms\Components\TextInput::make('monto')
                        ->label('Monto')
                        ->numeric()
                        ->columnSpan(1)
                        ->required(),
                ])
                ->label('Superávit')
                ->columnSpanFull()
                ->defaultItems(0)
                ->relationship(),
        ];
    }
}

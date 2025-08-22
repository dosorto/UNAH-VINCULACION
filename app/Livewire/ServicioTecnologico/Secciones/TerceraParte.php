<?php

namespace App\Livewire\ServicioTecnologico\Secciones;

use Filament\Forms;
use Filament\Forms\Get;

use Filament\Forms\Components\Select;


use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;

use App\Models\Demografia\Municipio;
use App\Models\Demografia\Departamento;
use App\Models\Demografia\Ciudad;
use App\Models\Demografia\Aldea;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Set;

class TerceraParte
{
    public static function form(): array
    {
        return [
            Forms\Components\Textarea::make('descripción_servicio')
                ->label('Descripción del servicio: (Explicar brevemente en qué consiste el proyecto, los antecedentes que dieron su origen y la importancia que tiene para los objetivos estratégicos de la UNAH)')
                ->rows(5)
                ->cols(20)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('descripcion_problema')
                ->label('Descripción del problema / necesidad identificada al que responde el servicio que se brindará')
                ->rows(5)
                ->cols(20)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('descripcion_participante')
                ->label('Descripción de las participantes del proyecto (Descripción breve de las unidades académicas participantes y su alineamiento con la estrategia de vinculación de la unidad)')
                ->rows(5)
                ->cols(20)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('objetivo_general')
                ->label('Objetivo General (El objetivo debe estar basado en la población participante de la acción)')
                ->rows(5)
                ->cols(20)
                ->required()
                ->columnSpanFull(),

            Repeater::make('objetivosEspecificos')
                ->schema([
                    Forms\Components\Textarea::make('descripcion')
                        ->rows(3)
                        ->cols(30)
                        ->required()
                        ->label('Objetivo Específico')
                        ->columnSpanFull(),

                    Repeater::make('resultados_esperados')
                        ->schema([
                            Forms\Components\TextInput::make('nombre_resultado')
                                ->required()
                                ->label('Resultado')
                                ->columnSpan(1),
                                
                            Forms\Components\TextInput::make('nombre_indicador')
                                ->required()
                                ->label('Indicador')
                                ->columnSpan(1),
                        ])
                ])
                ->label('Objetivos Específicos')
                ->relationship('objetivosEspecificos')
                ->itemLabel(fn (array $state): ?string => 
                    isset($state['descripcion']) ? 
                    'Obj. Esp.: ' . substr($state['descripcion'], 0, 50) . '...' : 
                    'Objetivo Específico'
                )
                ->defaultItems(1)
                ->minItems(1)
                ->addActionLabel('Agregar Objetivo Específico')
                ->columnSpanFull(),

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

            Forms\Components\Textarea::make('aldeas')
                ->rows(5)
                ->cols(20)
                ->label('Barrio/Aldea (opcional)')
                ->columnSpanFull(),
            
        ];
    }
}

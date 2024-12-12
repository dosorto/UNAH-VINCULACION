<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use Filament\Forms;
use Filament\Forms\Get;

use Filament\Forms\Components\Select;

use App\Models\Demografia\Departamento;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;


use Filament\Forms\Components\DatePicker;

class CuartaParte
{
    public static function form(): array
    {
        return [
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

            Select::make('departamento')
                ->label('Departamento')
                ->multiple()
                ->searchable()
                // ->visible(fn(Get $get): bool
                // => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial')
                ->relationship(name: 'departamento', titleAttribute: 'nombre')
                ->live()
                ->preload(),

            Select::make('municipio')
                ->label('Municipio')
                ->searchable()
                ->multiple()
                // ->visible(fn(Get $get): bool
                // => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial')
                ->relationship(
                    name: 'municipio',
                    titleAttribute: 'nombre',
                    modifyQueryUsing: fn($query, Get $get) => $query->whereIn('departamento_id', $get('departamento'))
                )
                ->preload(),

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

            TextInput::make('aldea')
                ->label('Barrio/Aldea'),
                // ->visible(fn(Get $get): bool
                // => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial'),

            Fieldset::make('Resultados_indicadores')
                ->schema([
                    TextInput::make('resultados_esperados')
                        ->label('Resultados esperados'),
                    TextInput::make('indicadores_medicion_resultados')
                        ->label('Indicadores de medición de resultados'),
                ])
                ->label('')
                ->columnSpanFull()
                ->columns(2),
            Fieldset::make('Presupuesto')
                ->schema([
                    TextInput::make('aporte_estudiantes')
                        ->label('Aporte de estudiantes')
                        ->numeric()
                        ->required(),
                    
                    TextInput::make('aporte_profesores')
                        ->label('Aporte de profesores')
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
                    
                    TextInput::make('aporte_contraparte')
                        ->label('Aporte de contraparte')
                        ->numeric()
                        ->required(),
                    
                    TextInput::make('aporte_comunidad')
                        ->label('Aporte de comunidad')
                        ->numeric()
                        ->required(),
                ])
                ->relationship('presupuesto') // Relación hasOne
                ->columnSpanFull()
                ->columns(2 ),
            
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
                ->relationship(),
        ];
    }
}

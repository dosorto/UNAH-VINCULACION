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
use Filament\Forms\Set;

class CuartaParte
{
    public static function form(): array
    {
        return [
            Forms\Components\Textarea::make('resumen')
                ->label('Resumen')
                ->rows(10)
                ->cols(30)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('objetivo_general')
                ->label('Objetivo general')
                ->rows(10)
                ->cols(30)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('objetivos_especificos')
                ->label('Objetivos específicos')
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
            TextInput::make('poblacion_participante')
                ->label('Población participante')
                ->numeric()
                ->columnSpan(1)
                ->required(),


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

            Forms\Components\Textarea::make('aldea')
                ->rows(10)
                ->cols(30)
                ->label('Barrio/Aldea (opcional)')
                ->columnSpanFull(),
            // ->visible(fn(Get $get): bool
            // => $get('modalidad_ejecucion') === 'Bimodal' || $get('modalidad_ejecucion') === 'Presencial'),


            Forms\Components\Textarea::make('resultados_esperados')
                ->cols(30)
                ->rows(4)
                ->label('Resultados esperados')
                ->required(),
            Forms\Components\Textarea::make('indicadores_medicion_resultados')
                ->cols(30)
                ->rows(4)
                ->label('Indicadores de medición de resultados')
                ->required(),

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
                ->columns(2),

            Repeater::make('superavit')
            ->label('Superávit (En caso de existir)')
                ->schema([
                    Forms\Components\TextInput::make('inversion')
                        ->label('Inversión')
                        ->numeric()
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

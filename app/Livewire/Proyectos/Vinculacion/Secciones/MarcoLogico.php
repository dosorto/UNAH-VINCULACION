<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Fieldset;

class MarcoLogico
{
    public static function form(): array
    {
        return [
            Forms\Components\Textarea::make('objetivo_general')
                ->rows(5)
                ->cols(30)
                ->required()
                ->label('Objetivo General del Proyecto')
                ->columnSpanFull(),

            Repeater::make('objetivosEspecificos')
                ->schema([
                    Forms\Components\Textarea::make('descripcion')
                        ->rows(3)
                        ->cols(30)
                        ->required()
                        ->label('Objetivo Específico')
                        ->columnSpanFull(),

                    Repeater::make('resultados')
                        ->schema([
                            Forms\Components\TextInput::make('nombre_resultado')
                                ->required()
                                ->label('Resultado')
                                ->columnSpan(1),
                                
                            Forms\Components\TextInput::make('nombre_indicador')
                                ->required()
                                ->label('Indicador')
                                ->columnSpan(1),
                                
                            Forms\Components\TextInput::make('nombre_medio_verificacion')
                                ->required()
                                ->label('Medio de Verificación')
                                ->columnSpan(1),
                        ])
                        ->label('Resultados, Indicadores y Medios de Verificación')
                        ->relationship('resultados')
                        ->itemLabel(fn (array $state): ?string => 
                            $state['nombre_resultado'] ?? 'Resultado'
                        )
                        ->defaultItems(1)
                        ->minItems(1)
                        ->addActionLabel('Agregar Resultado (con Indicador y Medio)')
                        ->columns(3)
                        ->columnSpanFull(),
                ])
                ->label('Objetivos Específicos del Marco Lógico')
                ->relationship('objetivosEspecificos')
                ->itemLabel(fn (array $state): ?string => 
                    isset($state['descripcion']) ? 
                    'Obj. Esp.: ' . substr($state['descripcion'], 0, 50) . '...' : 
                    'Objetivo Específico'
                )
                ->defaultItems(1)
                ->minItems(1)
                ->addActionLabel('Agregar Objetivo Específico')
                ->columnSpanFull()
        ];
    }
}

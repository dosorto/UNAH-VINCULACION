<?php

namespace App\Livewire\Proyectos\Actualizacion\Secciones;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Forms\Get;
class SegundaParte
{
    public static function form(): array
    {
        return [
            // Sección de extensión de tiempo de ejecución
            Fieldset::make('Extensión de tiempo de ejecución del proyecto')
                ->schema([
                    TextInput::make('fecha_finalizacion_actual')
                        ->label('Fecha de finalización actual del proyecto')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Esta es la fecha de finalización que tenía el proyecto al momento de crear esta ficha')
                        ->formatStateUsing(function ($livewire) {
                            // Si estamos editando una ficha existente, mostrar la fecha guardada
                            if (isset($livewire->record) && $livewire->record instanceof \App\Models\Proyecto\FichaActualizacion && $livewire->record->fecha_finalizacion_actual) {
                                return \Carbon\Carbon::parse($livewire->record->fecha_finalizacion_actual)->format('d/m/Y');
                            }
                            // Si estamos creando una nueva ficha, mostrar la fecha actual del proyecto
                            elseif (isset($livewire->record) && $livewire->record instanceof \App\Models\Proyecto\Proyecto && $livewire->record->fecha_finalizacion) {
                                return \Carbon\Carbon::parse($livewire->record->fecha_finalizacion)->format('d/m/Y');
                            }
                            return 'No definida';
                        })
                        ->columnSpan(1)->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            $fechaFin = $get('fecha_ampliacion');
                            if ($state && $fechaFin && $state > $fechaFin) {
                                $set('fecha_ampliacion', null);
                            }
                        }),

                    DatePicker::make('fecha_ampliacion')
                        ->label('Nueva fecha de finalización')
                        ->helperText('Seleccione la nueva fecha de finalización del proyecto')
                        ->displayFormat('d/m/Y')
                        ->columnSpan(1)
                        ->live()
                        ->rules([
                                fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $fechaInicio = $get('fecha_finalizacion_actual');
                                    if ($fechaInicio && $value) {
                                        try {
                                            // Convierte ambas fechas a Carbon
                                            $fechaInicioCarbon = \Carbon\Carbon::createFromFormat('d/m/Y', $fechaInicio);
                                            $fechaAmpliacionCarbon = \Carbon\Carbon::parse($value); // DatePicker retorna Y-m-d
                                            if ($fechaAmpliacionCarbon->lte($fechaInicioCarbon)) {
                                                $fail('La nueva fecha de finalización debe ser mayor a la fecha actual de finalización.');
                                            }
                                        } catch (\Exception $e) {
                                            // Si hay error de formato, no validar
                                        }
                                    }
                                },
                            ]),

                    Textarea::make('motivo_ampliacion')
                        ->label('Motivos por los cuales se extiende la fecha de ejecución del proyecto')
                        ->helperText('Incluir las fechas de evaluación del proyecto')
                        ->rows(6)
                        ->columnSpanFull()
                        ->required()
                        ->visible(fn (Get $get) => !empty($get('fecha_ampliacion'))),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }
}

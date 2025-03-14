<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;




class TerceraParte
{
    public static function form(): array
    {

        return  [
            Repeater::make('actividades')
                ->relationship()
                ->schema([
                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción de la actividad')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('objetivos')
                        ->label('Objetivos de la actividad')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('resultados')
                        ->label('Resultados de la actividad')
                        ->required()
                        ->rows(5)
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

                                // agregar al $ids el id del usuario logueado porque es el coordinador del proyecto
                                $ids[] = auth()->user()->empleado->id;



                                $query->whereIn('empleado.id', $ids)
                                    ->with(['actividades']);
                            }
                        )
                        ->preload()
                        ->multiple()
                        ->required()
                        ->searchable()
                        ->columnSpanFull(),
                    Datepicker::make('fecha_inicio')
                        ->label('Fecha de Inicio')
                        ->required()
                        ->columnSpan(1),
                    Datepicker::make('fecha_finalizacion')
                        ->label('Fecha de Finalizacion')
                        ->required()
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('horas')
                        ->label('Horas')
                        ->required()
                        ->numeric(),
                ])
                ->label('Actividades')
                ->defaultItems(0)
                ->itemLabel('Actividad')
                ->addActionLabel('Agregar actividad')
                ->grid(2)
                ->collapsed(),
        ];
    }
}

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
        ];
    }
}

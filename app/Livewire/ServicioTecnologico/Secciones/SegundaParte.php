<?php

namespace App\Livewire\ServicioTecnologico\Secciones;

use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Illuminate\Validation\Rule;

class SegundaParte
{
    public static function form(): array
    {
        return [
            Repeater::make('actividades')
                ->relationship()
                ->schema([
                    Forms\Components\Textarea::make('descripcion')
                        ->label('Descripción de la actividad')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),

                    Select::make('empleados')
                        ->label('Responsables')
                        ->relationship(
                            name: 'empleados',
                            titleAttribute: 'nombre_completo',
                            modifyQueryUsing: function (\Illuminate\Database\Eloquent\Builder $query, Get $get) {
                                $data = $get('../../empleado_servicio');
                                $ids = array_map(fn($item) => $item['empleado_id'], $data ?? []);
                                $ids[] = auth()->user()->empleado->id;

                                $query->whereIn('empleado.id', $ids)
                                    ->with(['actividades']);
                            }
                        )
                        ->rules(function (Get $get) {
                            $data = $get('../../empleado_proyecto') ?? [];
                            $ids = array_map(fn($item) => $item['empleado_id'] ?? null, $data);
                            $ids[] = auth()->user()->empleado->id;
                            $ids = array_filter(array_unique($ids));

                            return ['array', Rule::in($ids)];
                        })
                        ->validationMessages([
                            'in' => 'El Empleado seleccionado debe ser un integrante del proyecto :)'
                        ])
                        ->required()
                        ->preload()
                        ->multiple()
                        ->searchable()
                        ->columnSpanFull(),

                    // Agrupamos fechas en una fila
                    Datepicker::make('fecha_inicio')
                        ->label('Fecha de Inicio')
                        ->required()
                        ->columnSpan(2),

                    Datepicker::make('fecha_finalizacion')
                        ->label('Fecha de Finalización')
                        ->required()
                        ->columnSpan(2),
                ])
                ->label('(Descripción de las principales actividades requeridas para el desarrollo de la acción. Incluir la presentación del informe final, sistematización de la acción, proceso de evaluación y la publicación o difusión de los resultados)')
                ->defaultItems(0)
                ->itemLabel('Actividad')
                ->addActionLabel('Agregar actividad')
                ->columnSpanFull()
                ->grid(2)
                ->collapsed(),
        ];
    }
}

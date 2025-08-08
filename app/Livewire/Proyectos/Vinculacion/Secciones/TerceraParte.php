<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;

use Illuminate\Validation\Rule;


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

                        ->rules(function (Get $get) {
                            $data = $get('../../empleado_proyecto') ?? [];
                    
                            // Extraer los IDs de los empleados si existen en cada entrada
                            $ids = array_map(fn($item) => $item['empleado_id'] ?? null, array_values($data));
                    
                            // Filtrar valores nulos
                            $ids = array_filter($ids);
                    
                            // Agregar el ID del usuario logueado
                            $ids[] = auth()->user()->empleado->id;
                            $ids = array_values(array_unique($ids)); // Evita duplicados
                    
                            // Retornar la regla de validación
                            return ['array', Rule::in($ids)];
                        })
                        ->validationMessages([
                            'in' => 'El Empleado seleccionado debe ser un integrante del proyecto :)'
                        ])
                        ->required()
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
                        ->minLength(1)
                        ->maxLength(255)
                        ->required()
                        ->numeric(),
                ])
                ->label('Descripción de actividades del proyecto (Descripción de todas las actividades enmarcadas en el proyecto, las cuales pueden ser, entre otras, la negociación inicial, la organización de los equipos de trabajo, la planificación, el desarrollo de actividades de capacitación y fortalecimiento, presentación de informe intermedio o parciales, presentación del informe final, proceso de evaluación, proceso de sistematización, publicación de artículo, otras acciones de divulgación)')
                ->defaultItems(0)
                ->itemLabel('Actividad')
                ->addActionLabel('Agregar actividad')
                ->grid(2)
                ->collapsed(),
        ];
    }
}

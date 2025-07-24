<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioDocente;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioEstudiante;
use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioIntegranteInternacional;

use Filament\Forms;
use App\Models\User;
use Filament\Forms\Get;

use App\Models\Personal\Empleado;
use App\Models\Proyecto\IntegranteInternacional;

use App\Models\Estudiante\Estudiante;
use App\Models\UnidadAcademica\FacultadCentro;
use Faker\Provider\ar_EG\Text;
use Filament\Forms\Components\Hidden;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Fieldset;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Set;


class Presupuesto
{
    public static function form(): array
    {
        return [
            Fieldset::make('Aporte Institucional (manifestado en lempiras)')
                ->schema([
                    Repeater::make('aporte_institucional')
                        ->schema([
                            Hidden::make('concepto'),
                            Hidden::make('unidad'),
                                
                            TextInput::make('concepto_label')
                                ->label('Concepto')
                                ->disabled()
                                ->columnSpan(2),
                                
                            TextInput::make('unidad_label')
                                ->label('Unidad')
                                ->disabled()
                                ->columnSpan(1),
                                
                            TextInput::make('cantidad')
                                ->label('Cantidad')
                                ->numeric()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $costoUnitario = floatval($get('costo_unitario') ?? 0);
                                    $cantidad = floatval($state ?? 0);
                                    $costoTotal = $cantidad * $costoUnitario;
                                    $set('costo_total', number_format($costoTotal, 2, '.', ''));
                                    
                                    // Calcular total usando una función helper
                                    self::calcularTotalAporteInstitucional($get, $set);
                                })
                                ->columnSpan(1),
                                
                            TextInput::make('costo_unitario')
                                ->label('Costo unitario')
                                ->numeric()
                                ->prefix('L.')
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $cantidad = floatval($get('cantidad') ?? 0);
                                    $costoUnitario = floatval($state ?? 0);
                                    $costoTotal = $cantidad * $costoUnitario;
                                    $set('costo_total', number_format($costoTotal, 2, '.', ''));
                                    
                                    // Calcular total usando una función helper
                                    self::calcularTotalAporteInstitucional($get, $set);
                                })
                                ->columnSpan(1),
                                
                            TextInput::make('costo_total')
                                ->label('Costo Total')
                                ->numeric()
                                ->prefix('L.')
                                ->disabled()
                                ->dehydrated()
                                ->live()
                                ->columnSpan(1),
                        ])
                        ->label('Conceptos del Aporte Institucional')
                        ->defaultItems(7)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columns(6)
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            // Calcular el total del aporte institucional
                            $total = 0;
                            if (is_array($state)) {
                                foreach ($state as $item) {
                                    $total += floatval($item['costo_total'] ?? 0);
                                }
                            }
                            $set('total_aporte_institucional', $total);
                        })
                        ->default([
                            [
                                'concepto' => 'horas_trabajo_docentes',
                                'concepto_label' => 'a) Horas de trabajo docentes',
                                'unidad' => 'hra_profes',
                                'unidad_label' => 'Hra/profes',
                                'cantidad' => 0,
                                'costo_unitario' => 0,
                                'costo_total' => 0,
                            ],
                            [
                                'concepto' => 'horas_trabajo_estudiantes',
                                'concepto_label' => 'b) Horas de trabajo estudiantes',
                                'unidad' => 'hra_estud',
                                'unidad_label' => 'Hra/estud',
                                'cantidad' => 0,
                                'costo_unitario' => 0,
                                'costo_total' => 0,
                            ],
                            [
                                'concepto' => 'gastos_movilizacion',
                                'concepto_label' => 'c) Gastos de movilización',
                                'unidad' => 'global',
                                'unidad_label' => 'Global',
                                'cantidad' => 0,
                                'costo_unitario' => 0,
                                'costo_total' => 0,
                            ],
                            [
                                'concepto' => 'utiles_materiales_oficina',
                                'concepto_label' => 'd) Útiles y materiales de oficina',
                                'unidad' => 'global',
                                'unidad_label' => 'Global',
                                'cantidad' => 0,
                                'costo_unitario' => 0,
                                'costo_total' => 0,
                            ],
                            [
                                'concepto' => 'gastos_impresion',
                                'concepto_label' => 'e) Gastos de impresión',
                                'unidad' => 'global',
                                'unidad_label' => 'Global',
                                'cantidad' => 0,
                                'costo_unitario' => 0,
                                'costo_total' => 0,
                            ],
                            [
                                'concepto' => 'costos_indirectos_infraestructura',
                                'concepto_label' => 'f) Costos indirectos por infraestructura universidad',
                                'unidad' => 'porcentaje',
                                'unidad_label' => '%',
                                'cantidad' => 0,
                                'costo_unitario' => 0,
                                'costo_total' => 0,
                            ],
                            [
                                'concepto' => 'costos_indirectos_servicios',
                                'concepto_label' => 'g) Costos indirectos por servicios públicos',
                                'unidad' => 'porcentaje',
                                'unidad_label' => '%',
                                'cantidad' => 0,
                                'costo_unitario' => 0,
                                'costo_total' => 0,
                            ],
                        ])
                        ->relationship('aporteInstitucional')
                        ->columnSpanFull(),
                        
                    TextInput::make('total_aporte_institucional')
                        ->label('Total Aporte Institucional')
                        ->numeric()
                        ->prefix('L.')
                        ->disabled()
                        ->dehydrated()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Fieldset::make('Presupuesto del proyecto')
                ->schema([
                    TextInput::make('aporte_contraparte')
                        ->label('Aporte de contraparte')
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

                    TextInput::make('aporte_comunidad')
                        ->label('Aportes de beneficiarios')
                        ->numeric()
                        ->required(),

                    TextInput::make('otros_aportes')
                        ->label('Otros aportes')
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

    private static function calcularTotalAporteInstitucional(Get $get, Set $set): void
    {
        // Obtener todos los datos del repeater desde el nivel superior
        $allData = $get('../../') ?? [];
        $aporteInstitucional = $allData['aporte_institucional'] ?? [];
        
        $total = 0;
        if (is_array($aporteInstitucional)) {
            foreach ($aporteInstitucional as $item) {
                $costoTotal = floatval($item['costo_total'] ?? 0);
                $total += $costoTotal;
            }
        }
        
        $set('../../total_aporte_institucional', number_format($total, 2, '.', ''));
    }
}

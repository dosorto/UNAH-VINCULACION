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
                                ->columnSpan(2)
                                ->default(0),
                                
                            TextInput::make('unidad_label')
                                ->label('Unidad')
                                ->disabled()
                                ->columnSpan(1)
                                ->default(0),
                                
                            TextInput::make('cantidad')
                                ->label('Cantidad')
                                ->numeric()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $costoUnitario = floatval($get('costo_unitario') ?? 0);
                                    $cantidad = floatval($state ?? 0);
                                    $costoTotal = $cantidad * $costoUnitario;
                                    $set('costo_total', number_format($costoTotal, 2, '.', ''));
                                    // Calcular total usando una función helper
                                    self::calcularTotalAporteInstitucional($get, $set);
                                    self::calcularTotalCantidades($get, $set); // Nueva función
                                })
                                ->columnSpan(1)
                                ->default(0),
                                
                            TextInput::make('costo_unitario')
                                ->label('Costo unitario')
                                ->numeric()
                                ->prefix('L.')
                                ->live(onBlur: true) 
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $cantidad = floatval($get('cantidad') ?? 0);
                                    $costoUnitario = floatval($state ?? 0);
                                    $costoTotal = $cantidad * $costoUnitario;
                                    $set('costo_total', number_format($costoTotal, 2, '.', ''));
                                    // Calcular total usando una función helper
                                    self::calcularTotalAporteInstitucional($get, $set);
                                    self::calcularCostoUnitario($get, $set); // Nueva función
                                })
                                ->columnSpan(1)
                                ->default(0),

                            TextInput::make('costo_total')
                                ->label('Costo Total')
                                ->numeric()
                                ->prefix('L.')
                                ->disabled()
                                ->dehydrated()
                                ->live(onBlur: true)
                                ->columnSpan(1),
                        ])
                        ->label('Conceptos del Aporte Institucional')
                        ->defaultItems(7)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columns(6)
                        ->live(onBlur: true)
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
                            ],
                            [
                                'concepto' => 'horas_trabajo_estudiantes',
                                'concepto_label' => 'b) Horas de trabajo estudiantes',
                                'unidad' => 'hra_estud',
                                'unidad_label' => 'Hra/estud',
                            ],
                            [
                                'concepto' => 'gastos_movilizacion',
                                'concepto_label' => 'c) Gastos de movilización',
                                'unidad' => 'global',
                                'unidad_label' => 'Global',
                            ],
                            [
                                'concepto' => 'utiles_materiales_oficina',
                                'concepto_label' => 'd) Útiles y materiales de oficina',
                                'unidad' => 'global',
                                'unidad_label' => 'Global',
                            ],
                            [
                                'concepto' => 'gastos_impresion',
                                'concepto_label' => 'e) Gastos de impresión',
                                'unidad' => 'global',
                                'unidad_label' => 'Global',
                            ],
                        ])
                        ->relationship('aporteInstitucional')
                        ->columnSpanFull(),

                        // Campos separados para infraestructura
                    Fieldset::make('f) Costos indirectos por infraestructura universidad')
                        ->schema([
                            Hidden::make('concepto'),
                            Hidden::make('unidad'),
                            TextInput::make('infraestructura_unidad')
                                ->label('Unidad (%)')
                                ->disabled()
                                ->columnSpan(1)
                                ->default('%'),

                            TextInput::make('cantidad_infraestructura')
                                ->label('Cantidad')
                                ->numeric()
                                ->disabled()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $costoUnitario = floatval($get('costo_unitario_infraestructura') ?? 0);
                                    $cantidad = floatval($state ?? 0);
                                    $costoTotal = $cantidad * $costoUnitario;
                                    $set('costo_total_infraestructura', number_format($costoTotal, 2, '.', ''));
                                    // Recalcular el total del aporte institucional
                                    self::calcularTotalAporteInstitucional($get, $set);
                                })
                                ->columnSpan(1)
                                ->default(0),

                            TextInput::make('costo_unitario_infraestructura')
                                ->label('Costo unitario')
                                ->numeric()
                                ->disabled()
                                ->prefix('L.')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $cantidad = floatval($get('cantidad_infraestructura') ?? 0);
                                    $costoUnitario = floatval($state ?? 0);
                                    $costoTotal = $cantidad * $costoUnitario;
                                    $set('costo_total_infraestructura', number_format($costoTotal, 2, '.', ''));
                                    // Recalcular el total del aporte institucional
                                    self::calcularTotalAporteInstitucional($get, $set);
                                })
                                ->columnSpan(1)
                                ->default(0),

                            TextInput::make('costo_total_infraestructura')
                                ->label('Costo Total')
                                ->numeric()
                                ->prefix('L.')
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(1),
                        ])
                        ->default([
                            'concepto' => 'costos_indirectos_infraestructura',
                            'concepto_label' => 'f) Costos indirectos por infraestructura universidad',
                            'unidad' => 'porcentaje',
                            'unidad_label' => '%',
                        ])
                        ->columns(4)
                        ->columnSpanFull(),

                    // Campos separados para servicios
                    Fieldset::make('g) Costos indirectos por servicios públicos')
                        ->schema([
                            Hidden::make('concepto'),
                            Hidden::make('unidad'),
                            TextInput::make('servicios_unidad')
                                ->label('Unidad (%)')
                                ->disabled()
                                ->columnSpan(1)
                                ->default('%'),

                            TextInput::make('cantidad_servicios')
                                ->label('Cantidad')
                                ->numeric()
                                ->disabled()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $costoUnitario = floatval($get('costo_unitario_servicios') ?? 0);
                                    $cantidad = floatval($state ?? 0);
                                    $costoTotal = $cantidad * $costoUnitario;
                                    $set('costo_total_servicios', number_format($costoTotal, 2, '.', ''));
                                    // Recalcular el total del aporte institucional
                                    self::calcularTotalAporteInstitucional($get, $set);
                                })
                                ->columnSpan(1)
                                ->default(0),

                            TextInput::make('costo_unitario_servicios')
                                ->label('Costo unitario')
                                ->numeric()
                                ->disabled()
                                ->prefix('L.')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $cantidad = floatval($get('cantidad_servicios') ?? 0);
                                    $costoUnitario = floatval($state ?? 0);
                                    $costoTotal = $cantidad * $costoUnitario;
                                    $set('costo_total_servicios', number_format($costoTotal, 2, '.', ''));
                                    // Recalcular el total del aporte institucional
                                    self::calcularTotalAporteInstitucional($get, $set);
                                })
                                ->columnSpan(1)
                                ->default(0),

                            TextInput::make('costo_total_servicios')
                                ->label('Costo Total')
                                ->numeric()
                                ->prefix('L.')
                                ->disabled()
                                ->dehydrated()
                                ->columnSpan(1),
                        ])
                        ->default([
                            'concepto' => 'costos_indirectos_servicios',
                            'concepto_label' => 'g) Costos indirectos por servicios públicos',
                            'unidad' => 'porcentaje',
                            'unidad_label' => '%',
                        ])
                        ->columns(4)
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
                        ->required()
                        ->default(0),

                    TextInput::make('aporte_internacionales')
                        ->label('Aporte fondos internacionales')
                        ->numeric()
                        ->required()
                        ->default(0),

                    TextInput::make('aporte_otras_universidades')
                        ->label('Aporte otras universidades')
                        ->numeric()
                        ->required()
                        ->default(0),

                    TextInput::make('aporte_comunidad')
                        ->label('Aportes de beneficiarios')
                        ->numeric()
                        ->required()
                        ->default(0),

                    TextInput::make('otros_aportes')
                        ->label('Otros aportes')
                        ->numeric()
                        ->required()
                        ->default(0),

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
        
        // Agregar los costos de infraestructura y servicios al total
        $costoInfraestructura = floatval($get('../../costo_total_infraestructura') ?? 0);
        $costoServicios = floatval($get('../../costo_total_servicios') ?? 0);
        $total += $costoInfraestructura + $costoServicios;
        
        $set('../../total_aporte_institucional', number_format($total, 2, '.', ''));
    }

    private static function calcularTotalCantidades(Get $get, Set $set): void
    {
        // Obtener todos los datos del repeater desde el nivel superior
        $allData = $get('../../') ?? [];
        $aporteInstitucional = $allData['aporte_institucional'] ?? [];
        
        $totalCantidades = 0;
        
        if (is_array($aporteInstitucional)) {
            foreach ($aporteInstitucional as $item) {
                $cantidad = floatval($item['cantidad'] ?? 0);
                $totalCantidades += $cantidad;
            }
        }
        
        // Asignar el 5% del total de cantidades al campo cantidad_infraestructura
        $cantidadInfraestructura = $totalCantidades * 0.05;
        $set('../../cantidad_infraestructura', $cantidadInfraestructura);
        
        // También asignar el 5% del total de cantidades al campo cantidad_servicios
        $cantidadServicios = $totalCantidades * 0.05;
        $set('../../cantidad_servicios', $cantidadServicios);
        
        // Actualizar el costo total de infraestructura
        $costoUnitarioInfra = floatval($get('../../costo_unitario_infraestructura') ?? 0);
        $costoTotalInfra = $cantidadInfraestructura * $costoUnitarioInfra;
        $set('../../costo_total_infraestructura', number_format($costoTotalInfra, 2, '.', ''));
        
        // Actualizar el costo total de servicios
        $costoUnitarioServ = floatval($get('../../costo_unitario_servicios') ?? 0);
        $costoTotalServ = $cantidadServicios * $costoUnitarioServ;
        $set('../../costo_total_servicios', number_format($costoTotalServ, 2, '.', ''));
        
        // Recalcular el total del aporte institucional incluyendo infraestructura y servicios
        self::calcularTotalAporteInstitucional($get, $set);
    }
        
    private static function calcularCostoUnitario(Get $get, Set $set): void
    {
        // Obtener todos los datos del repeater desde el nivel superior
        $allData = $get('../../') ?? [];
        $aporteInstitucional = $allData['aporte_institucional'] ?? [];

        $totalCostoUnitario = 0;

        if (is_array($aporteInstitucional)) {
            foreach ($aporteInstitucional as $item) {
                $costoUnitario = floatval($item['costo_unitario'] ?? 0);
                $totalCostoUnitario += $costoUnitario;
            }
        }
        
        // Asignar el 5% del total del costo unitario al campo costo_unitario_infraestructura
        $costoUnitarioInfraestructura = $totalCostoUnitario * 0.05;
        $set('../../costo_unitario_infraestructura', $costoUnitarioInfraestructura);
        
        // También asignar el 5% del total del costo unitario al campo costo_unitario_servicios
        $costoUnitarioServicios = $totalCostoUnitario * 0.05;
        $set('../../costo_unitario_servicios', $costoUnitarioServicios);
        
        // Actualizar el costo total de infraestructura
        $cantidadInfra = floatval($get('../../cantidad_infraestructura') ?? 0);
        $costoTotalInfra = $cantidadInfra * $costoUnitarioInfraestructura;
        $set('../../costo_total_infraestructura', number_format($costoTotalInfra, 2, '.', ''));
        
        // Actualizar el costo total de servicios
        $cantidadServ = floatval($get('../../cantidad_servicios') ?? 0);
        $costoTotalServ = $cantidadServ * $costoUnitarioServicios;
        $set('../../costo_total_servicios', number_format($costoTotalServ, 2, '.', ''));
        
        // Guardar el total en el campo total_costo_unitario también
        $set('../../total_costo_unitario', number_format($totalCostoUnitario, 2, '.', ''));
        
        // Recalcular el total del aporte institucional incluyendo infraestructura y servicios
        self::calcularTotalAporteInstitucional($get, $set);
    }
}

<?php

namespace App\Livewire\ServicioTecnologico\Secciones;

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

use Filament\Forms\Components\Grid;

class Presupuesto
{
    public static function form(): array
    {
        return [
            Fieldset::make('Ingresos')
            ->schema([
                Repeater::make('ingresos')
                    ->label('Ingresos')
                    ->schema([
                        TextInput::make('descripcion')
                            ->label('Descripción')
                            ->columnSpanFull(),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('unidad')
                                    ->label('Unidad'),

                                TextInput::make('cantidad')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $cantidad = floatval($state ?? 0);
                                        $costoUnitario = floatval($get('costo_unitario') ?? 0);
                                        $set('costo_total', number_format($cantidad * $costoUnitario, 2, '.', ''));
                                    }),

                                TextInput::make('costo_unitario')
                                    ->label('Costo unitario')
                                    ->numeric()
                                    ->prefix('L.')
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $cantidad = floatval($get('cantidad') ?? 0);
                                        $costoUnitario = floatval($state ?? 0);
                                        $set('costo_total', number_format($cantidad * $costoUnitario, 2, '.', ''));

                                        self::calcularTotalRepeater($get, $set,'ingresos', 'total_ingresos');
                                    }),

                                TextInput::make('costo_total')
                                    ->label('Costo Total')
                                    ->numeric()
                                    ->prefix('L.')
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                    ])
                    ->columns(1)
                    ->defaultItems(1)
                    ->addable()
                    ->deletable()
                    ->reorderable()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        self::calcularTotalRepeater($get, $set,'ingresos', 'total_ingresos');
                    })
                    ->columnSpanFull(),

                TextInput::make('total_ingresos')
                    ->label('Total de Ingresos')
                    ->numeric()
                    ->prefix('L.')
                    ->disabled()
                    ->live()
                    ->dehydrated()
                    ->columnSpanFull(),
            ])
            ->columns(1),


            Fieldset::make('Egresos')
                ->schema([
                    Repeater::make('egresos')
                        ->label('Egresos')
                        ->schema([
                            TextInput::make('descripcion')
                                ->label('Descripción')
                                ->columnSpanFull(),

                            Grid::make(4)
                                ->schema([
                                    TextInput::make('unidad')
                                        ->label('Unidad'),

                                    TextInput::make('cantidad')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $cantidad = floatval($state ?? 0);
                                            $costoUnitario = floatval($get('costo_unitario') ?? 0);
                                            $set('costo_total', number_format($cantidad * $costoUnitario, 2, '.', ''));
                                        }),

                                    TextInput::make('costo_unitario')
                                        ->label('Costo unitario')
                                        ->numeric()
                                        ->prefix('L.')
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $cantidad = floatval($get('cantidad') ?? 0);
                                            $costoUnitario = floatval($state ?? 0);
                                            $set('costo_total', number_format($cantidad * $costoUnitario, 2, '.', ''));

                                            self::calcularTotalRepeater($get, $set,'egresos', 'total_egresos');
                                        }),

                                    TextInput::make('costo_total')
                                        ->label('Costo Total')
                                        ->numeric()
                                        ->prefix('L.')
                                        ->disabled()
                                        ->dehydrated(),
                                ])
                        ])
                        ->columns(1)
                        ->defaultItems(1)
                        ->addable()
                        ->deletable()
                        ->reorderable()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            self::calcularTotalRepeater($get, $set,'egresos', 'total_egresos');
                        })
                        ->columnSpanFull(),

                    TextInput::make('total_egresos')
                        ->label('Total de Egresos')
                        ->numeric()
                        ->prefix('L.')
                        ->disabled()
                        ->dehydrated()
                        ->columnSpanFull(),
                ])
                ->columns(1),

            Fieldset::make('Aportes en especies de la UNAH')
                ->schema([
                    Repeater::make('aportes_unah')
                        ->label('Aportes en especies de la UNAH')
                        ->schema([
                            TextInput::make('descripcion')
                                ->label('Descripción')
                                ->columnSpanFull(),

                            Grid::make(4)
                                ->schema([
                                    TextInput::make('unidad')
                                        ->label('Unidad'),

                                    TextInput::make('cantidad')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $cantidad = floatval($state ?? 0);
                                            $costoUnitario = floatval($get('costo_unitario') ?? 0);
                                            $set('costo_total', number_format($cantidad * $costoUnitario, 2, '.', ''));
                                        }),

                                    TextInput::make('costo_unitario')
                                        ->label('Costo unitario')
                                        ->numeric()
                                        ->prefix('L.')
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $cantidad = floatval($get('cantidad') ?? 0);
                                            $costoUnitario = floatval($state ?? 0);
                                            $set('costo_total', number_format($cantidad * $costoUnitario, 2, '.', ''));

                                            self::calcularTotalRepeater($get, $set,'aportes_unah', 'total_aporte_unah');
                                        }),

                                    TextInput::make('costo_total')
                                        ->label('Costo Total')
                                        ->numeric()
                                        ->prefix('L.')
                                        ->disabled()
                                        ->dehydrated(),
                                ])
                        ])
                        ->columns(1)
                        ->defaultItems(1)
                        ->addable()
                        ->deletable()
                        ->reorderable()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            self::calcularTotalRepeater($get, $set,'aportes_unah', 'total_aporte_unah');
                        })
                        ->columnSpanFull(),

                    TextInput::make('total_aporte_unah')
                        ->label('Total aporte en especies de la UNAH')
                        ->numeric()
                        ->prefix('L.')
                        ->disabled()
                        ->dehydrated()
                        ->columnSpanFull(),
                ])
                ->columns(1),

        ];
    }

    public static function calcularTotalRepeater(Get $get, Set $set, string $repeaterField, string $totalField): void
    {
        $total = 0;
        $items = $get($repeaterField) ?? [];

        foreach ($items as $item) {
            $total += floatval($item['costo_total'] ?? 0);
        }

        $set($totalField, number_format($total, 2, '.', ''));
    }
}
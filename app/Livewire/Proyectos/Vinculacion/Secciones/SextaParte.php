<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\CargoFirma;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;

class SextaParte
{

    public static function form(): array
    {
        return [
            Repeater::make('firma_proyecto_jefe')
                ->id('firma_proyecto_1')
                ->label('Jefe de departamento')
                ->schema([
                    Select::make('empleado_id')
                        ->label('')
                        ->searchable(['nombre_completo', 'numero_empleado'])
                        ->relationship(name: 'empleado', titleAttribute: 'nombre_completo'),
                    Select::make('cargo_firma_id')
                        ->label('')
                        ->searchable()
                        ->relationship(name: 'cargo_firma', titleAttribute: 'nombre')
                        ->default(
                            CargoFirma::where('nombre', 'Jefe departamento')->first()->id
                        )
                        ->disabled()
                        ->preload(),
                    Hidden::make('cargo_firma_id')
                        ->default(
                            CargoFirma::where('nombre', 'Jefe departamento')->first()->id
                        ),
                    Hidden::make('estado_revision')
                        ->default('Pendiente'),
                    Hidden::make('estado_actual_id')
                        ->default(
                            TipoEstado::where('nombre', 'Esperando Firma de Jefe de Departamento')->first()->id
                        ),

                ])
                ->addable(false)
                ->deletable(false)
                ->relationship()
                ->columns(2),
            Repeater::make('firma_proyecto_decano')
                ->id('firma_proyecto_1')
                ->label('Decano de facultad o director de centro')
                ->schema([
                    Select::make('empleado_id')
                        ->label('')
                        ->searchable(['nombre_completo', 'numero_empleado'])
                        ->relationship(name: 'empleado', titleAttribute: 'nombre_completo'),
                    Select::make('cargo_firma_id')
                        ->label('')
                        ->searchable()
                        ->relationship(name: 'cargo_firma', titleAttribute: 'nombre')
                        ->default(
                            CargoFirma::where('nombre', 'Director centro')->first()->id
                        )
                        ->disabled()
                        ->preload(),
                    Hidden::make('cargo_firma_id')
                        ->default(
                            CargoFirma::where('nombre', 'Director centro')->first()->id
                        ),
                    Hidden::make('estado_revision')
                        ->default('Pendiente'),
                    Hidden::make('estado_actual_id')
                        ->default(
                            TipoEstado::where('nombre', 'Esperando firma de Director/Decano')->first()->id
                        ),

                ])
                ->deletable(false)
                ->addable(false)
                ->relationship()
                ->columns(2),


            // repeter para el firma_proyecto_enlace
            Repeater::make('firma_proyecto_enlace')
                ->label('Enlace de Vinculación')
                ->schema([


                    Select::make('empleado_id')
                        ->label('')
                        ->searchable(['nombre_completo', 'numero_empleado'])
                        ->relationship(name: 'empleado', titleAttribute: 'nombre_completo'),
                    Select::make('cargo_firma_id')
                        ->label('')
                        ->searchable()
                        ->relationship(name: 'cargo_firma', titleAttribute: 'nombre')
                        ->default(
                            CargoFirma::where('nombre', 'Enlace Vinculacion')->first()->id
                        )
                        ->disabled()
                        ->preload(),
                    Hidden::make('cargo_firma_id')
                        ->default(
                            CargoFirma::where('nombre', 'Enlace Vinculacion')->first()->id
                        ),
                    Hidden::make('estado_revision')
                        ->default('Pendiente'),
                    Hidden::make('estado_actual_id')
                        ->default(
                            TipoEstado::where('nombre', 'Esperando Firma de Vinculacion')->first()->id
                        ),

                ])
                ->deletable(false)
                ->addable(false)
                ->relationship()
                ->columns(2),

        ];
    }
}

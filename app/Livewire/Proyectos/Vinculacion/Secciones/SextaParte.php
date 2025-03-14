<?php

namespace App\Livewire\Proyectos\Vinculacion\Secciones;

use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\CargoFirma;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;

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
                        ->required()
                        ->relationship(name: 'empleado', titleAttribute: 'nombre_completo'),
                   

                    Hidden::make('cargo_firma_id')
                        ->default(
                            CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                                ->where('tipo_cargo_firma.nombre', 'Jefe Departamento')
                                ->first()->id
                        ),



                ])
                ->addable(false)
                ->deletable(false)
                ->relationship(),
            Repeater::make('firma_proyecto_decano')
                ->id('firma_proyecto_1')
                ->label('Decano de facultad o director de centro')
                ->schema([
                    Select::make('empleado_id')
                        ->label('')
                        ->required()
                        ->searchable(['nombre_completo', 'numero_empleado'])
                        ->relationship(name: 'empleado', titleAttribute: 'nombre_completo'),
                    Hidden::make('cargo_firma_id')
                        ->default(
                            CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                                ->where('tipo_cargo_firma.nombre', 'Director centro')
                                ->first()->id
                        ),


                ])
                ->deletable(false)
                ->addable(false)
                ->relationship(),


            // repeter para el firma_proyecto_enlace
            Repeater::make('firma_proyecto_enlace')
                ->label('Enlace de Vinculación')
                ->schema([
                    Select::make('empleado_id')
                        ->label('')
                        ->required()
                        ->searchable(['nombre_completo', 'numero_empleado'])
                        ->relationship(name: 'empleado', titleAttribute: 'nombre_completo'),
                    Hidden::make('cargo_firma_id')
                        ->default(
                            CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                                ->where('tipo_cargo_firma.nombre', 'Enlace Vinculacion')
                                ->first()->id
                        ),

                ])
                ->deletable(false)
                ->addable(false)
                ->relationship(),

        ];
    }
}

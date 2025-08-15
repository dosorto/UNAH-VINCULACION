<?php

namespace App\Livewire\ServicioTecnologico\Secciones;

use Filament\Forms;
use Filament\Forms\Get;

use Filament\Forms\Components\Select;


use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;

use App\Models\Demografia\Municipio;
use App\Models\Demografia\Departamento;
use App\Models\Demografia\Ciudad;
use App\Models\Demografia\Aldea;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Set;

class CuartaParte
{
    public static function form(): array
    {
        return [
            Forms\Components\Fieldset::make('En esta sección detallará todos los espacios o servicios de la UNAH que utilizará para el desarrollo de la actividad, tales como: uso de laboratorios, aulas, auditorios, etc.')
        ->schema([
            Forms\Components\Textarea::make('descripción_ser_infraestructura')
                ->label('Descripción del servicio de infraestructura')
                ->cols(30)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('ubicacion')
                ->label('Ubicación')
                ->rows(5)
                ->cols(20)
                ->required()
                ->columnSpanFull(),
            Forms\Components\Textarea::make('unidad_gestora')
                ->label('Unidad Gestora')
                ->rows(5)
                ->cols(20)
                ->required()
                ->columnSpanFull(),
        ])
        ->columnSpanFull(),
        ];
    }
}

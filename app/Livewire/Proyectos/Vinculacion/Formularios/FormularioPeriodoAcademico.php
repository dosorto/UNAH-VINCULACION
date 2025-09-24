<?php

namespace App\Livewire\Proyectos\Vinculacion\Formularios;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Models\PeriodoAcademico;

class FormularioPeriodoAcademico
{
    public static function form(): array
    {
        return [
            TextInput::make('nombre')
                ->label('Nombre del Período')
                ->required()
                ->maxLength(255),
        ];
    }
}
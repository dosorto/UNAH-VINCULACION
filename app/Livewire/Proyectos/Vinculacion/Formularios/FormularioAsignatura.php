<?php

namespace App\Livewire\Proyectos\Vinculacion\Formularios;

use App\Models\Asignatura;
use App\Models\PeriodoAcademico;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class FormularioAsignatura
{
    public static function form(): array
    {
        return [
            TextInput::make('codigo')
                ->label('Código de la Asignatura')
                ->required()
                ->unique('asignaturas', 'codigo')
                ->maxLength(10),

            TextInput::make('nombre')
                ->label('Nombre de la Asignatura')
                ->required()
                ->maxLength(255),
          
        ];
    }
}
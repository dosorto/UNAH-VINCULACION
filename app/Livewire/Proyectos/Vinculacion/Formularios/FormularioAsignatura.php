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
                ->placeholder('ej: HH-101, EG-101')
                ->required()
                ->unique('asignaturas', 'codigo')
                ->live()
                ->maxLength(10)
                ->rules([
                    fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                        if (!preg_match('/^[A-Z]{2}-\d{3}$/', $value)) {
                            $fail('El código debe contener dos letras mayúsculas, un guión y tres números (por ejemplo: HH-101, EG-101).');
                        }
                    },
                ]),

            TextInput::make('nombre')
                ->label('Nombre de la Asignatura')
                ->required()
                ->maxLength(255),
          
        ];
    }
}
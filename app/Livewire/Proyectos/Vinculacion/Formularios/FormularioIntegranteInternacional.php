<?php

namespace App\Livewire\Proyectos\Vinculacion\Formularios;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class FormularioIntegranteInternacional
{
    public static function form(): array
    {
        return [
            TextInput::make('nombre_completo')
                ->label('Nombre Completo')
                ->required()
                ->maxLength(255)
                ->columnSpan(1),

            TextInput::make('documento_identidad')
                ->label('Pasaporte')
                ->required()
                ->maxLength(50)
                ->columnSpan(1),

            Select::make('sexo')
                ->label('Sexo')
                ->options([
                    'masculino' => 'Masculino',
                    'femenino' => 'Femenino',
                ])
                ->required()
                ->columnSpan(1),    

            TextInput::make('email')
                ->label('Correo Electrónico')
                ->email()
                ->required()
                ->unique()
                ->maxLength(255)
                ->columnSpan(1),

            TextInput::make('pais')
                ->label('País')
                ->required()
                ->maxLength(100)
                ->columnSpan(1),

            TextInput::make('institucion')
                ->label('Universidad/Institución')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
        ];
    }
}

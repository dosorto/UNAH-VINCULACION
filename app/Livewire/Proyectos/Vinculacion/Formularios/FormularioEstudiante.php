<?php

namespace App\Livewire\Proyectos\Vinculacion\Formularios;

use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\FacultadCentro;

use Filament\Forms\Components\Select;

use Filament\Forms\Components\TextInput;

class FormularioEstudiante
{
    public static function form(): array
    {
        return [
            TextInput::make('email')->label('Correo electrónico Académico del Estudiante')->required()->unique('users', 'email')->email()->required(),
            TextInput::make('nombre')->label('Nombre ')->required(),
            TextInput::make('apellido')->label('Apellidos del Estudiante')->required(),
            Select::make('centro_facultad_id')
                ->label('Facultad o Centro')
                ->searchable()
                ->required()
                ->options(FacultadCentro::all()->pluck('nombre', 'id'))
                ->preload(),
            Select::make('carrera_id')
                ->label('Carrera')
                ->searchable()
                ->required()
                ->options(Carrera::all()->pluck('nombre', 'id'))
                ->preload(),
            TextInput::make('numero_cuenta')->maxLength(255)->label('Número de cuenta del Estudiante')->required()->unique('estudiante', 'cuenta')->numeric()->required(),
        ];
    }
}

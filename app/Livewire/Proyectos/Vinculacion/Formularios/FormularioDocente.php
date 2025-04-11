<?php

namespace App\Livewire\Proyectos\Vinculacion\Formularios;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;

class FormularioDocente
{
    public static function form(): array
    {
        return [
            // campus o facultad del empleado

            Select::make('centro_facultad_id')
                ->label('Facultad o Centro')
                ->searchable()
                ->relationship(name: 'centro_facultad', titleAttribute: 'nombre')
                ->required()
                ->afterStateUpdated(function (?string $state, ?string $old, Set $set) {
                    $set('departamento_academico_id', null);
                })
                ->live()
                ->preload(),

            // departamento academico del empleado

            Select::make('departamento_academico_id')->label('Departamento Académico')->searchable()->live()->preload()->visible(fn(Get $get): bool => !is_null($get('centro_facultad_id')))->relationship(name: 'departamento_academico', titleAttribute: 'nombre', modifyQueryUsing: fn($query, Get $get) => $query->where('centro_facultad_id', $get('centro_facultad_id')))->required(),

            Forms\Components\TextInput::make('nombre_completo')->minLength(2)->maxLength(255)->label('Nombre completo')->required(),

            Forms\Components\TextInput::make('numero_empleado')->minLength(2)->maxLength(255)->label('Número de empleado')->unique('empleado', 'numero_empleado')->required(),

            Select::make('categoria_id')->label('Categoría')->relationship(name: 'categoria', titleAttribute: 'nombre')->required()->preload(),

            Forms\Components\TextInput::make('email')->minLength(2)->maxLength(255)->label('Correo electrónico ')->required()->email()->unique('users', 'email')->email(), //->required(),

            Forms\Components\TextInput::make('celular')->minLength(2)->maxLength(255)->label('Celular')->required(),
        ];
    }
}

<?php

namespace App\Livewire\Personal\Empleado\Formularios;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\CheckboxList;


final class FormularioEmpleado
{
    public static function form(bool $disableTipoEmpleado = false): array
    {
        return [
            Section::make('user')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre de Usuario')
                        ->required()
                        ->unique('users', 'name', ignoreRecord: true)
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Correo Electrónico')
                        ->disabled($disableTipoEmpleado)
                        ->required()
                        ->unique('users', 'email', ignoreRecord: true)
                        ->email()
                        ->maxLength(255),
                ])
                ->columnSpanFull(),
            Section::make('Empleado')
                ->schema([
                   /* Select::make('tipo_empleado')
                        ->label('Tipo Empleado')
                        ->options([
                            'administrativo' => 'Administrativo',
                            'docente' => 'Docente',
                        ])
                        ->live()
                        ->disabled($disableTipoEmpleado)
                        ->required(),*/
                    TextInput::make('nombre_completo')
                        ->label('Nombre Completo')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('numero_empleado')
                        ->label('Número de Empleado')
                        ->unique('empleado', 'numero_empleado', ignoreRecord: true)
                        ->required()
                        ->numeric()
                        ->maxLength(255),
                    TextInput::make('celular')
                        ->required()
                        ->numeric()
                        ->maxLength(255),
                    Select::make('categoria_id')
                        ->label('Categoría')
                        ->relationship('categoria', 'nombre')
                        ->visible(fn(Get $get) => $get('tipo_empleado') === 'docente')
                        ->required(),
                    Select::make('empleado.centro_facultad_id')
                        ->label('Facultades o Centros')
                        ->searchable()
                        ->live()
                        ->relationship(name: 'centro_facultad', titleAttribute: 'nombre')
                        ->afterStateUpdated(function (Set $set) {
                            $set('empleado.departamento_academico_id', null);
                        })
                        ->required()
                        ->preload(),
                    Select::make('empleado.departamento_academico_id')
                        ->label('Departamentos Académicos')
                        ->searchable()
                        ->relationship(
                            name: 'departamento_academico',
                            titleAttribute: 'nombre',
                            modifyQueryUsing: fn($query, Get $get) => $query->where('centro_facultad_id', $get('empleado.centro_facultad_id'))
                        )
                        ->visible(fn(Get $get) => !empty($get('empleado.centro_facultad_id')
                            && $get('tipo_empleado') === 'docente'))
                        ->live()
                        ->required()
                        ->preload(),

                ])
                ->relationship('empleado')
                ->columns(2),
            Section::make('Roles')
            ->visible(!$disableTipoEmpleado)    
            ->schema([
                    CheckboxList::make('Roles')
                        ->label('Roles')
                        ->columns(3)
                        ->relationship(name: 'roles', titleAttribute: 'name')

                ])

                ->columnSpanFull(),
                ];
        
    }
    
}


<?php

namespace App\Livewire\Estudiante;

use App\Models\Estudiante\Estudiante;
use App\Models\User;
use App\Models\UnidadAcademica\Carrera;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Validation\Rule;

class ListarEstudiante extends TableComponent
{
    public function table(Table $table): Table
    {
        return $table
            ->heading('Estudiantes')
            ->query(
                Estudiante::query()
                    ->with(['user', 'proyectosEstudiante.proyecto']) // Cargar relaciones necesarias
            )
            ->columns([
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('apellido')->label('Apellido')->searchable()->sortable(),
                TextColumn::make('cuenta')->label('Cuenta')->searchable(),
                TextColumn::make('proyectosEstudiante.proyecto.nombre_proyecto')
                    ->label('Nombre del Proyecto')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('proyectosEstudiante.tipo_participacion_estudiante')
                    ->label('Tipo de Participación')
                    ->sortable()
                    ->searchable(),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Editar Estudiante')
                    ->modelLabel('Estudiante')
                    ->form(fn(EditAction $action): array => [
                        Section::make('Usuario Estudiante')
                            ->schema([
                                TextInput::make('nombre_usuario')
                                    ->label('Nombre de Usuario')
                                    ->default(fn ($record) => $record->user?->name)
                                    ->required()
                                    ->maxLength(255)
                                    ->rule(function ($record) {
                                        return Rule::unique('users', 'name')->ignore($record?->user?->id);
                                    }),

                                TextInput::make('correo_electronico')
                                    ->label('Correo Electrónico')
                                    ->default(fn ($record) => $record->user?->email)
                                    ->required()
                                    ->email()
                                    ->maxLength(255)
                                    ->rule(function ($record) {
                                        return Rule::unique('users', 'email')->ignore($record?->user?->id);
                                    }),
                            ])
                            ->columnSpanFull(),

                        Section::make('Datos de Estudiante')
                            ->schema([
                                TextInput::make('nombre')
                                    ->label('Nombres')
                                    ->default(fn ($record) => $record->nombre)
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('apellido')
                                    ->label('Apellidos')
                                    ->default(fn ($record) => $record->apellido)
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('cuenta')
                                    ->label('Número de Cuenta')
                                    ->default(fn ($record) => $record->cuenta)
                                    ->required()
                                    ->numeric()
                                    ->maxLength(255)
                                    ->unique('estudiante', 'cuenta', ignoreRecord: true),

                                Select::make('centro_facultad_id')
                                    ->label('Facultades o Centros')
                                    ->relationship(name: 'centro_facultad', titleAttribute: 'nombre')
                                    ->searchable()
                                    ->live()
                                    ->required()
                                    ->preload(),
                            ])
                            ->columns(2),
                    ])
                    ->mutateRecordDataUsing(function (Estudiante $record): array {
                        return [
                            'nombre_usuario' => $record->user?->name,
                            'correo_electronico' => $record->user?->email,
                            'nombre' => $record->nombre,
                            'apellido' => $record->apellido,
                            'cuenta' => $record->cuenta,
                            'centro_facultad_id' => $record->centro_facultad_id,
                        ];
                    })
                    ->using(function (Estudiante $record, array $data): void {
                        // Actualizar datos del estudiante
                        $record->update([
                            'nombre' => $data['nombre'],
                            'apellido' => $data['apellido'],
                            'cuenta' => $data['cuenta'],
                            'centro_facultad_id' => $data['centro_facultad_id'],
                        ]);

                        // Actualizar datos del usuario relacionado
                        if ($record->user) {
                            $record->user->update([
                                'name' => $data['nombre_usuario'],
                                'email' => $data['correo_electronico'],
                            ]);
                        }
                    }),
            ]);
    }

    public function render()
    {
        return view('livewire.estudiante.listar-estudiante');
    }
}

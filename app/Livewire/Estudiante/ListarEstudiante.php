<?php

namespace App\Livewire\Estudiante;

use App\Models\Estudiante\Estudiante;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;

class ListarEstudiante extends TableComponent
{
    public function table(Table $table): Table
    {
        return $table
            ->heading('Estudiantes')
            ->query(
                Estudiante::query()
                    ->with(['participacionesProyectos.proyecto']) 
            )
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('apellido')
                    ->label('Apellido')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cuenta')
                    ->label('Cuenta')
                    ->searchable(),

                TextColumn::make('participacionesProyectos.proyecto.nombre_proyecto') 
                    ->label('Nombre del Proyecto')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('participacionesProyectos.tipo_participacion_estudiante') 
                    ->label('Tipo de Participación')
                    ->sortable()
                    ->searchable(),
            ]);
    }

    public function render()
    {
        return view('livewire.estudiante.listar-estudiante');
    }
}

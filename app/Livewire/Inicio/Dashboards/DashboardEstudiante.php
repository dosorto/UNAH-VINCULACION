<?php

namespace App\Livewire\Inicio\Dashboards;

use App\Models\Proyecto\Proyecto;
use App\Models\Estudiante\EstudianteProyecto;
use Filament\Tables;
use Filament\Tables\TableComponent;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;

class DashboardEstudiante extends TableComponent
{
    public function table(Table $table): Table
    {
        $estudiante = auth()->user()->estudiante; // Obtén el estudiante asociado al usuario

        if (!$estudiante) {
            return $table->heading('No hay estudiante asociado.');
        }

        $query = Proyecto::query()
            ->with('estudianteProyecto')
            ->whereHas('estudianteProyecto', function ($query) use ($estudiante) {
                $query->where('estudiante_id', $estudiante->id);
            });

        return $table
            ->heading('Mis Proyectos')
            ->query($query)
            ->columns([
                TextColumn::make('nombre_proyecto')
                    ->label('Nombre del Proyecto')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('estudianteProyecto.tipo_participacion_estudiante')
                    ->label('Tipo de Participación')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('fecha_inicio')
                    ->label('Fecha de Inicio')
                    ->date()
                    ->sortable(),
                TextColumn::make('fecha_finalizacion')
                    ->label('Fecha de Finalización')
                    ->date()
                    ->sortable(),
            ])
            ->actions([
                Action::make('Constancia')
                    ->label('Descargar Constancia')
                    ->icon('heroicon-o-arrow-down-tray'),
            ]);
    }

    public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver
    {
        return null;
    }

    public function render()
    {
        return view('livewire.inicio.dashboards.dashboard-estudiante');
    }
}
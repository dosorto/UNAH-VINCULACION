<?php

namespace App\Livewire\Inicio\Dashboards;

use App\Models\Proyecto\Proyecto;
use App\Models\Estudiante\TipoParticipacion;
use App\Models\Estudiante\Estudiante;
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
        return $table
            ->heading('Mis Proyectos')
            ->query(
                Proyecto::query()
                    ->whereHas('tipoParticipacion', function ($query) {
                        $query->where('estudiante_id', auth()->user()->id);
                    })
                    ->with(['tipoParticipacion'])
            )
            ->columns([
                TextColumn::make('nombre_proyecto')
                    ->label('Nombre del Proyecto')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('tipoParticipacion.nombre')
                    ->label('Tipo de Participación')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('fecha_finalizacion')
                    ->label('Fecha de Finalización')
                    ->date()
                    ->sortable(),
            ])
            ->actions([
                Action::make('Constancia')
                    ->label('Descargar Constancia')
                    ->icon('heroicon-o-arrow-down-tray')
                    //->icon('heroicon-o-download')
                    //->url(fn ()),
                    //->openUrlInNewTab(),
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

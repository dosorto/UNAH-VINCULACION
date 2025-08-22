<?php

namespace App\Livewire\Inicio\Dashboards;

use App\Models\Proyecto\Proyecto;
use App\Models\Estudiante\EstudianteProyecto;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\TableComponent;
use Illuminate\Database\Eloquent\Builder;

class DashboardEstudiante extends TableComponent
{
    use Tables\Concerns\InteractsWithTable;

    protected function getTableQuery(): Builder
    {
        $estudiante = auth()->user()->estudiante;

        if (!$estudiante) {
            return Proyecto::query()->whereNull('id'); 
        }

        return Proyecto::query()
            ->with('estudianteProyecto')
            ->whereHas('estudianteProyecto', function ($query) use ($estudiante) {
                $query->where('estudiante_id', $estudiante->id);
            });
    }

    protected function getTableColumns(): array
    {
        return [
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
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('Constancia')
                ->label('Descargar Constancia')
                ->icon('heroicon-o-arrow-down-tray'),
        ];
    }

    protected function getTableHeading(): string
    {
        $estudiante = auth()->user()->estudiante;
        
        return $estudiante ? 'Mis Proyectos' : 'No hay estudiante asociado.';
    }

    public function render()
    {
        return view('livewire.inicio.dashboards.dashboard-estudiante');
    }
}
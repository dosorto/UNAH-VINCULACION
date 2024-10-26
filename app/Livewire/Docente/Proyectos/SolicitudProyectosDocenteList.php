<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;

class SolicitudProyectosDocenteList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    public Empleado $docente;

    public function mount(Empleado $docente): void
    {
        $this->docente = $docente;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Proyecto::query()
                    ->join('estado_proyecto', 'estado_proyecto.proyecto_id', '=', 'proyecto.id')
                    ->join('tipo_estado', 'tipo_estado.id', '=', 'estado_proyecto.tipo_estado_id')
                    ->select(
                        'proyecto.id AS proyecto_id',
                        'proyecto.nombre_proyecto',
                        'proyecto.fecha_inicio',
                        'proyecto.fecha_finalizacion',
                        'proyecto.objetivos_especificos',
                        'tipo_estado.nombre AS nombre_estado'
                    )
                    ->where('tipo_estado.id', 1)
                    ->distinct()
            )
            ->columns([
                Tables\Columns\TextColumn::make('proyecto_id')
                    ->label('ID Proyecto')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nombre_proyecto')
                    ->searchable(),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_finalizacion')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('objetivos_especificos')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nombre_estado')
                    ->label('Estado')
                    ->searchable()
                    ->color('success') // Cambia 'blue' por el color que desees
                    ->badge(), // Esto aplicará el estilo de badge al texto
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->modalContent(fn(Proyecto $proyecto): View => 
                        view('components.fichas.ficha-proyecto', ['proyecto' => $proyecto])
                    )
                    ->modalWidth('800px')
                    ->modalSubmitAction(false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->proyecto_id; // Asegúrate de que 'proyecto_id' es único
    }

    public function render(): View
    {
        return view('livewire.docente.proyectos.solicitud-proyectos-docente-list');
    }
}

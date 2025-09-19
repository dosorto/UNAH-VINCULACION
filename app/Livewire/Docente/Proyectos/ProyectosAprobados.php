<?php

namespace App\Livewire\Docente\Proyectos;

use Filament\Tables;
use Livewire\Component;
use Filament\Tables\Table;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class ProyectosAprobados extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Empleado $docente;

    public function mount()
    {
        $this->docente = auth()->user()->empleado;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->docente->firmaProyectoAprobado()
                    ->getQuery()
            )
            ->columns([
                Tables\Columns\TextColumn::make('proyecto.nombre_proyecto')
                    ->label('Nombre del Proyecto')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('cargo_firma.tipoCargoFirma.nombre')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->wrap()
                    ->label('Cargo de firma')
                    ->searchable(),

                Tables\Columns\TextColumn::make('estado_revision')
                    ->label('Estado Firma')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cargo_firma.descripcion')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->wrap()
                    ->label('Tipo Firma')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.docente.proyectos.proyectos-aprobados');
        // ->layout('components.panel.modulos.modulo-firmas-docente');
    }
}

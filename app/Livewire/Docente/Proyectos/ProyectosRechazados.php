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

class ProyectosRechazados extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Empleado $docente;

    public function mount() {
        // dd(auth()->user()->empleado);
        $this->docente = auth()->user()->empleado;
        // dd($this->docente);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Proyecto::query()
                ->whereIn('id', $this->docente->firmaProyectoRechazado->pluck('proyecto_id')->toArray())
            )
            ->columns([
                //
                Tables\Columns\TextColumn::make('nombre_proyecto')
                    ->searchable(),

                Tables\Columns\TextColumn::make('departamentos_academicos.nombre')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->wrap()
                    ->label('Departamento')
                    ->searchable(),

                Tables\Columns\TextColumn::make('facultades_centros.nombre')
                    ->badge()
                    ->wrap()
                    ->label('Centro/Facultad'),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->date()
                    ->sortable(),
                    Tables\Columns\TextColumn::make('firma_proyecto_cargo.cargo_firma.nombre')
                        ->label('cargo')
                        ->sortable(),
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
        return view('livewire.docente.proyectos.proyectos-rechazados');
    }
}

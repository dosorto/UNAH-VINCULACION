<?php

namespace App\Livewire\Estudiante;

use App\Models\Estudiante;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;

class ListarEstudiante extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Estudiantes y Proyectos')
            ->query(Estudiante::with(['centroFacultad', 'departamentoAcademico', 'estudianteProyectos.proyecto']))
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombres')
                    ->searchable(),

                TextColumn::make('apellido')
                    ->label('Apellidos')
                    ->searchable(),

                TextColumn::make('cuenta')
                    ->label('Número de Cuenta')
                    ->searchable(),

                TextColumn::make('centroFacultad.nombre')
                    ->label('Facultad o Centro'),

                TextColumn::make('departamentoAcademico.nombre')
                    ->label('Departamento Académico'),

                TextColumn::make('estudianteProyectos.proyecto.nombre')
                    ->label('Proyecto')
                    ->sortable(),

                TextColumn::make('estudianteProyectos.tipo_participacion_estudiante')
                    ->label('Tipo de Participación')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        TextColumn::make('nombre')
                            ->label('Nombres'),
                        TextColumn::make('apellido')
                            ->label('Apellidos'),
                        TextColumn::make('cuenta')
                            ->label('Número de Cuenta'),
                    ]),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.estudiante.listar-estudiante')
            ->layout('layouts.app');
    }
}

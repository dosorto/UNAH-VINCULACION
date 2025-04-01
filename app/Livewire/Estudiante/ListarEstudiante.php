<?php

namespace App\Livewire\Estudiante;

use App\Models\Estudiante\Estudiante;
use App\Models\Proyecto\Proyecto;
use App\Models\Estudiante\TipoParticipacion;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class ListarEstudiante extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Estudiantes')
            ->query(
                Estudiante::query()
                    ->with(['participacionesProyectos.proyecto', 'participacionesProyectos.tipoParticipacion'])
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
                    ->label('Número de Cuenta')
                    ->searchable(),
                TextColumn::make('participacionesProyectos.proyecto.nombre_proyecto')
                    ->label('Proyecto')
                    ->formatStateUsing(function ($state, Estudiante $record) {
                        return $record->participacionesProyectos
                            ->map(fn($part) => $part->proyecto?->nombre_proyecto)
                            ->filter()
                            ->unique()
                            ->join(', ');
                    }),
                TextColumn::make('participacionesProyectos.tipoParticipacion.nombre')
                    ->label('Tipo de Participación')
                    ->formatStateUsing(function ($state, Estudiante $record) {
                        return $record->participacionesProyectos
                            ->map(fn($part) => $part->tipoParticipacion?->nombre)
                            ->filter()
                            ->unique()
                            ->join(', ');
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                DeleteAction::make(),
                EditAction::make()
                ->mutateRecordDataUsing(function (Estudiante $record) {
                    $participacion = $record->participacionesProyectos()->first();
            
                    return [
                        'nombre' => $record->nombre,
                        'apellido' => $record->apellido,
                        'cuenta' => $record->cuenta,
                        'proyecto_id' => $participacion?->proyecto_id,
                        'tipo_participacion_id' => $participacion?->tipo_participacion_id,
                    ];
                })
                ->form([
                    TextInput::make('nombre')
                        ->label('Nombre')
                        ->required(),
                    TextInput::make('apellido')
                        ->label('Apellido')
                        ->required(),
                    TextInput::make('cuenta')
                        ->label('Número de Cuenta')
                        ->required()
                        ->numeric(),
                    Select::make('proyecto_id')
                        ->label('Proyecto')
                        ->options(Proyecto::pluck('nombre_proyecto', 'id'))
                        ->default(fn ($record) => $record->participacionesProyectos()->first()?->proyecto_id),
                    Select::make('tipo_participacion_id')
                        ->label('Tipo de Participación')
                        ->options(TipoParticipacion::pluck('nombre', 'id'))
                        ->default(fn ($record) => $record->participacionesProyectos()->first()?->tipo_participacion_id),
                ])
                ->mutateFormDataUsing(function (array $data) {
                    return collect($data)->only(['nombre', 'apellido', 'cuenta', 'proyecto_id', 'tipo_participacion_id'])->toArray();
                })
                ->action(function (Estudiante $record, array $data) {
                    // Actualizar datos en la tabla `estudiantes`
                    $record->update([
                        'nombre' => $data['nombre'],
                        'apellido' => $data['apellido'],
                        'cuenta' => $data['cuenta'],
                    ]);
            
                    // Actualizar datos en la tabla pivote `estudiante_proyecto`
                    $record->participacionesProyectos()->updateOrCreate(
                        ['estudiante_id' => $record->id], // Condición de búsqueda
                        [
                            'proyecto_id' => $data['proyecto_id'],
                            'tipo_participacion_id' => $data['tipo_participacion_id'],
                        ]
                    );
                })
            
                ]);
    }         
    public function render(): View
    {
        return view('livewire.estudiante.listar-estudiante');
    }
}
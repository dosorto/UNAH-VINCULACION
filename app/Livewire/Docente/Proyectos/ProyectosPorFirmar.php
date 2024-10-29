<?php

namespace App\Livewire\Docente\Proyectos;

use Filament\Tables;
use Livewire\Component;
use Filament\Tables\Table;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\View\View;
use App\Models\Proyecto\FirmaProyecto;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class ProyectosPorFirmar extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Empleado $docente;

    public function mount() {
        // dd($this->docente->firmaProyectoPendiente->pluck('id')->toArray());
        // $model = Proyecto::where('id', 2)->first();
        // dd($model->firma_proyecto_cargo);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Proyecto::query()
                    ->whereIn('id', $this->docente->firmaProyectoPendiente->pluck('proyecto_id')->toArray())
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
                Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle') // Icono para "Aprobar"
                    ->color('success') 
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Aprobación') // Título del diálogo
                    ->modalSubheading('¿Estás seguro de que deseas aprobar la firma de este proyecto?')
                    ->action(function (Proyecto $proyecto) {
                        // dd($this->docente);
                        $firma_proyecto = FirmaProyecto::where('proyecto_id', $proyecto->id)
                                                ->where('empleado_id', $this->docente->id)
                                                ->first();
                        $firma_proyecto->update(['estado_revision' => 'Aprobado']);
                        // dd(FirmaProyecto::where('proyecto_id', $proyecto->id)
                        // ->where('empleado_id', $this->docente->id)
                        // ->first());
                        Notification::make()
                            ->title('¡Realizado!')
                            ->body('Proyecto Aprobado correctamente')
                            ->info()
                            ->send();
                    })
                    ->button(),

                    Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle') // Icono para "Rechazar"
                    ->color('danger') 
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Rechazo') // Título del diálogo
                    ->modalSubheading('¿Estás seguro de que deseas Rechazar la firma de este proyecto?')
                    ->action(function (Proyecto $proyecto) {
                        // dd($this->docente);
                        $firma_proyecto = FirmaProyecto::where('proyecto_id', $proyecto->id)
                                                ->where('empleado_id', $this->docente->id)
                                                ->first();
                        $firma_proyecto->update(['estado_revision' => 'Rechazado']);
                        // dd(FirmaProyecto::where('proyecto_id', $proyecto->id)
                        // ->where('empleado_id', $this->docente->id)
                        // ->first());
                        Notification::make()
                            ->title('¡Realizado!')
                            ->body('Proyecto Rechazado')
                            ->info()
                            ->send();
                    })
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.docente.proyectos.proyectos-por-firmar');
    }
}
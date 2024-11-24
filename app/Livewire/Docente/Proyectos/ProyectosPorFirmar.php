<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
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
use Filament\Support\Enums\MaxWidth;

use Filament\Forms\Components\Textarea;

class ProyectosPorFirmar extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Empleado $docente;

    public function mount()
    {
        // dd($this->docente->firmaProyectoPendiente->pluck('id')->toArray());
        // $model = Proyecto::where('id', 2)->first();
        // dd($model->firma_proyecto_cargo);
    }



    public function table(Table $table): Table
    {

        return $table
            ->query(
                $this->docente->firmaProyectoPendientes()
                    ->getQuery()
            )
            ->columns([
                //
                Tables\Columns\TextColumn::make('proyecto.nombre_proyecto')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cargo_firma.nombre')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->wrap()
                    ->label('Cargo de firma')
                    ->searchable(),

                Tables\Columns\TextColumn::make('estado_revision')
                    ->label('Estado Firma')
                    ->searchable(),

                Tables\Columns\TextColumn::make('proyecto.estado.tipoestado.nombre')
                    ->badge()
                    ->label('Estado Proyecto')
                    ->searchable(),

                    Tables\Columns\TextColumn::make('created_at')
                    ->badge()
                    ->label('Time staps')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([

                Action::make('first')
                    ->label('Ver')
                    ->modalContent(
                        fn(FirmaProyecto $firmaProyecto): View =>
                        view(
                            'components.fichas.ficha-proyecto-vinculacion',
                            ['proyecto' => $firmaProyecto->proyecto]
                        )
                    )
                    ->stickyModalFooter()
                    ->stickyModalHeader()
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->modalSubmitAction(false)
                    ->extraModalFooterActions([
                        Action::make('second')
                            ->label('Rechazar')
                            ->form([
                                Textarea::make('comentario')
                                    ->label('Comentario')
                                    ->columnSpanFull(),
                            ])
                            ->icon('heroicon-o-x-circle') // Icono para "Rechazar"
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading('Confirmar Rechazo') // Título del diálogo

                            ->modalSubheading('¿Estás seguro de que deseas Rechazar la firma de este proyecto?')
                            ->action(function (FirmaProyecto $firma_proyecto,  array $data) {

                                // cambiar el estado de la firma a rechazado y agregar el comentario
                                $firma_proyecto->proyecto->firma_proyecto()->update([
                                    'estado_revision' => 'Pendiente',
                                    'firma_id' => null,
                                    'sello_id' => null,
                                ]);

                                // actualizar el estado del proyecto al siguiente estado :)
                                $firma_proyecto->proyecto->estado_proyecto()->create([
                                    'empleado_id' => $this->docente->id,
                                    'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')->first()->id,
                                    'fecha' => now(),
                                    'comentario' => $data['comentario'],
                                ]);




                                // dd(FirmaProyecto::where('proyecto_id', $proyecto->id)
                                // ->where('empleado_id', $this->docente->id)
                                // ->first());
                                Notification::make()
                                    ->title('¡Realizado!')
                                    ->body('Proyecto Rechazado')
                                    ->info()
                                    ->send();
                            })
                            ->cancelParentActions()
                            ->button(),

                        Action::make('aprobar')
                            ->label('Aprobar')
                            ->cancelParentActions()
                            ->icon('heroicon-o-check-circle') // Icono para "Aprobar"
                            ->color('success')
                            ->requiresConfirmation()
                            ->modalHeading('Confirmar Aprobación') // Título del diálogo
                            ->modalSubheading('¿Estás seguro de que deseas aprobar la firma de este proyecto?')
                            ->action(function (FirmaProyecto $firma_proyecto) {
                                // dd($this->docente);
                                $firma_proyecto->update([
                                    'estado_revision' => 'Aprobado',
                                    'firma_id' => $this->docente->firma->id,
                                    'sello_id' => $this->docente->sello->id,
                                ]);

                                // actualizar el estado del proyecto al siguiente estado :)
                                $firma_proyecto->proyecto->estado_proyecto()->create([
                                    'empleado_id' => $this->docente->id,
                                    'tipo_estado_id' => $firma_proyecto->cargo_firma->estadoProyectoSiguiente->id,
                                    'fecha' => now(),
                                    'comentario' => 'Se ha aprobado la firma del proyecto',
                                ]);

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
                    ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.docente.proyectos.proyectos-por-firmar')
            ->layout('components.panel.modulos.modulo-firmas-docente');
    }
}

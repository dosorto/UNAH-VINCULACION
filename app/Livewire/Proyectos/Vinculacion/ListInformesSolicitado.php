<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Http\Controllers\Docente\VerificarConstancia;
use App\Models\Proyecto\DocumentoProyecto;
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
use App\Models\Proyecto\Proyecto;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;



use Filament\Notifications\Notification;
use App\Models\Estado\TipoEstado;


use Illuminate\Support\Facades\Auth;

use Filament\Forms\Components\Textarea;
use League\CommonMark\Node\Block\Document;

class ListInformesSolicitado extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DocumentoProyecto::query()
                    ->whereIn('id', function ($query) {
                        $query->select('estadoable_id')
                            ->from('estado_proyecto')
                            ->where('estadoable_type', DocumentoProyecto::class)
                            ->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision')->first()->id)
                            ->where('es_actual', true);
                    })
            )
            ->columns([
                TextColumn::make('proyecto.nombre_proyecto')
                    ->label('Nombre del Proyecto')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('tipo_documento')
                    ->label('Tipo de informe')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('estado.tipoestado.nombre')
                    ->label('Estado')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([

                Action::make('first')
                    ->label('Ver')
                    ->modalContent(
                        fn(DocumentoProyecto $documentoProyecto) =>  view(
                            'components.fichas.informe',
                            ['documentoProyecto' => $documentoProyecto]
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
                            ->action(function (DocumentoProyecto $documentoProyecto,  array $data) {
                                //  cambiar todos los estados de la revision a Pendiente
                                $documentoProyecto->firma_documento()->update([
                                    'estado_revision' => 'Pendiente',
                                    'firma_id' => null,
                                    'sello_id' => null,

                                ]);

                                $documentoProyecto->estado_documento()->create([
                                    'empleado_id' => Auth::user()->empleado->id,
                                    'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')->first()->id,
                                    'fecha' => now(),
                                    'comentario' => $data['comentario'],
                                ]);

                                // dd(FirmaProyecto::where('proyecto_id', $proyecto->id)
                                // ->where('empleado_id', $this->docente->id)
                                // ->first());
                                Notification::make()
                                    ->title('¡Realizado!')
                                    ->body('Informe Rechazado correctamente')
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
                            ->modalHeading('Terminar Registro') // Título del diálogo
                            ->modalSubheading('Para aprobar el proyecto, por favor llene los siguientes campos')
                            ->action(function (DocumentoProyecto $documentoProyecto) {
                                // dd($this->docente);
                                // dd($documentoProyecto->tipo_documento);
                                // actualizar el estado del proyecto al siguiente estado :)

                                // dd($documentoProyecto->tipo_documento);
                                if ($documentoProyecto->tipo_documento == 'Informe Final') {
                                    // dd('finalizado');
                                    $proyecto  =  $documentoProyecto->proyecto;
                                    $proyecto->estado_proyecto()->create([
                                        'empleado_id' => Auth::user()->empleado->id,
                                        'tipo_estado_id' => TipoEstado::where('nombre', 'Finalizado')->first()->id,
                                        'fecha' => now(),
                                        'comentario' => 'El informe ha sido aprobado correctamente',
                                    ]);


                                    VerificarConstancia::makeConstanciasProyecto($proyecto);
                                }

                                // dd('antes de finalizar documento');
                                $documentoProyecto->estado_documento()->create([
                                    'empleado_id' => Auth::user()->empleado->id,
                                    'tipo_estado_id' => TipoEstado::where('nombre', 'Aprobado')->first()->id,
                                    'fecha' => now(),
                                    'comentario' => 'El informe ha sido aprobado correctamente',
                                ]);
                                // dd(FirmaProyecto::where('proyecto_id', $proyecto->id)
                                // ->where('empleado_id', $this->docente->id)
                                // ->first());
                                Notification::make()
                                    ->title('¡Realizado!')
                                    ->body('Informe Aprobado correctamente')
                                    ->info()
                                    ->send();
                            })
                            // ->modalWidth(MaxWidth::SevenExtraLarge)
                            ->button(),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.list-informes-solicitado')
            ;//->layout('components.panel.modulos.modulo-proyectos');
    }
}

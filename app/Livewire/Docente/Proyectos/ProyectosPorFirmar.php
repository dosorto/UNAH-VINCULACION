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

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Auth;

class ProyectosPorFirmar extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Empleado $docente;

    public function mount($docente = null): void
    {
        $this->docente = $docente ?? Auth::user()->empleado;
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

                Action::make('first')
                    ->label('Ver')
                    ->modalContent(
                        function (FirmaProyecto $firmaProyecto): View {

                            $firmaProyecto->firmable_type == Proyecto::class ?
                                $proyecto =  $firmaProyecto->proyecto :
                                $proyecto =  $firmaProyecto->documento_proyecto->proyecto;

                            return view(
                                'components.fichas.ficha-proyecto-vinculacion',
                                ['proyecto' => $proyecto]
                            );
                        }
                    )
                    ->closeModalByEscaping(false)
                    ->stickyModalFooter()
                    ->stickyModalHeader()
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->modalSubmitAction(false)
                    ->extraModalFooterActions([
                        Action::make('second')
                            ->label('Rechazar')
                            ->form([
                                Textarea::make('comentario')
                                    ->label('Comentario: Indique el motivo de rechazo')
                                    ->required()
                                    ->columns(5)
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ])
                            ->icon('heroicon-o-x-circle') // Icono para "Rechazar"
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading('Confirmar Rechazo') // Título del diálogo

                            ->modalSubheading('¿Estás seguro de que deseas Rechazar la firma de este proyecto?')
                            ->action(function (FirmaProyecto $firma_proyecto,  array $data) {
                                if ($firma_proyecto->firmable_type == Proyecto::class) {
                                    $firma_proyecto->proyecto->firma_proyecto()->update([
                                        'estado_revision' => 'Pendiente',
                                        'firma_id' => null,
                                        'sello_id' => null,
                                        'fecha_firma' => null,
                                    ]);

                                    // actualizar el estado del proyecto al siguiente estado :)
                                    $firma_proyecto->proyecto->estado_proyecto()->create([
                                        'empleado_id' => $this->docente->id,
                                        'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')
                                            ->first()->id,
                                        'fecha' => now(),
                                        'comentario' => $data['comentario'],
                                    ]);
                                } else {
                                    $firma_proyecto->documento_proyecto->firma_documento()->update([
                                        'estado_revision' => 'Pendiente',
                                        'firma_id' => null,
                                        'sello_id' => null,
                                    ]);

                                    // actualizar el estado del proyecto al siguiente estado :)
                                    $firma_proyecto->documento_proyecto->estado_documento()->create([
                                        'empleado_id' => $this->docente->id,
                                        'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')
                                            ->first()->id,
                                        'fecha' => now(),
                                        'comentario' => $data['comentario'],
                                    ]);
                                }


                                Notification::make()
                                    ->title('¡Realizado!')
                                    ->body('Proyecto Rechazado')
                                    ->info()
                                    ->send();
                            })
                            ->modalWidth(MaxWidth::FiveExtraLarge)
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

                                if ($firma_proyecto->firmable_type == Proyecto::class) {
                                    $firma_proyecto->update([
                                        'estado_revision' => 'Aprobado',
                                        'firma_id' => auth()->user()?->empleado?->firma?->id,
                                        'sello_id' => auth()->user()?->empleado?->sello?->id,
                                        'fecha_firma' => now(),
                                    ]);
                                    // actualizar el estado del proyecto al siguiente estado :)



                                    $firma_proyecto->proyecto->estado_proyecto()->create([

                                        'empleado_id' => auth()->user()->empleado->id,
                                        'tipo_estado_id' => $firma_proyecto->cargo_firma->estado_siguiente_id,
                                        'fecha' => now(),
                                        'comentario' => 'Firmado y aprobado en este estado',
                                    ]);
                                } else {
                                    $firma_proyecto->update([
                                        'estado_revision' => 'Aprobado',
                                        'firma_id' => auth()->user()?->empleado?->firma?->id,
                                        'sello_id' => auth()->user()?->empleado?->sello?->id,
                                    ]);

                                    // actualizar el estado del proyecto al siguiente estado :)
                                    $firma_proyecto->documento_proyecto->estado_documento()->create([
                                        'empleado_id' => $this->docente->id,
                                        'tipo_estado_id' => $firma_proyecto->cargo_firma->estado_siguiente_id,
                                        'fecha' => now(),
                                        'comentario' => 'Firmado y aprobado en este estado',
                                    ]);
                                }

                                // dd(FirmaProyecto::where('proyecto_id', $proyecto->id)
                                // ->where('empleado_id', $this->docente->id)
                                // ->first());
                                Notification::make()
                                    ->title('¡Realizado!')
                                    ->body('Proyecto Aprobado correctamente')
                                    ->info()
                                    ->send();
                            })
                            ->modalWidth(MaxWidth::SevenExtraLarge)
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
        return view('livewire.docente.proyectos.proyectos-por-firmar');
    }
}

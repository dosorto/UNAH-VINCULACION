<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use Filament\Tables;
use Livewire\Component;
use Filament\Tables\Table;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\FichaActualizacion;
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

class FichasActualizacionPorFirmar extends Component implements HasForms, HasTable
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
                // Solo las firmas de fichas de actualización pendientes
                $this->docente->firmaProyecto()
                    ->where('firmable_type', FichaActualizacion::class)
                    ->whereIn('id', $this->docente->getIdValidos())
                    ->getQuery()
            )
            ->columns([
                Tables\Columns\TextColumn::make('ficha_actualizacion.proyecto.nombre_proyecto')
                    ->label('Nombre del Proyecto')
                    ->searchable()
                    ->wrap()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ficha_actualizacion.proyecto.codigo_proyecto')
                    ->label('Código Proyecto')
                    ->searchable()
                    ->placeholder('Sin código'),

                Tables\Columns\TextColumn::make('cargo_firma.tipoCargoFirma.nombre')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->wrap()
                    ->label('Cargo de firma')
                    ->searchable(),

                Tables\Columns\TextColumn::make('estado_revision')
                    ->label('Estado Firma')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aprobado' => 'success',
                        'Pendiente' => 'warning',
                        'Rechazado' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('ver_ficha')
                    ->label('Ver Ficha')
                    ->modalContent(
                        function (FirmaProyecto $firmaProyecto): View {
                            $fichaActualizacion = $firmaProyecto->ficha_actualizacion;
                            $proyecto = $fichaActualizacion->proyecto;

                            return view(
                                'components.fichas.ficha-actualizacion-proyecto-vinculacion',
                                [
                                    'fichaActualizacion' => $fichaActualizacion,
                                    'proyecto' => $proyecto->load(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye'])
                                ]
                            );
                        }
                    )
                    ->closeModalByEscaping(false)
                    ->stickyModalFooter()
                    ->stickyModalHeader()
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->modalSubmitAction(false)
                    ->extraModalFooterActions([
                        Action::make('rechazar_ficha')
                            ->label('Rechazar')
                            ->form([
                                Textarea::make('comentario')
                                    ->label('Comentario: Indique el motivo de rechazo')
                                    ->required()
                                    ->columns(5)
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ])
                            ->icon('heroicon-o-x-circle')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading('Confirmar Rechazo')
                            ->modalSubheading('¿Estás seguro de que deseas rechazar la firma de esta ficha de actualización?')
                            ->action(function (FirmaProyecto $firma_ficha, array $data) {
                                $fichaActualizacion = $firma_ficha->ficha_actualizacion;
                                
                                $firma_ficha->update([
                                    'estado_revision' => 'Pendiente',
                                    'firma_id' => null,
                                    'sello_id' => null,
                                    'fecha_firma' => null,
                                ]);

                                // Crear estado de rechazo para la ficha
                                $fichaActualizacion->estado_proyecto()->create([
                                    'empleado_id' => $this->docente->id,
                                    'tipo_estado_id' => TipoEstado::where('nombre', 'Rechazado')->first()->id,
                                    'fecha' => now(),
                                    'comentario' => $data['comentario'],
                                ]);

                                // Cancelar todas las solicitudes pendientes asociadas a la ficha
                                $resultadoCancelacion = $fichaActualizacion->cancelarSolicitudesPorRechazo();

                                $mensaje = 'Ficha de Actualización Rechazada';
                                if ($resultadoCancelacion['canceladas']) {
                                    $mensaje .= '. ' . $resultadoCancelacion['mensaje'];
                                }

                                Notification::make()
                                    ->title('¡Realizado!')
                                    ->body($mensaje)
                                    ->info()
                                    ->send();
                            })
                            ->modalWidth(MaxWidth::FiveExtraLarge)
                            ->cancelParentActions()
                            ->button(),

                        Action::make('aprobar_ficha')
                            ->label('Aprobar')
                            ->cancelParentActions()
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->requiresConfirmation()
                            ->modalHeading('Confirmar Aprobación')
                            ->modalSubheading('¿Estás seguro de que deseas aprobar la firma de esta ficha de actualización?')
                            ->action(function (FirmaProyecto $firma_ficha) {
                                $fichaActualizacion = $firma_ficha->ficha_actualizacion;

                                $firma_ficha->update([
                                    'estado_revision' => 'Aprobado',
                                    'firma_id' => auth()->user()?->empleado?->firma?->id,
                                    'sello_id' => auth()->user()?->empleado?->sello?->id,
                                    'fecha_firma' => now(),
                                ]);

                                // Crear estado siguiente para la ficha
                                $fichaActualizacion->estado_proyecto()->create([
                                    'empleado_id' => auth()->user()->empleado->id,
                                    'tipo_estado_id' => $firma_ficha->cargo_firma->estado_siguiente_id,
                                    'fecha' => now(),
                                    'comentario' => 'Ficha de actualización firmada y aprobada',
                                ]);

                                Notification::make()
                                    ->title('¡Realizado!')
                                    ->body('Ficha de Actualización Aprobada correctamente')
                                    ->info()
                                    ->send();
                            })
                            ->modalWidth(MaxWidth::SevenExtraLarge)
                            ->button(),
                    ])
            ])
            ->emptyStateHeading('No hay fichas de actualización por firmar')
            ->emptyStateDescription('Las fichas de actualización que requieran su firma aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.docente.proyectos.fichas-actualizacion-por-firmar');
    }
}

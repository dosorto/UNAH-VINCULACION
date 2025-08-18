<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\FichaActualizacion;
use App\Models\Proyecto\FirmaProyecto;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ListFichasActualizacionVinculacion extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FichaActualizacion::query()
                    ->whereHas('estado_proyecto', function (Builder $query) {
                        $query->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision')->first()?->id)
                              ->where('es_actual', true);
                    })
                    ->whereHas('firma_proyecto', function (Builder $query) {
                        $query->whereHas('cargo_firma.tipoCargoFirma', function ($subQuery) {
                            $subQuery->where('nombre', 'Revisor Vinculacion');
                        })
                        ->where('empleado_id', auth()->user()->empleado->id)
                        ->where('estado_revision', 'Pendiente');
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('proyecto.nombre_proyecto')
                    ->label('Nombre del Proyecto')
                    ->searchable()
                    ->wrap()
                    ->sortable(),

                Tables\Columns\TextColumn::make('proyecto.codigo_proyecto')
                    ->label('Código Proyecto')
                    ->searchable()
                    ->placeholder('Sin código'),

                Tables\Columns\TextColumn::make('proyecto.coordinador.nombre_completo')
                    ->label('Coordinador')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('fecha_registro')
                    ->label('Fecha Creación Ficha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado_actual')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(function (FichaActualizacion $record): string {
                        $estado = $record->obtenerUltimoEstado();
                        return $estado ? $estado->tipoestado->nombre : 'Sin estado';
                    })
                    ->color(function (FichaActualizacion $record): string {
                        $estado = $record->obtenerUltimoEstado();
                        if (!$estado) return 'gray';
                        
                        return match($estado->tipoestado->nombre) {
                            'En revision' => 'warning',
                            'Actualizacion realizada' => 'success',
                            'Subsanacion' => 'danger',
                            default => 'primary',
                        };
                    }),

                Tables\Columns\TextColumn::make('firmas_progreso')
                    ->label('Progreso Firmas')
                    ->badge()
                    ->formatStateUsing(function (FichaActualizacion $record): string {
                        $firmasTotal = $record->firma_proyecto()->count();
                        $firmasAprobadas = $record->firma_proyecto()->where('estado_revision', 'Aprobado')->count();
                        return "{$firmasAprobadas}/{$firmasTotal}";
                    })
                    ->color(function (FichaActualizacion $record): string {
                        $firmasTotal = $record->firma_proyecto()->count();
                        $firmasAprobadas = $record->firma_proyecto()->where('estado_revision', 'Aprobado')->count();
                        
                        if ($firmasTotal == 0) return 'gray';
                        if ($firmasAprobadas == $firmasTotal) return 'success';
                        if ($firmasAprobadas > 0) return 'warning';
                        return 'danger';
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('ver_ficha')
                    ->label('Revisar Ficha')
                    ->icon('heroicon-o-document-text')
                    ->modalContent(
                        function (FichaActualizacion $fichaActualizacion): View {
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
                            ->modalSubheading('¿Estás seguro de que deseas rechazar esta ficha de actualización?')
                            ->action(function (FichaActualizacion $fichaActualizacion, array $data) {
                                // Buscar la firma correspondiente al revisor de vinculación
                                $firmaRevisor = $fichaActualizacion->firma_proyecto()
                                    ->whereHas('cargo_firma.tipoCargoFirma', function ($query) {
                                        $query->where('nombre', 'Revisor Vinculacion');
                                    })
                                    ->where('empleado_id', auth()->user()->empleado->id)
                                    ->first();

                                if ($firmaRevisor) {
                                    $firmaRevisor->update([
                                        'estado_revision' => 'Pendiente',
                                        'firma_id' => null,
                                        'sello_id' => null,
                                        'fecha_firma' => null,
                                    ]);

                                    // Crear estado de subsanación para la ficha
                                    $fichaActualizacion->estado_proyecto()->create([
                                        'empleado_id' => auth()->user()->empleado->id,
                                        'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')->first()->id,
                                        'fecha' => now(),
                                        'comentario' => $data['comentario'],
                                    ]);

                                    Notification::make()
                                        ->title('¡Realizado!')
                                        ->body('Ficha de Actualización Rechazada')
                                        ->info()
                                        ->send();
                                }
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
                            ->modalSubheading('¿Estás seguro de que deseas aprobar esta ficha de actualización? Esto cambiará el estado a "Actualización realizada".')
                            ->action(function (FichaActualizacion $fichaActualizacion) {
                                // Buscar la firma correspondiente al revisor de vinculación
                                $firmaRevisor = $fichaActualizacion->firma_proyecto()
                                    ->whereHas('cargo_firma.tipoCargoFirma', function ($query) {
                                        $query->where('nombre', 'Revisor Vinculacion');
                                    })
                                    ->where('empleado_id', auth()->user()->empleado->id)
                                    ->first();

                                if ($firmaRevisor) {
                                    $firmaRevisor->update([
                                        'estado_revision' => 'Aprobado',
                                        'firma_id' => auth()->user()?->empleado?->firma?->id,
                                        'sello_id' => auth()->user()?->empleado?->sello?->id,
                                        'fecha_firma' => now(),
                                    ]);

                                    // Cambiar al estado "Actualización realizada"
                                    $fichaActualizacion->estado_proyecto()->create([
                                        'empleado_id' => auth()->user()->empleado->id,
                                        'tipo_estado_id' => TipoEstado::where('nombre', 'Actualizacion realizada')->first()->id,
                                        'fecha' => now(),
                                        'comentario' => 'Ficha de actualización aprobada por Revisor de Vinculación. Actualización completada exitosamente.',
                                    ]);

                                    // PROCESAR LAS BAJAS PENDIENTES - APLICAR LOS CAMBIOS AL EQUIPO EJECUTOR
                                    $bajasProcesadas = $fichaActualizacion->procesarBajasPendientes();

                                    $mensaje = 'Ficha de Actualización Aprobada. Estado cambiado a "Actualización realizada".';
                                    if ($bajasProcesadas > 0) {
                                        $mensaje .= " Se han aplicado {$bajasProcesadas} baja(s) al equipo ejecutor.";
                                    }

                                    Notification::make()
                                        ->title('¡Realizado!')
                                        ->body($mensaje)
                                        ->success()
                                        ->send();
                                }
                            })
                            ->modalWidth(MaxWidth::SevenExtraLarge)
                            ->button(),
                    ])
            ])
            ->emptyStateHeading('No hay fichas de actualización por revisar')
            ->emptyStateDescription('No hay fichas de actualización pendientes de revisión por vinculación en este momento.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.list-fichas-actualizacion-vinculacion');
    }
}

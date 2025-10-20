<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Estado\TipoEstado;
use App\Http\Controllers\Docente\VerificarConstancia;
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
use Illuminate\Support\Facades\Mail;
use App\Mail\ProyectoEstadoCambiado;
use Illuminate\Support\Facades\Log;

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
                                        'tipo_estado_id' => TipoEstado::where('nombre', 'Rechazado')->first()->id,
                                        'fecha' => now(),
                                        'comentario' => $data['comentario'],
                                    ]);

                                    // Cancelar todas las solicitudes pendientes asociadas a la ficha
                                    $resultadoCancelacion = $fichaActualizacion->cancelarSolicitudesPorRechazo();

                                    // Enviar notificación por correo al coordinador del proyecto
                                    try {
                                        $coordinador = $fichaActualizacion->proyecto->coordinador_proyecto->first()?->empleado->user ?? null;
                                        
                                        if ($coordinador) {
                                            Mail::to($coordinador->email)->send(
                                                new ProyectoEstadoCambiado(
                                                    $fichaActualizacion->proyecto,
                                                    $coordinador,
                                                    'Ficha de Actualización Rechazada',
                                                    $data['comentario'],
                                                    'rechazo de ficha de actualización'
                                                )
                                            );
                                            Log::info('Correo de rechazo de ficha de actualización enviado', [
                                                'proyecto_id' => $fichaActualizacion->proyecto->id,
                                                'coordinador_email' => $coordinador->email
                                            ]);
                                        } else {
                                            Log::warning('No se pudo enviar correo de rechazo de ficha: coordinador no encontrado', [
                                                'proyecto_id' => $fichaActualizacion->proyecto->id
                                            ]);
                                        }
                                    } catch (\Exception $e) {
                                        Log::error('Error al enviar correo de rechazo de ficha de actualización', [
                                            'error' => $e->getMessage(),
                                            'proyecto_id' => $fichaActualizacion->proyecto->id
                                        ]);
                                    }

                                    $mensaje = 'Ficha de Actualización Rechazada';
                                    if ($resultadoCancelacion['canceladas']) {
                                        $mensaje .= '. ' . $resultadoCancelacion['mensaje'];
                                    }

                                    Notification::make()
                                        ->title('¡Realizado!')
                                        ->body($mensaje)
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

                                    // PROCESAR LOS INTEGRANTES NUEVOS - INCORPORARLOS AL EQUIPO EJECUTOR
                                    $integrantesNuevosProcesados = $fichaActualizacion->procesarIntegrantesNuevos();

                                    // APLICAR LA NUEVA FECHA DE FINALIZACIÓN SI SE ESPECIFICÓ
                                    $fechaActualizada = $fichaActualizacion->aplicarNuevaFechaFinalizacion();

                                    $mensaje = 'Ficha de Actualización Aprobada. Estado cambiado a "Actualización realizada".';
                                    if ($bajasProcesadas > 0) {
                                        $mensaje .= " Se han aplicado {$bajasProcesadas} baja(s) al equipo ejecutor.";
                                    }
                                    if ($integrantesNuevosProcesados > 0) {
                                        $mensaje .= " Se han incorporado {$integrantesNuevosProcesados} nuevo(s) integrante(s) al equipo ejecutor.";
                                    }
                                    if ($fechaActualizada['actualizada']) {
                                        $fechaNuevaFormateada = \Carbon\Carbon::parse($fechaActualizada['fecha_nueva'])->format('d/m/Y');
                                        $mensaje .= " La fecha de finalización del proyecto se ha actualizado al {$fechaNuevaFormateada}.";
                                    }

                                    // Enviar notificación por correo al coordinador del proyecto
                                    try {
                                        $coordinador = $fichaActualizacion->proyecto->coordinador_proyecto->first()?->empleado->user ?? null;
                                        
                                        if ($coordinador) {
                                            $mensajeCorreo = 'Su Ficha de Actualización ha sido aprobada exitosamente. ';
                                            if ($bajasProcesados > 0) {
                                                $mensajeCorreo .= "Se han aplicado {$bajasProcesados} baja(s) al equipo ejecutor. ";
                                            }
                                            if ($integrantesNuevosProcesados > 0) {
                                                $mensajeCorreo .= "Se han incorporado {$integrantesNuevosProcesados} nuevo(s) integrante(s) al equipo ejecutor. ";
                                            }
                                            if ($fechaActualizada['actualizada']) {
                                                $fechaNuevaFormateada = \Carbon\Carbon::parse($fechaActualizada['fecha_nueva'])->format('d/m/Y');
                                                $mensajeCorreo .= "La fecha de finalización del proyecto se ha actualizado al {$fechaNuevaFormateada}. ";
                                            }
                                            $mensajeCorreo .= 'Las constancias han sido generadas automáticamente.';
                                            
                                            Mail::to($coordinador->email)->send(
                                                new ProyectoEstadoCambiado(
                                                    $fichaActualizacion->proyecto,
                                                    $coordinador,
                                                    'Ficha de Actualización Aprobada',
                                                    $mensajeCorreo,
                                                    'aprobación de ficha de actualización'
                                                )
                                            );
                                            Log::info('Correo de aprobación de ficha de actualización enviado', [
                                                'proyecto_id' => $fichaActualizacion->proyecto->id,
                                                'coordinador_email' => $coordinador->email
                                            ]);
                                        } else {
                                            Log::warning('No se pudo enviar correo de aprobación de ficha: coordinador no encontrado', [
                                                'proyecto_id' => $fichaActualizacion->proyecto->id
                                            ]);
                                        }
                                    } catch (\Exception $e) {
                                        Log::error('Error al enviar correo de aprobación de ficha de actualización', [
                                            'error' => $e->getMessage(),
                                            'proyecto_id' => $fichaActualizacion->proyecto->id
                                        ]);
                                    }

                                    VerificarConstancia::makeConstanciasActualizacion($fichaActualizacion);
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

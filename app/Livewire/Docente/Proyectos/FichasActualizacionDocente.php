<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Proyecto\FichaActualizacion;
use App\Http\Controllers\Docente\VerificarConstancia;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class FichasActualizacionDocente extends Component implements HasForms, HasTable
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
                FichaActualizacion::query()
                    ->whereHas('proyecto.coordinador_proyecto', function (Builder $query) {
                        $query->where('empleado_id', auth()->user()->empleado->id);
                    })
                    ->with(['proyecto.coordinador_proyecto.empleado'])
            )
            ->columns([
                TextColumn::make('proyecto.nombre_proyecto')
                    ->label('Nombre del Proyecto')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (FichaActualizacion $record): string => 'Código: ' . $record->proyecto->codigo_proyecto),

                TextColumn::make('proyecto.coordinador.nombre_completo')
                    ->label('Coordinador')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('estado')
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
                        'Actualizacion realizada' => 'success',
                        'Rechazado' => 'danger',
                        'Borrador' => 'warning',
                        default => 'primary',
                        };
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ActionGroup::make([
                    Action::make('ver_ficha')
                        ->label('Ver Ficha')
                        ->icon('heroicon-o-eye')
                        ->color('primary')
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
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar')
                        ->modal()
                        ->modalWidth(MaxWidth::SevenExtraLarge),

                         Action::make('eliminar_ficha')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Eliminar Ficha de Actualización?')
                    ->modalDescription(function (FichaActualizacion $record) {
                        if (!$record->puedeSerEliminada()) {
                            return 'No se puede eliminar esta ficha porque ya tiene firmas de otros cargos aprobadas.';
                        }
                        
                        $firmasAprobadas = $record->firma_proyecto()
                            ->where('estado_revision', 'Aprobado')
                            ->with('cargo_firma.tipoCargoFirma')
                            ->get();
                            
                        if ($firmasAprobadas->count() === 0) {
                            return 'Esta acción eliminará permanentemente la ficha de actualización y todas sus solicitudes asociadas. Esta acción no se puede deshacer.';
                        }
                        
                        if ($firmasAprobadas->count() === 1 && 
                            $firmasAprobadas->first()->cargo_firma->tipoCargoFirma->nombre === 'Coordinador Proyecto') {
                            return 'Esta ficha tiene su firma como coordinador aprobada, pero aún puede ser eliminada. Esta acción eliminará permanentemente la ficha de actualización y todas sus solicitudes asociadas. Esta acción no se puede deshacer.';
                        }
                        
                        return 'Esta acción eliminará permanentemente la ficha de actualización y todas sus solicitudes asociadas. Esta acción no se puede deshacer.';
                    })
                    ->modalSubmitActionLabel('Sí, Eliminar')
                    ->modalCancelActionLabel('Cancelar')
                    ->visible(fn (FichaActualizacion $record) => $record->puedeSerEliminada())
                    ->action(function (FichaActualizacion $record) {
                        $resultado = $record->eliminarFichaSiEsSeguro();
                        
                        if ($resultado['eliminada']) {
                            Notification::make()
                                ->title('¡Eliminada!')
                                ->body($resultado['mensaje'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error')
                                ->body($resultado['razon'])
                                ->danger()
                                ->send();
                        }
                    }),

                    Action::make('constancia_actualizacion')
                        ->label('Constancia Actualización')
                        ->icon('heroicon-o-document')
                        ->color('info')
                        ->visible(function (FichaActualizacion $fichaActualizacion) {
                            $esEstadoActualizacion = $fichaActualizacion->estado
                                && $fichaActualizacion->estado->tipoestado
                                && $fichaActualizacion->estado->tipoestado->nombre === 'Actualizacion realizada';

                            $empleadoProyecto = $fichaActualizacion->equipoEjecutor()
                                ->where('empleado_id', $this->docente->id)
                                ->first();

                            return $esEstadoActualizacion
                                && VerificarConstancia::validarConstanciaActualizacion($empleadoProyecto);
                        })
                        ->action(function (FichaActualizacion $fichaActualizacion) {
                            return VerificarConstancia::CrearPdfActualizacion($fichaActualizacion->equipoEjecutor()
                                ->where('empleado_id', $this->docente->id)
                                ->first());
                        }),

                ])->button()
                    ->color('primary')
                    ->label('Acciones'),
            ])
            ->emptyStateHeading('No hay fichas de actualización')
            ->emptyStateDescription('Aún no has creado ninguna ficha de actualización para tus proyectos.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public function render(): View
    {
        return view('livewire.docente.proyectos.fichas-actualizacion-docente');
    }
}

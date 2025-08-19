<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Proyecto\FichaActualizacion;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class FichasActualizacionDocente extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

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
                        'Subsanacion' => 'danger',
                        'Borrador' => 'warning',
                        default => 'primary',
                        };
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('ver_ficha')
                    ->label('Ver Ficha')
                    ->icon('heroicon-o-eye')
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

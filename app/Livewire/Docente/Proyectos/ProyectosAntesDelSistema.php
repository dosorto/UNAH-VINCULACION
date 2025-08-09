<?php

namespace App\Livewire\Docente\Proyectos;

use Livewire\Component;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Proyecto\Proyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\EmpleadoProyecto;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\MaxWidth;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\Personal\Empleado;

class ProyectosAntesDelSistema extends Component implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;
    public Empleado $docente;

    public function mount($docente = null): void
    {
        $this->docente = $docente ?? Auth::user()->empleado;
    }
    public function table(Table $table): Table
    {
        $empleadoId = auth()->user()->empleado->id;

        return $table
            ->query(
                Proyecto::query()
                    ->whereHas('estado_proyecto', function (Builder $query) {
                        $tipoEstados = TipoEstado::whereIn('nombre', ['PendienteInformacion', 'Finalizado'])->pluck('id');
                        if ($tipoEstados->isNotEmpty()) {
                            $query->whereIn('tipo_estado_id', $tipoEstados)
                                  ->where('es_actual', true);
                        }
                    })
                    ->whereHas('docentes_proyecto', function (Builder $query) use ($empleadoId) {
                        $query->where('empleado_id', $empleadoId);
                    })
                    ->with(['tipo_estado', 'modalidad', 'docentes_proyecto.empleado'])
            )
            ->columns([
                TextColumn::make('codigo_proyecto')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('numero_dictamen')
                    ->label('Número de Dictamen')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombre_proyecto')
                    ->label('Nombre del Proyecto')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                    //rol del empleado en el proyecto 
                TextColumn::make('docentes_proyecto.rol')
                    ->label('Rol')
                    ->formatStateUsing(
                        function (Proyecto $record) {
                            return $record->docentes_proyecto()
                                ->where('empleado_id', $this->docente->id)
                                ->first()->rol;
                        }
                    ),
                TextColumn::make('tipo_estado.nombre')
                    ->label('Estado')
                    ->badge()
                    ->color(function ($record) {
                        $estado = $record->tipo_estado?->nombre;
                        return match($estado) {
                            'PendienteInformacion' => 'warning',
                            'Finalizado' => 'success',
                            default => 'gray'
                        };
                    }),
            ])
            ->actions([
                Action::make('editar')
                    ->label(function (Proyecto $record) {
                        $estado = $record->tipo_estado?->nombre;
                        return $estado === 'Finalizado' ? 'Editar Proyecto' : 'Completar Información';
                    })
                    ->visible(function (Proyecto $record) {
                        $estado = $record->tipo_estado?->nombre;
                        return in_array($estado, ['PendienteInformacion']);
                    })
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn(Proyecto $record): string => route('editarProyectoAntesDelSistema', $record)),
                    
                Action::make('ver_detalles')
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalContent(fn (Proyecto $record) => view('livewire.docente.proyectos.proyecto-antes-del-sistema-detalles', compact('record')))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth(MaxWidth::SevenExtraLarge),
            ])
            ->emptyStateHeading('No hay proyectos en esta sección')
            ->emptyStateDescription('Los proyectos creados automáticamente desde códigos de investigación aparecerán aquí. Podrás completar la información pendiente y seguirán disponibles en esta vista para su gestión.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->defaultSort('created_at', 'desc');
    }

    public function render()
    {
        return view('livewire.docente.proyectos.proyectos-antes-del-sistema');
    }
}

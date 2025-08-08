<?php

namespace App\Livewire\Personal;

use Livewire\Component;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Models\Personal\EmpleadoCodigoInvestigacion;
use App\Models\Personal\EmpleadoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\Estado\TipoEstado;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CodigosInvestigacionAdmin extends Component implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(EmpleadoCodigoInvestigacion::query()->with(['empleado', 'verificadoPor']))
            ->columns([
                TextColumn::make('empleado.nombre_completo')
                    ->label('Docente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('codigo_proyecto')
                    ->label('Código del Proyecto')
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
                TextColumn::make('rol_docente')
                    ->label('Rol del Docente')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'coordinador' => 'Coordinador',
                        'integrante' => 'Integrante',
                        default => $state
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'integrante' => 'info',
                        'coordinador' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('año')
                    ->label('Año')
                    ->sortable(),
                TextColumn::make('estado_verificacion')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'verificado' => 'success',
                        'rechazado' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendiente' => 'Pendiente',
                        'verificado' => 'Verificado',
                        'rechazado' => 'Rechazado',
                        default => 'Desconocido',
                    }),
                TextColumn::make('verificadoPor.nombre_completo')
                    ->label('Verificado por')
                    ->placeholder('—'),
                TextColumn::make('numero_dictamen')
                    ->label('Número de Dictamen')
                    ->getStateUsing(function (EmpleadoCodigoInvestigacion $record) {
                        // Buscar el proyecto con el mismo código
                        $proyecto = Proyecto::where('codigo_proyecto', $record->codigo_proyecto)->first();
                        
                        // Debug: mostrar si existe proyecto y su dictamen
                        if (!$proyecto) {
                            return 'Sin proyecto';
                        }
                        
                        return $proyecto->numero_dictamen ?: 'Sin dictamen';
                    })
                    ->placeholder('—')
                    ->sortable(false),
            ])
            ->filters([
                SelectFilter::make('estado_verificacion')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'verificado' => 'Verificado',
                        'rechazado' => 'Rechazado',
                    ]),
                SelectFilter::make('rol_docente')
                    ->label('Rol del Docente')
                    ->options([
                        'coordinador' => 'Coordinador',
                        'integrante' => 'Integrante',
                    ]),
                SelectFilter::make('año')
                    ->label('Año')
                    ->options(collect(range(date('Y') - 10, date('Y') + 2))->mapWithKeys(fn($year) => [$year => $year])),
            ])
            ->actions([
                Action::make('verificar')
                    ->label('Verificar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (EmpleadoCodigoInvestigacion $record): bool => $record->estado_verificacion === 'pendiente')
                    ->form([
                        TextInput::make('numero_dictamen')
                                ->label('Número de Dictamen')
                                ->placeholder('Agregar número de dictamen...')
                                ->required(),
                        Textarea::make('observaciones_admin')
                            ->label('Observaciones (Opcional)')
                            ->placeholder('Agregar observaciones sobre la verificación...')
                            ->rows(3),
                    ])
                    ->action(function (EmpleadoCodigoInvestigacion $record, array $data): void {
                        // Verificar si el código ya existe en la tabla proyecto
                        $proyectoExistente = Proyecto::where('codigo_proyecto', $record->codigo_proyecto)->first();
                        
                        if ($proyectoExistente) {
                            // Si el código ya existe, verificar si el empleado ya está registrado en ese proyecto
                            $empleadoYaRegistrado = EmpleadoProyecto::where('empleado_id', $record->empleado_id)
                                ->where('proyecto_id', $proyectoExistente->id)
                                ->exists();
                            
                            if (!$empleadoYaRegistrado) {
                                // Registrar al empleado en el proyecto existente
                                EmpleadoProyecto::create([
                                    'empleado_id' => $record->empleado_id,
                                    'proyecto_id' => $proyectoExistente->id,
                                    'rol' => $record->rol_docente === 'coordinador' ? 'Coordinador' : 'Integrante',
                                    'hash' => Str::random(32),
                                ]);
                                
                                $record->update([
                                    'estado_verificacion' => 'verificado',
                                    'observaciones_admin' => ($data['observaciones_admin'] ?? 'Docente registrado en proyecto existente') . ' | Dictamen: ' . ($data['numero_dictamen'] ?? 'Sin dictamen'),
                                    'verificado_por' => auth()->user()->empleado->id,
                                    'fecha_verificacion' => now(),
                                ]);

                                // Actualizar el dictamen en el proyecto existente
                                $proyectoExistente->update([
                                    'numero_dictamen' => $data['numero_dictamen']
                                ]);

                                Notification::make()
                                    ->title('Código verificado')
                                    ->body("El código {$record->codigo_proyecto} ha sido verificado exitosamente y el docente ha sido registrado en el proyecto existente.")
                                    ->success()
                                    ->send();
                            } else {
                                $record->update([
                                    'estado_verificacion' => 'verificado',
                                    'observaciones_admin' => ($data['observaciones_admin']),
                                    'verificado_por' => auth()->user()->empleado->id,
                                    'fecha_verificacion' => now(),
                                ]);

                                // Actualizar el dictamen en el proyecto existente
                                $proyectoExistente->update([
                                    'numero_dictamen' => $data['numero_dictamen']
                                ]);

                                Notification::make()
                                    ->title('Código verificado')
                                    ->body("El código {$record->codigo_proyecto} ha sido verificado exitosamente. El docente ya estaba registrado en este proyecto.")
                                    ->success()
                                    ->send();
                            }
                        } else {
                            // El proyecto no existe, crear un nuevo proyecto con los datos del código
                            try {
                                $nuevoProyecto = Proyecto::create([
                                    'nombre_proyecto' => $record->nombre_proyecto,
                                    'codigo_proyecto' => $record->codigo_proyecto,
                                    'resumen' => $record->descripcion ?? 'Proyecto registrado mediante código de investigación',
                                    'descripcion_participantes' => 'Proyecto verificado administrativamente',
                                    'definicion_problema' => 'Por definir',
                                    'objetivo_general' => 'Por definir',
                                    'fecha_inicio' => now(),
                                    'fecha_finalizacion' => now()->addYear(),
                                    'evaluacion_intermedia' => now()->addMonths(6),
                                    'evaluacion_final' => now()->addYear(),
                                    'poblacion_participante' => 0,
                                    'hombres' => 0,
                                    'mujeres' => 0,
                                    'otros' => 0,
                                    'modalidad_id' => 1, // Asumir modalidad por defecto
                                    'fecha_registro' => now(),
                                    'numero_dictamen' => $data['numero_dictamen'] ?? null, // Guardar el dictamen en el proyecto
                                ]);

                                // Registrar al empleado en el nuevo proyecto
                                EmpleadoProyecto::create([
                                    'empleado_id' => $record->empleado_id,
                                    'proyecto_id' => $nuevoProyecto->id,
                                    'rol' => $record->rol_docente === 'coordinador' ? 'Coordinador' : 'Integrante',
                                    'hash' => Str::random(32),
                                ]);

                                // Crear estado inicial del proyecto
                                $tipoEstadoPendiente = TipoEstado::where('nombre', 'PendienteInformacion')->first();
                                if (!$tipoEstadoPendiente) {
                                    // Si no existe, crearlo
                                    $tipoEstadoPendiente = TipoEstado::create([
                                        'nombre' => 'PendienteInformacion',
                                        'descripcion' => 'Estado para proyectos creados automáticamente que requieren completar información'
                                    ]);
                                }
                                
                                $nuevoProyecto->estado_proyecto()->create([
                                    'empleado_id' => auth()->user()->empleado->id,
                                    'tipo_estado_id' => $tipoEstadoPendiente->id,
                                    'fecha' => now(),
                                    'comentario' => 'Proyecto creado automáticamente al verificar código de investigación. Dictamen: ' . ($data['numero_dictamen'] ?? 'Sin dictamen'),
                                ]);

                                $record->update([
                                    'estado_verificacion' => 'verificado',
                                    'observaciones_admin' => ($data['observaciones_admin'] ?? 'Proyecto creado automáticamente al verificar código') . ' | Dictamen: ' . ($data['numero_dictamen'] ?? 'Sin dictamen'),
                                    'verificado_por' => auth()->user()->empleado->id,
                                    'fecha_verificacion' => now(),
                                ]);

                                Notification::make()
                                    ->title('Código verificado y proyecto creado')
                                    ->body("El código {$record->codigo_proyecto} ha sido verificado exitosamente con dictamen {$data['numero_dictamen']}. Se ha creado un nuevo proyecto y el docente ha sido registrado.")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error al crear proyecto')
                                    ->body("Error al crear el proyecto: " . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }
                    }),
                Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (EmpleadoCodigoInvestigacion $record): bool => $record->estado_verificacion === 'pendiente')
                    ->form([
                        Textarea::make('observaciones_admin')
                            ->label('Motivo del Rechazo')
                            ->placeholder('Explicar por qué se rechaza este código...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (EmpleadoCodigoInvestigacion $record, array $data): void {
                        $record->update([
                            'estado_verificacion' => 'rechazado',
                            'observaciones_admin' => $data['observaciones_admin'],
                            'verificado_por' => auth()->user()->empleado->id,
                            'fecha_verificacion' => now(),
                        ]);

                        Notification::make()
                            ->title('Código rechazado')
                            ->body("El código {$record->codigo_proyecto} ha sido rechazado.")
                            ->warning()
                            ->send();
                    }),
                Action::make('revertir')
                    ->label('Revertir')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (EmpleadoCodigoInvestigacion $record): bool => $record->estado_verificacion !== 'pendiente')
                    ->requiresConfirmation()
                    ->modalHeading('Revertir Estado')
                    ->modalDescription('¿Está seguro de que desea revertir este código a estado pendiente?')
                    ->action(function (EmpleadoCodigoInvestigacion $record): void {
                        $record->update([
                            'estado_verificacion' => 'pendiente',
                            'observaciones_admin' => null,
                            'verificado_por' => null,
                            'fecha_verificacion' => null,
                        ]);

                        Notification::make()
                            ->title('Estado revertido')
                            ->body("El código {$record->codigo_proyecto} ha sido revertido a pendiente.")
                            ->info()
                            ->send();
                    }),
                Action::make('ver_detalles')
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (EmpleadoCodigoInvestigacion $record) => view('livewire.personal.codigo-investigacion-detalles', compact('record')))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->bulkActions([
                BulkAction::make('verificar_multiples')
                    ->label('Verificar Seleccionados')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verificar Códigos Seleccionados')
                    ->modalDescription('¿Está seguro de que desea verificar todos los códigos seleccionados?')
                    ->action(function (Collection $records): void {
                        $records->where('estado_verificacion', 'pendiente')->each(function ($record) {
                            $record->update([
                                'estado_verificacion' => 'verificado',
                                'verificado_por' => auth()->user()->empleado->id,
                                'fecha_verificacion' => now(),
                            ]);
                        });

                        Notification::make()
                            ->title('Códigos verificados')
                            ->body('Los códigos seleccionados han sido verificados exitosamente.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function render()
    {
        return view('livewire.personal.codigos-investigacion-admin');
    }
}

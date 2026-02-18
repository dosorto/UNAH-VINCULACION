<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\Proyecto\Proyecto;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;

use Filament\Tables\Filters\Filter;
use Filament\Forms\Get;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Grid;
use App\Models\Estado\TipoEstado;


use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;


class ListProyectosVinculacion extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {


        return $table
            ->query(

                Proyecto::query()
                    ->whereNotIn('proyecto.id', function ($query) {
                        $query->select('estadoable_id')
                            ->from('estado_proyecto')
                            ->where('estadoable_type', Proyecto::class) 
                            ->whereIn('tipo_estado_id', TipoEstado::whereIn('nombre', ['Borrador'])->pluck('id')->toArray()) // Excluir 'Borrador' y 'Subsanacion'
                            ->where('es_actual', true);
                    })
                    ->leftJoin('proyecto_centro_facultad', 'proyecto_centro_facultad.proyecto_id', '=', 'proyecto.id')
                    ->leftJoin('proyecto_depto_ac', 'proyecto_depto_ac.proyecto_id', '=', 'proyecto.id')
    
                    ->leftJoin('estado_proyecto', 'estado_proyecto.estadoable_id', '=', 'proyecto.id')
                    ->leftJoin('tipo_estado', 'estado_proyecto.tipo_estado_id', '=', 'tipo_estado.id')
                    // Filtrar por categoría según el rol del usuario
                    ->when(auth()->user()->hasRole('equipo_desarrollo_local'), function ($query) {
                        $query->whereHas('categoria', function ($q) {
                            $q->where('nombre', 'Desarrollo Local');
                        });
                    })
                    ->when(auth()->user()->hasRole('equipo_vinculacion_educacion_no_formal'), function ($query) {
                        $query->whereHas('categoria', function ($q) {
                            $q->where('nombre', 'Vinculación Educación No Formal');
                        });
                    })
                    ->select('proyecto.*')
                    ->distinct('proyecto.id')



            )
            ->columns([

                Tables\Columns\TextColumn::make('codigo_proyecto')
                    ->label('Código')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => $record->codigo_proyecto ?: '-')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('numero_dictamen')
                    ->label('N° Dictamen')
                    ->searchable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => $record->numero_dictamen ?: '-')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('nombre_proyecto')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('departamentos_academicos.nombre')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->wrap()
                    ->label('Departamentos')
                    ->searchable(),

                Tables\Columns\TextColumn::make('facultades_centros.nombre')
                    ->badge()
                    ->wrap()
                    ->label('Centros/Facultades'),

                Tables\Columns\TextColumn::make('Estado.tipoestado.nombre')
                    ->badge()
                    ->color(fn(Proyecto $proyecto) => match ($proyecto->estado->tipoestado->nombre) {
                        'En curso' => 'success',
                        'Subsanacion' => 'danger',
                        'Borrador' => 'warning',
                        'Finalizado' => 'info',
                        default => 'primary',
                    })
                    ->label('Estado')
                    ->separator(',')
                    ->wrap()
                    ->label('Estado'),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->date(),

                Tables\Columns\TextColumn::make('poblacion_participante')
                    ->label('Población Participante')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric(),

                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->badge()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Categoría'),



            ])
            ->filters([

                Filter::make('estado_id')
                    ->label('Estado')
                    ->form([
                        Select::make('estado_id')
                            ->label('Estado')
                            ->options(TipoEstado::all()->pluck('nombre', 'id'))
                            ->live()
                            ->multiple(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['estado_id'])) {
                            $query
                                ->whereIn('tipo_estado_id', $data['estado_id'])
                                ->where('es_actual', true);
                        }
                        return $query;
                    }),

                // filtrar por ods
                SelectFilter::make('ods_id')
                    ->label('ODS')
                    ->multiple()
                    ->relationship('ods', 'nombre')
                    ->preload(),

                // Filtro por rango de fechas (inicio y fin)
                Filter::make('rango_fechas')
                    ->label('Rango de Fechas')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('fecha_inicio')
                                    ->label('Fecha de Inicio')
                                    ->displayFormat('d/m/Y')
                                    ->placeholder('Seleccione fecha de inicio')
                                    ->live(),
                                DatePicker::make('fecha_fin')
                                    ->label('Fecha de Fin')
                                    ->displayFormat('d/m/Y')
                                    ->placeholder('Seleccione fecha de fin')
                                    ->live(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['fecha_inicio'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_inicio', '>=', $date),
                            )
                            ->when(
                                $data['fecha_fin'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fecha_finalizacion', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['fecha_inicio'] ?? null) {
                            $indicators[] = 'Desde: ' . \Carbon\Carbon::parse($data['fecha_inicio'])->format('d/m/Y');
                        }
                        if ($data['fecha_fin'] ?? null) {
                            $indicators[] = 'Hasta: ' . \Carbon\Carbon::parse($data['fecha_fin'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),


                SelectFilter::make('categoria_id')
                    ->label('Categoría')
                    ->multiple()
                    ->relationship('categoria', 'nombre')
                    ->preload(),
                SelectFilter::make('modalidad_id')
                    ->label('Modalidad')
                    ->multiple()
                    ->relationship('modalidad', 'nombre')
                    ->preload(),


                // filter name can be anything you want
                Filter::make('created_at')
                    ->form([
                        Select::make('centro_facultad_id')
                            ->label('Centro/Facultad')
                            ->default('asdf')
                            ->options(function () {
                                return FacultadCentro::query()

                                    ->get()
                                    ->pluck('nombre', 'id');
                            })
                            // si el usuario tiene el permiso de admin_centro_facultad-proyectos filtrar por el centro/facultad
                            ->live()
                            ->multiple(),
                        Select::make('departamento_id')
                            ->label('Departamento')
                            ->visible(fn(Get $get) => !empty($get('centro_facultad_id')))
                            ->options(fn(Get $get) => DepartamentoAcademico::query()
                                ->whereIn('centro_facultad_id', $get('centro_facultad_id') ?: [])
                                ->get()
                                ->pluck('nombre', 'id'))
                            ->live()
                            ->multiple(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['centro_facultad_id'])) {
                            $query

                                ->whereIn('centro_facultad_id', $data['centro_facultad_id']);
                        }
                        if (!empty($data['departamento_id'])) {
                            $query
                                ->whereIn('departamento_academico_id', $data['departamento_id']);
                        }
                        return $query;
                    })



            ],  layout: FiltersLayout::AboveContent)
            ->actions([
                Action::make('editarEnlaces')
                    ->label('Editar Firmas')
                    ->icon('heroicon-o-link')
                    ->modalHeading('Reasignar Jefes y Directores')
                    ->modalWidth(MaxWidth::TwoExtraLarge)
                    ->form([
                        Select::make('jefe_id')
                            ->label('Jefe de Departamento')
                            ->options(function ($record) {
                                return \App\Models\Personal\Empleado::query()
                                    ->get()
                                    ->pluck('nombre_completo', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Seleccione el jefe de departamento')
                            ->default(function ($record) {
                                $firma = $record->firma_proyecto_jefe()->first();
                                return $firma?->empleado_id;
                            }),
                        
                        Select::make('director_id')
                            ->label('Decano/Director de Centro')
                            ->options(function ($record) {
                                return \App\Models\Personal\Empleado::query()
                                    ->get()
                                    ->pluck('nombre_completo', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Seleccione el decano o director')
                            ->default(function ($record) {
                                $firma = $record->firma_proyecto_decano()->first();
                                return $firma?->empleado_id;
                            }),
                        
                        Select::make('enlace_id')
                            ->label('Enlace de Vinculación')
                            ->options(function ($record) {
                                return \App\Models\Personal\Empleado::query()
                                    ->get()
                                    ->pluck('nombre_completo', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Seleccione el enlace de vinculación')
                            ->default(function ($record) {
                                $firma = $record->firma_proyecto_enlace()->first();
                                return $firma?->empleado_id;
                            }),
                    ])
                    ->action(function (array $data, $record) {
                        $cambios = [];
                        $empleadosNotificar = [];
                        
                        // Actualizar Jefe de Departamento
                        if (isset($data['jefe_id'])) {
                            $firmaJefe = $record->firma_proyecto_jefe()->first();
                            if ($firmaJefe) {
                                $firmaJefe->update(['empleado_id' => $data['jefe_id']]);
                                $nuevoJefe = \App\Models\Personal\Empleado::find($data['jefe_id']);
                                if ($nuevoJefe) {
                                    $cambios['Jefe de Departamento'] = $nuevoJefe->nombre_completo;
                                    $empleadosNotificar[] = $nuevoJefe;
                                }
                            }
                        }
                        
                        // Actualizar Decano/Director
                        if (isset($data['director_id'])) {
                            $firmaDirector = $record->firma_proyecto_decano()->first();
                            if ($firmaDirector) {
                                $firmaDirector->update(['empleado_id' => $data['director_id']]);
                                $nuevoDirector = \App\Models\Personal\Empleado::find($data['director_id']);
                                if ($nuevoDirector) {
                                    $cambios['Decano/Director de Centro'] = $nuevoDirector->nombre_completo;
                                    $empleadosNotificar[] = $nuevoDirector;
                                }
                            }
                        }
                        
                        // Actualizar Enlace
                        if (isset($data['enlace_id'])) {
                            $firmaEnlace = $record->firma_proyecto_enlace()->first();
                            if ($firmaEnlace) {
                                $firmaEnlace->update(['empleado_id' => $data['enlace_id']]);
                                $nuevoEnlace = \App\Models\Personal\Empleado::find($data['enlace_id']);
                                if ($nuevoEnlace) {
                                    $cambios['Enlace de Vinculación'] = $nuevoEnlace->nombre_completo;
                                    $empleadosNotificar[] = $nuevoEnlace;
                                }
                            }
                        }
                        
                        // Enviar correos si hubo cambios
                        if (!empty($cambios)) {
                            // Notificar al coordinador del proyecto
                            $coordinador = $record->coordinador;
                            if ($coordinador && $coordinador->user && $coordinador->user->email) {
                                try {
                                    \Illuminate\Support\Facades\Mail::to($coordinador->user->email)
                                        ->send(new \App\Mail\FirmasActualizadas($record, $cambios, $coordinador));
                                } catch (\Exception $e) {
                                    \Log::error('Error enviando correo al coordinador: ' . $e->getMessage());
                                }
                            }
                            
                            // Notificar a las nuevas firmas
                            foreach ($empleadosNotificar as $empleado) {
                                if ($empleado->user && $empleado->user->email) {
                                    try {
                                        \Illuminate\Support\Facades\Mail::to($empleado->user->email)
                                            ->send(new \App\Mail\FirmasActualizadas($record, $cambios, $empleado));
                                    } catch (\Exception $e) {
                                        \Log::error('Error enviando correo a firma: ' . $e->getMessage());
                                    }
                                }
                            }
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Enlaces actualizados')
                            ->body('Los enlaces de autoridades han sido actualizados correctamente y se han enviado las notificaciones por correo.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn() => auth()->user()->hasRole(['admin', 'Director/Enlace'])),
                
                Action::make('Proyecto de Vinculación')
                    ->label('Ver')
                    ->modalContent(
                        fn(Proyecto $proyecto): View =>
                        view(
                            'components.fichas.ficha-proyecto-vinculacion',
                            ['proyecto' => $proyecto->load(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye'])]
                        )
                    )
                    // ->stickyModalHeader()
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->modalSubmitAction(false),


            ])
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')
                        ->fromTable()
                        ->withColumns([
                            \pxlrbt\FilamentExcel\Columns\Column::make('codigo_proyecto')
                                ->heading('Código'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('numero_dictamen')
                                ->heading('N° Dictamen'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('nombre_proyecto')
                                ->heading('Nombre del Proyecto'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('fecha_inicio')
                                ->heading('Fecha Inicio'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('fecha_finalizacion')
                                ->heading('Fecha Finalización'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('integrantes')
                                ->heading('Integrantes del Proyecto')
                                ->formatStateUsing(fn($record) => $record->empleado_proyecto->pluck('empleado.nombre_completo')->implode(', ')),
                            \pxlrbt\FilamentExcel\Columns\Column::make('categoria')
                                ->heading('Categoría')
                                ->formatStateUsing(fn($record) => $record->categoria->pluck('nombre')->implode(', ')),
                            \pxlrbt\FilamentExcel\Columns\Column::make('estado')
                                ->heading('Estado')
                                ->formatStateUsing(fn($record) => $record->estado?->tipoestado?->nombre ?? '-'),
                        ])
                        ->askForFilename('Proyectos de Vinculación')
                        ->askForWriterType(),
                ])
                    ->label('Exportar a Excel')
                    ->color('success')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.list-proyectos-vinculacion')
            ;//->layout('components.panel.modulos.modulo-proyectos');
    }
}

<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Http\Controllers\Docente\VerificarConstancia;
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
use App\Models\Estado\TipoEstado;


use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;


use Filament\Notifications\Notification;

use App\Models\Proyecto\CargoFirma;


use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;

use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use Filament\Forms\Get;


class ListProyectoRevisionFinal extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Proyecto::query()
                    ->whereIn('proyecto.id', function ($query) {
                        $query->select('estadoable_id')
                            ->from('estado_proyecto')
                            ->where('estadoable_type', Proyecto::class) // Asegúrate de filtrar por el modelo `Proyecto`
                            ->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision final')->first()->id)
                            ->where('es_actual', true);
                    })
                    ->leftJoin('proyecto_centro_facultad', 'proyecto_centro_facultad.proyecto_id', '=', 'proyecto.id')
                    ->leftJoin('proyecto_depto_ac', 'proyecto_depto_ac.proyecto_id', '=', 'proyecto.id')
                    // unir con la tabla de estados
                    ->leftJoin('estado_proyecto', 'estado_proyecto.estadoable_id', '=', 'proyecto.id')
                    ->leftJoin('tipo_estado', 'estado_proyecto.tipo_estado_id', '=', 'tipo_estado.id')
                    // si  el usuario tiene el permiso de admin_centro_facultad-proyectos filtrar por el centro/facultad

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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap()
                    ->label('Departamentos')
                    ->searchable(),

                Tables\Columns\TextColumn::make('facultades_centros.nombre')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap()
                    ->label('Centros/Facultades'),



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

            ])
            ->filters([
                // filtrar por ods
                SelectFilter::make('ods_id')
                    ->label('ODS')
                    ->multiple()
                    ->relationship('ods', 'nombre')
                    ->preload(),


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

                Action::make('first')
                    ->label('Ver')
                    ->modalContent(
                        fn(Proyecto $proyecto) =>  view(
                            'components.fichas.ficha-proyecto-vinculacion',
                            ['proyecto' => $proyecto->load(['aporteInstitucional', 'presupuesto', 'ods', 'metasContribuye'])]
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
                                    ->required()
                                    ->label('Comentario')
                                    ->columnSpanFull(),
                            ])
                            ->icon('heroicon-o-x-circle') // Icono para "Rechazar"
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading('Confirmar Rechazo') // Título del diálogo

                            ->modalSubheading('¿Estás seguro de que deseas Rechazar la firma de este proyecto?')
                            ->action(function (Proyecto $record,  array $data) {
                                //  cambiar todos los estados de la revision a Pendiente
                                $record->firma_proyecto()->update([
                                    'estado_revision' => 'Pendiente',
                                    'firma_id' => null,
                                    'sello_id' => null,
                                    'fecha_firma' => null,

                                ]);
                                // eliminar al  $proyecto->firma_revisor_vinculacion()->create([

                                $record->firma_revisor_vinculacion()->delete();

                                // quitar al responsable de la revision
                                $record->responsable_revision_id = null;
                                // quitar el numero de dictamen
                                $record->numero_dictamen = null;
                                // quitar la fecha de aprobacion
                                $record->fecha_aprobacion = null;
                                // quitar la fecha de registro
                                $record->fecha_registro = null;
                                // quitar el nuro de libro
                                $record->numero_libro = null;

                                // quitar el numero de tomo
                                $record->numero_tomo = null;

                                // quitar el numero de folio
                                $record->numero_folio = null;

                                $record->save();
                                // quitar las categorias
                                $record->categoria()->detach();

                                // quitar los ods
                                $record->ods()->detach();



                                $record->estado_proyecto()->create([
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
                                    ->body('Proyecto Rechazado')
                                    ->info()
                                    ->send();

                                //recargar la pagina con js

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
                            ->modalSubheading('Para aprobar el proyecto, por favor presione el botón "Aprobar"')
                            ->action(function (Proyecto $proyecto, array $data) {
                                // dd($this->docente);

                                

                                // actualizar el estado del proyecto al siguiente estado :)
                                $proyecto->estado_proyecto()->create([
                                    'empleado_id' => Auth::user()->empleado->id,
                                    'tipo_estado_id' => TipoEstado::where('nombre', 'En curso')->first()->id,
                                    'fecha' => now(),
                                    'comentario' => 'El proyecto ha cambiado de estado a En curso',
                                ]);

                                $proyecto->firma_proyecto()->updateOrCreate(
                                    [
                                        'empleado_id' => auth()->user()->empleado->id,
                                        'cargo_firma_id' => CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                                            ->where('tipo_cargo_firma.nombre', 'Director Vinculacion')
                                            ->where('cargo_firma.descripcion', 'Proyecto')
                                            ->first()->id,
                                    ],
                                    [
                                        'estado_revision' => 'Aprobado',
                                        'firma_id' => auth()->user()?->empleado?->firma?->id,
                                        'sello_id' => auth()->user()?->empleado?->sello?->id,
                                        'hash' => 'hash',
                                        'fecha_firma' => now(),
                                    ]
                                );

                               // $proyecto->user_director_id = Auth::user()->empleado->id;
                                $proyecto->save();
                                $proyecto->update($data);
                                
                                VerificarConstancia::makeConstanciasProyecto($proyecto);
                                // dd(FirmaProyecto::where('proyecto_id', $proyecto->id)
                                // ->where('empleado_id', $this->docente->id)
                                // ->first());
                                Notification::make()
                                    ->title('¡Realizado!')
                                    ->body('Proyecto Aprobado correctamente')
                                    ->info()
                                    ->send();
                            })
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
        return view('livewire.proyectos.vinculacion.list-proyecto-revision-final')
            ;//->layout('components.panel.modulos.modulo-proyectos');
    }
}

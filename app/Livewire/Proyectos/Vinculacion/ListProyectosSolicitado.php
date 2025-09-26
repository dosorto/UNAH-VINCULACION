<?php

namespace App\Livewire\Proyectos\Vinculacion;

use Filament\Tables;
use Filament\Forms\Get;
use Livewire\Component;
use Filament\Tables\Table;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\Proyecto;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use App\Models\Proyecto\FirmaProyecto;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Contracts\HasTable;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use App\Models\Proyecto\CargoFirma;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\UnidadAcademica\FacultadCentro;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use Filament\Forms\Components\Section;

use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

// Imports para notificaciones por correo
use Illuminate\Support\Facades\Mail;
use App\Mail\ProyectoEstadoCambiado;

class ListProyectosSolicitado extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {


        return $table
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')
                        ->fromTable()

                        ->askForFilename('Proyectos de Vinculación')
                        ->askForWriterType(),
                ])
                    ->label('Exportar a Excel')
                    ->color('success')
            ])
            ->query(
                Proyecto::query()
                    ->whereIn('proyecto.id', function ($query) {
                        $query->select('estadoable_id')
                            ->from('estado_proyecto')
                            ->where('estadoable_type', Proyecto::class) // Asegúrate de filtrar por el modelo `Proyecto`
                            ->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision')->first()->id)
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



                                $record->estado_proyecto()->create([
                                    'empleado_id' => Auth::user()->empleado->id,
                                    'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')->first()->id,
                                    'fecha' => now(),
                                    'comentario' => $data['comentario'],
                                ]);

                                // Recargar el proyecto con todas sus relaciones para el correo
                                $record->refresh();
                                $record->load(['coordinador_proyecto.empleado.user']);

                                // Enviar notificación por correo al coordinador del proyecto
                                try {
                                    if ($record->coordinador && $record->coordinador->user) {
                                        Mail::to($record->coordinador->user->email)->send(
                                            new ProyectoEstadoCambiado(
                                                $record, 
                                                $record->coordinador->user, 
                                                'Subsanación', 
                                                $data['comentario'],
                                                'rechazo'
                                            )
                                        );
                                        
                                        \Log::info('Correo de rechazo enviado exitosamente para el proyecto: ' . $record->nombre_proyecto);
                                    } else {
                                        \Log::warning('No se pudo enviar correo de rechazo: coordinador o usuario no encontrado para el proyecto ID: ' . $record->id);
                                    }
                                } catch (\Exception $emailException) {
                                    \Log::error('Error al enviar correo de proyecto rechazado para proyecto ID ' . $record->id . ': ' . $emailException->getMessage());
                                }

                                // dd(FirmaProyecto::where('proyecto_id', $proyecto->id)
                                // ->where('empleado_id', $this->docente->id)
                                // ->first());
                                Notification::make()
                                    ->title('¡Realizado!')
                                    ->body('Proyecto Rechazado y notificación enviada al coordinador')
                                    ->warning()
                                    ->send();
                            })
                            ->cancelParentActions()
                            ->button(),

                        Action::make('aprobar')
                            ->label('Aprobar')
                            ->form([
                                Section::make('Formulario Final')
                                    ->description('')
                                    ->schema([
                                        Hidden::make('responsable_revision_id')
                                            ->default(auth()->user()->empleado->id),
                                        TextInput::make('numero_dictamen')
                                            ->label('Numero de dictamen')
                                            ->disabled()
                                            ->columnSpan(1)
                                            ->default(function (Proyecto $proyecto) {
                                                $prefix = 'DICTAMEN-VRA-DVUS'. '-' . $proyecto->coordinador->centro_facultad->siglas;
                                                $year = date('Y'); // Obtiene el año actual
                                                $nextId = $proyecto ? $proyecto->id + 1 : 1; // Incrementa el ID o lo inicia en 1
                                                $formattedId = str_pad($nextId, 3, '0', STR_PAD_LEFT); // Formatea el ID a 3 dígitos

                                                return "{$prefix}-{$year}-{$formattedId}";
                                            }),

                                        TextInput::make('codigo_proyecto')
                                            ->label('Código del Proyecto')
                                            ->disabled()
                                            ->columnSpan(1)
                                            ->default(function (Proyecto $proyecto) {
                                                $prefix = 'VRA-DVUS'. '-' . $proyecto->coordinador->centro_facultad->siglas;
                                                $year = date('Y'); // Obtiene el año actual
                                                $nextId = $proyecto ? $proyecto->id + 1 : 1; // Incrementa el ID o lo inicia en 1
                                                $formattedId = str_pad($nextId, 3, '0', STR_PAD_LEFT); // Formatea el ID a 3 dígitos

                                                return "{$prefix}-{$year}-{$formattedId}";
                                            }),
                                        /*Select::make('categoria_id')
                                            ->label('Categoría')
                                            ->multiple()
                                            ->relationship(name: 'categoria', titleAttribute: 'nombre')
                                            ->required()
                                            ->preload(),
                                        Select::make('ods')
                                            ->label('ODS')
                                            ->multiple()
                                            ->relationship('ods', 'nombre')
                                            ->visible(fn(?Proyecto $record) 
                                            => $record && $record->estado && $record->estado->tipoestado && $record->estado->tipoestado->nombre === 'En revision')
                                            ->columnSpanFull()
                                            ->preload()
                                            ->required(),
                                        DatePicker::make('fecha_aprobacion')
                                            ->label('Fecha de aprobación.')
                                            ->columnSpan(1)
                                            ->required(),
                                        TextInput::make('numero_libro')
                                            ->label('Número de libro.')
                                            ->numeric(),
                                        TextInput::make('numero_tomo')
                                            ->label('Número de tomo.')
                                            ->numeric(),
                                        TextInput::make('numero_folio')
                                            ->label('Número de folio.')
                                            ->numeric(), */
                                        Hidden::make('numero_dictamen')
                                            ->default(function (Proyecto $proyecto) {
                                                $prefix = 'DICTAMEN-VRA-DVUS'. '-' . $proyecto->coordinador->centro_facultad->siglas;
                                                $year = date('Y'); // Obtiene el año actual
                                                $nextId = $proyecto ? $proyecto->id + 1 : 1; // Incrementa el ID o lo inicia en 1
                                                $formattedId = str_pad($nextId, 3, '0', STR_PAD_LEFT); // Formatea el ID a 3 dígitos

                                                return "{$prefix}-{$year}-{$formattedId}";
                                            }),

                                        Hidden::make('codigo_proyecto')
                                            ->default(function (Proyecto $proyecto) {
                                                $prefix = 'VRA-DVUS'. '-' . $proyecto->coordinador->centro_facultad->siglas;
                                                $year = date('Y'); // Obtiene el año actual
                                                $nextId = $proyecto ? $proyecto->id + 1 : 1; // Incrementa el ID o lo inicia en 1
                                                $formattedId = str_pad($nextId, 3, '0', STR_PAD_LEFT); // Formatea el ID a 3 dígitos

                                                return "{$prefix}-{$year}-{$formattedId}";
                                            }),
                                    ])
                                    ->label('')
                                    ->columns(2),


                            ])
                            ->cancelParentActions()
                            ->icon('heroicon-o-check-circle') // Icono para "Aprobar"
                            ->color('success')
                            ->requiresConfirmation()
                            ->modalHeading('Terminar Registro') // Título del diálogo
                            ->modalSubheading('Para aprobar el proyecto, por favor llene los siguientes campos:')
                            ->action(function (Proyecto $proyecto, array $data) {
                                // dd($this->docente);

                                // actualizar el estado del proyecto al siguiente estado :)
                                $estadoProyecto = $proyecto->estado_proyecto()->create([
                                    'empleado_id' => Auth::user()->empleado->id,
                                    'tipo_estado_id' => TipoEstado::where('nombre', 'En revision final')->first()->id,
                                    'fecha' => now(),
                                    'comentario' => 'El proyecto ha sido enviado a revisión final.',
                                ]);
                                
                                $proyecto->firma_proyecto()->updateOrCreate(
                                    [
                                        'empleado_id' => auth()->user()->empleado->id,
                                        'cargo_firma_id' => CargoFirma::join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
                                            ->where('tipo_cargo_firma.nombre', 'Revisor Vinculacion')
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

                                $proyecto->update($data);

                                // Recargar el proyecto con todas sus relaciones para el correo
                                $proyecto->refresh();
                                $proyecto->load(['coordinador_proyecto.empleado.user']);

                                // Enviar notificación por correo al coordinador del proyecto
                                try {
                                    if ($proyecto->coordinador && $proyecto->coordinador->user) {
                                        Mail::to($proyecto->coordinador->user->email)->send(
                                            new ProyectoEstadoCambiado(
                                                $proyecto, 
                                                $proyecto->coordinador->user, 
                                                'En revisión final', 
                                                'Su proyecto ha sido aprobado exitosamente y enviado a revisión final. Recibirá notificaciones sobre el progreso del proceso.',
                                                'aprobación'
                                            )
                                        );
                                        
                                        \Log::info('Correo de aprobación enviado exitosamente para el proyecto: ' . $proyecto->nombre_proyecto);
                                    } else {
                                        \Log::warning('No se pudo enviar correo de aprobación: coordinador o usuario no encontrado para el proyecto ID: ' . $proyecto->id);
                                    }
                                } catch (\Exception $emailException) {
                                    \Log::error('Error al enviar correo de proyecto aprobado para proyecto ID ' . $proyecto->id . ': ' . $emailException->getMessage());
                                }

                                // dd(FirmaProyecto::where('proyecto_id', $proyecto->id)
                                // ->where('empleado_id', $this->docente->id)
                                // ->first());
                                Notification::make()
                                    ->title('¡Realizado!')
                                    ->body('Proyecto Aprobado correctamente y notificación enviada al coordinador')
                                    ->success()
                                    ->send();
                            })
                            ->modalWidth(MaxWidth::SevenExtraLarge)
                            ->button(),

                    ])
            ])
            ->filters([



              
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }


    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.list-proyectos-vinculacion-solicitados')
            ;//->layout('components.panel.modulos.modulo-proyectos');
    }
}

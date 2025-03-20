<?php

namespace App\Livewire\Docente\Proyectos;

use App\Http\Controllers\Docente\VerificarConstancia;
use Filament\Tables;
use Livewire\Component;
use Filament\Tables\Table;
use Illuminate\Support\Str;

use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use PHPUnit\Framework\Reorderable;
use App\Models\Proyecto\CargoFirma;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\File;
use App\Models\Estado\EstadoProyecto;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Crypt;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;

use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

use Filament\Tables\Filters\Layout;
use Filament\Tables\Filters\SelectFilter;

use Filament\Tables\Enums\FiltersLayout;

class ProyectosDocenteList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    public Empleado $docente;

    public function mount(Empleado $docente): void
    {
        $this->docente = $docente;
    }
  
    public function table(Table $table): Table
    {
        return $table
            ->recordClasses(function (Proyecto $proyecto) {
                if ($proyecto->estado->tipoestado->nombre == 'Subsanacion') {
                    return 'bg-red-100 border-4 border-red-600 dark:bg-red-900 dark:border-red-400';


                }
                
               
                
            })
            ->striped()
            ->query(

                Proyecto::query()
                    ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
                    ->select('proyecto.*')
                    ->where('empleado_proyecto.empleado_id', $this->docente->id)
                    ->distinct()
            )
            ->columns([

                Tables\Columns\TextColumn::make('nombre_proyecto')
                    ->limit(30)
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('docentes_proyecto.rol')
                    ->label('Rol')
                    ->formatStateUsing(
                        function (Proyecto $record) {
                            return $record->docentes_proyecto()
                                ->where('empleado_id', $this->docente->id)
                                ->first()->rol;
                        }
                    )
                    ->limit(30)
                    ->wrap()
                    ->searchable(),


                Tables\Columns\TextColumn::make('Estado.tipoestado.nombre')
                    ->badge()
                    ->color(fn (Proyecto $proyecto) => match ($proyecto->estado->tipoestado->nombre) {
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
                SelectFilter::make('categoria_id')
                    ->label('Categoría')
                    ->multiple()
                    ->relationship('categoria', 'nombre')
                    ->preload(),

                SelectFilter::make('rol')
                    ->label('Rol')
                    ->options([
                        'Coordinador' => 'Coordinador',
                        'Integrante' => 'Integrante',
                    ]),

                    
            ],  layout: FiltersLayout::AboveContent)
            ->actions([
                ActionGroup::make([
                    Action::make('Proyecto de Vinculación')
                        ->label('Ver Proyecto')
                        ->icon('heroicon-o-eye')
                        ->color('primary')
                        ->stickyModalFooter()
                        ->stickyModalHeader()
                        ->modalContent(
                            fn(Proyecto $proyecto): View =>
                            view(
                                'components.fichas.ficha-proyecto-vinculacion',
                                ['proyecto' => $proyecto]
                            )
                        )
                        // ->stickyModalHeader()
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->extraModalFooterActions([
                            Action::make('third')
                                ->label(
                                    'Subir Informe Intermedio'
                                    //     function (Proyecto $proyecto) {
                                    //     return $proyecto->estado->tipoestado->nombre == 'En curso' ? 'Subsanar' : 'Revisar';
                                    // }
                                )
                                ->form([

                                    Repeater::make('documentos')
                                        ->schema([
                                            Hidden::make('tipo_documento')
                                                ->default('Informe Intermedio'),
                                            TextInput::make('dd')
                                                ->label('Tipo de Informe')
                                                ->disabled()
                                                ->default('Informe Intermedio'),

                                            FileUpload::make('documento_url')
                                                ->label('Informe Intermedio')
                                                ->required()
                                                ->label('Informe Intermedio')
                                                ->acceptedFileTypes(['application/pdf'])
                                        ])
                                        ->addable(false)
                                        ->reorderable(false)
                                        ->deletable(false)
                                ])
                                ->action(function (Proyecto $proyecto, array $data) {
                                    // eliminar las firmas de los documentos intermedios anteriores
                                    $proyecto->documentos()
                                        ->where('tipo_documento', 'Informe Intermedio')
                                        ->each(function ($documento) {
                                            $documento->firma_documento()->delete();
                                        });

                                    // eliminar los estados de los documentos intermedios anteriores
                                    $proyecto->documentos()
                                        ->where('tipo_documento', 'Informe Intermedio')
                                        ->each(function ($documento) {
                                            $documento->estado_documento()->delete();
                                        });
                                    // eliminar todos los documentos intermedios anteriores
                                    $proyecto->documentos()
                                        ->where('tipo_documento', 'Informe Intermedio')
                                        ->delete();

                                    // crear el documento intermedio 
                                    $documentoIntermedio = $proyecto->documentos()->create([
                                        'tipo_documento' => $data['documentos'][0]['tipo_documento'],
                                        'documento_url' => $data['documentos'][0]['documento_url'],
                                    ]);
                                    $cargosFirmas = CargoFirma::where('descripcion', 'Documento_intermedio')
                                        ->get();
                                    $cargosFirmas->each(function ($cargo) use ($proyecto, $documentoIntermedio) {
                                        $documentoIntermedio->firma_documento()->create([
                                            'empleado_id' => $proyecto->getFirmabyCargo($cargo->tipoCargoFirma->nombre)->empleado->id,
                                            'cargo_firma_id' => $cargo->id,
                                            'estado_revision' => 'Pendiente',
                                            'hash' => 'hash'
                                        ]);
                                    });
                                    $documentoIntermedio->estado_documento()->create([
                                        'empleado_id' => auth()->user()->empleado->id,
                                        'tipo_estado_id' => TipoEstado::where('nombre', 'Enlace Vinculacion')->first()->id,
                                        'fecha' => now(),
                                        'comentario' => 'Documento creado',
                                    ]);

                                    // dd($proyecto->documento_intermedio());
                                })
                                ->icon('heroicon-o-document-arrow-up') // Icono para "Rechazar"
                                ->color('success')
                                ->modalHeading('Documentos del Proyecto')
                                ->modalSubheading('A continuación se muestran los documentos del proyecto y su estado')
                                ->visible(function (Proyecto $proyecto) {
                                    return ((($proyecto->estado->tipoestado->nombre == 'En curso' &&
                                        is_null($proyecto->documento_intermedio())) ||
                                        $proyecto->documento_intermedio()?->estado?->tipoestado?->nombre == 'Subsanacion')
                                        && $proyecto->coordinador->id == auth()->user()->empleado->id);
                                })
                                ->modalWidth(MaxWidth::SevenExtraLarge),



                            // informe final logica
                            Action::make('quinto')
                                ->label(
                                    'Informe Final'
                                )
                                ->form([
                                    Repeater::make('documentos')
                                        ->schema([
                                            Hidden::make('tipo_documento')
                                                ->default('Informe Final'),
                                            TextInput::make('informe_final')
                                                ->label('Tipo de Informe')
                                                ->disabled()
                                                ->default('Informe Final'),

                                            FileUpload::make('documento_url')
                                                ->label('Informe Final')
                                                ->required()
                                                ->acceptedFileTypes(['application/pdf'])
                                        ])
                                        ->addable(false)
                                        ->reorderable(false)
                                        ->deletable(false)
                                ])
                                ->action(function (Proyecto $proyecto, array $data) {
                                    // eliminar las firmas de los documentos intermedios anteriores
                                    $proyecto->documentos()
                                        ->where('tipo_documento', 'Informe Final')
                                        ->each(function ($documento) {
                                            $documento->firma_documento()->delete();
                                        });

                                    // eliminar los estados de los documentos intermedios anteriores
                                    $proyecto->documentos()
                                        ->where('tipo_documento', 'Informe Final')
                                        ->each(function ($documento) {
                                            $documento->estado_documento()->delete();
                                        });
                                    // eliminar todos los documentos intermedios anteriores
                                    $proyecto->documentos()
                                        ->where('tipo_documento', 'Informe Final')
                                        ->delete();

                                    // crear el documento intermedio 
                                    $documentoIntermedio = $proyecto->documentos()->create([
                                        'tipo_documento' => $data['documentos'][0]['tipo_documento'],
                                        'documento_url' => $data['documentos'][0]['documento_url'],
                                    ]);
                                    $cargosFirmas = CargoFirma::where('descripcion', 'Documento_final')
                                        ->get();
                                    $cargosFirmas->each(function ($cargo) use ($proyecto, $documentoIntermedio) {
                                        $documentoIntermedio->firma_documento()->create([
                                            'empleado_id' => $proyecto->getFirmabyCargo($cargo->tipoCargoFirma->nombre)->empleado->id,
                                            'cargo_firma_id' => $cargo->id,
                                            'estado_revision' => 'Pendiente',
                                            'hash' => 'hash'
                                        ]);
                                    });
                                    $documentoIntermedio->estado_documento()->create([
                                        'empleado_id' => auth()->user()->empleado->id,
                                        'tipo_estado_id' => TipoEstado::where('nombre', 'Enlace Vinculacion')->first()->id,
                                        'fecha' => now(),
                                        'comentario' => 'Documento creado',
                                    ]);
                                })
                                ->icon('heroicon-o-document-arrow-up') // Icono para "Rechazar"
                                ->color('success')
                                ->modalHeading('Documentos del Proyecto')
                                ->modalSubheading('A continuación se muestran los documentos del proyecto y su estado')
                                ->visible(function (Proyecto $proyecto) {
                                    return ((($proyecto->estado->tipoestado->nombre == 'En curso' &&
                                        $proyecto->documento_intermedio()?->estado?->tipoestado?->nombre == 'Aprobado') &&
                                        is_null($proyecto->documento_final())
                                        ||
                                        $proyecto->documento_final()?->estado?->tipoestado?->nombre == 'Subsanacion')
                                        && $proyecto->coordinador->id == auth()->user()->empleado->id);
                                })
                                ->modalWidth(MaxWidth::SevenExtraLarge),

                        ])
                        ->modalSubmitAction(false),

                    Action::make('subsanacion')
                        ->label(
                            fn(Proyecto $proyecto): string =>
                            $proyecto->estado->tipoestado->nombre == 'Subsanacion' ? 'Subsanar' : 'Editar Borrador'
                        )
                        ->icon('heroicon-o-document')
                        ->color('warning')
                        ->visible(function (Proyecto $proyecto) {
                            $condicion = ($proyecto->estado->tipoestado->nombre == 'Subsanacion' ||
                                $proyecto->estado->tipoestado->nombre == 'Borrador')
                                && $proyecto->coordinador->id == auth()->user()->empleado->id;

                            return  $condicion;
                        })
                        ->url(fn(Proyecto $record): string => route('editarProyectoVinculacion', $record)),
                    Action::make('constancia')
                        ->label('Constancia de Inscripción')
                        ->icon('heroicon-o-document')
                        ->color('info')
                        ->visible(function (Proyecto $proyecto) {
                            return VerificarConstancia::validarConstanciaEmpleado($proyecto->docentes_proyecto()
                                ->where('empleado_id', $this->docente->id)
                                ->first());
                        })
                        ->action(function (Proyecto $proyecto) {
                            return VerificarConstancia::CrearPdfInscripcion($proyecto->docentes_proyecto()
                                ->where('empleado_id', $this->docente->id)
                                ->first());
                        }),
                    Action::make('constancia_finalizacion')
                        ->label('Constancia de Finalización')
                        ->icon('heroicon-o-document')
                        ->color('info')
                        ->visible(function (Proyecto $proyecto) {
                            return VerificarConstancia::validarConstanciaFinalizacion($proyecto->docentes_proyecto()
                                ->where('empleado_id', $this->docente->id)
                                ->first());
                        })
                        ->action(function (Proyecto $proyecto) {
                            return VerificarConstancia::CrearPdfFinalizacion($proyecto->docentes_proyecto()
                                ->where('empleado_id', $this->docente->id)
                                ->first());
                        }),
                ])

                    ->button()
                    ->color('primary')
                    ->label('Acciones'),



                // ->openUrlInNewTab()
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.docente.proyectos.proyectos-docente-list');
    }
}

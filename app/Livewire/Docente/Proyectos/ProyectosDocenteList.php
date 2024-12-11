<?php

namespace App\Livewire\Docente\Proyectos;

use Filament\Tables;
use Livewire\Component;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
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
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

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
            ->query(

                Proyecto::query()
                    ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
                    ->select('proyecto.*')
                    ->where('empleado_proyecto.empleado_id', $this->docente->id)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nombre_proyecto')
                    ->searchable(),

                Tables\Columns\TextColumn::make('Estado.tipoestado.nombre')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->wrap()
                    ->label('Estado'),
                Tables\Columns\TextColumn::make('facultades_centros.nombre')
                    ->badge()
                    ->wrap()
                    ->label('Centro/Facultad'),




            ])
            ->filters([
                //
            ])
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
                                ->label(function (Proyecto $proyecto) {
                                    return $proyecto->estado->tipoestado->nombre == 'En curso' ? 'Subsanar' : 'Revisar';
                                })
                                ->form([

                                    Repeater::make('documentos')
                                        ->schema([
                                            Hidden::make('tipo_documento')
                                                ->default('Intermedio'),
                                            TextInput::make('dd')
                                                ->label('Tipo de Informe')
                                                ->disabled()
                                                ->default('Intermedio'),

                                            FileUpload::make('documento_url')
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
                                        ->where('tipo_documento', 'Intermedio')
                                        ->each(function ($documento) {
                                            $documento->firma_documento()->delete();
                                        });

                                    // eliminar los estados de los documentos intermedios anteriores
                                    $proyecto->documentos()
                                        ->where('tipo_documento', 'Intermedio')
                                        ->each(function ($documento) {
                                            $documento->estado_documento()->delete();
                                        });
                                    // eliminar todos los documentos intermedios anteriores
                                    $proyecto->documentos()
                                        ->where('tipo_documento', 'Intermedio')
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
                                })
                                ->icon('heroicon-o-document-arrow-up') // Icono para "Rechazar"
                                ->color('success')
                                ->modalHeading('Documentos del Proyecto')
                                ->modalSubheading('A continuación se muestran los documentos del proyecto y su estado')
                                ->visible(function (Proyecto $proyecto) {
                                    return ($proyecto->estado->tipoestado->nombre == 'En curso' &&
                                        is_null($proyecto->documento_intermedio)) ||
                                        $proyecto->documento_intermedio?->estado?->tipoestado?->nombre == 'Subsanacion';
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
                                                ->default('Final'),
                                            TextInput::make('informe_final')
                                                ->label('Tipo de Informe')
                                                ->disabled()
                                                ->default('Intermedio'),

                                            FileUpload::make('documento_url')
                                                ->label('Informe Final')
                                                ->acceptedFileTypes(['application/pdf'])
                                        ])
                                        ->addable(false)
                                        ->reorderable(false)
                                        ->deletable(false)
                                ])
                                ->action(function (Proyecto $proyecto, array $data) {
                                    // eliminar las firmas de los documentos intermedios anteriores
                                    $proyecto->documentos()
                                        ->where('tipo_documento', 'Final')
                                        ->each(function ($documento) {
                                            $documento->firma_documento()->delete();
                                        });

                                    // eliminar los estados de los documentos intermedios anteriores
                                    $proyecto->documentos()
                                        ->where('tipo_documento', 'Final')
                                        ->each(function ($documento) {
                                            $documento->estado_documento()->delete();
                                        });
                                    // eliminar todos los documentos intermedios anteriores
                                    $proyecto->documentos()
                                        ->where('tipo_documento', 'Final')
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
                                    return ($proyecto->estado->tipoestado->nombre == 'En curso' &&
                                        $proyecto->documento_intermedio?->estado?->tipoestado?->nombre == 'Aprobado') &&
                                        is_null($proyecto->documento_final)
                                        ||
                                        $proyecto->documento_final?->estado?->tipoestado?->nombre == 'Subsanacion';
                                })
                                ->modalWidth(MaxWidth::SevenExtraLarge),

                        ])
                        ->modalSubmitAction(false),

                        Action::make('subsanacion')
                        ->label(fn (Proyecto $proyecto) : string => 
                           $proyecto->estado->tipoestado->nombre == 'Subsanacion' ? 'Subsanar' : 'Editar Borrador'
                        )
                        ->icon('heroicon-o-document')
                        ->color('warning')
                        ->visible(function (Proyecto $proyecto) {
                            return $proyecto->estado->tipoestado->nombre == 'Subsanacion';
                        })
                        ->url(fn (Proyecto $record): string => route('editarProyectoVinculacion', $record)),
                        Action::make('constancia')
                            ->label('Constancia')
                            ->icon('heroicon-o-document')
                            ->color('info')
                            ->visible(function (Proyecto $proyecto) {
                                return $proyecto->estado->tipoestado->nombre == 'En curso';
                            })
                            ->action(function (Proyecto $proyecto) {
                                
                                // genear la constancia pdf

                                // Hashear los IDs
                                $hashedProjectId = Crypt::encrypt($proyecto->id);
                                $hashedEmployeeId = Crypt::encrypt(auth()->user()->empleado->id);

                                // Generar el código QR como imagen base64
                                $qrCode = QrCode::format('png')->size(150)->generate(url('/verificacion_constancia/' . $hashedProjectId. '/' . $hashedEmployeeId));
                                $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCode);
                                
                                // Cargar las imágenes y convertirlas a base64
                                $logoPath = resource_path('views/pdf/imagen1.jpg'); // Ruta de la imagen
                                $logoBase64 = base64_encode(File::get($logoPath));

                                // Datos que se pasan a la vista
                                $data = [
                                    'title' => 'Constancia de Participación',
                                    'proyecto' => $proyecto,
                                    'empleado' => auth()->user()->empleado,
                                    'qrCode' => $qrCodeBase64, 
                                    'logoBase64' => $logoBase64,  // Agregar imagen base64
                                ];
                                
                                // Generar el PDF desde una vista
                                $pdf = Pdf::loadView('pdf.constancia', $data);

                                // Generar un nombre único para el archivo basandome el los id del empleado en el proyecto (que deberia ser unica esa combinacion por logica)
                                $fileName = 'constancia_' . $proyecto->id . '_' . auth()->user()->empleado->id . '_' . Str::random(8) . '.pdf';

                                // Definir la ruta con el nombre único
                                $filePath = storage_path('app/public/' . $fileName);

                                // dd($filePath);
                                // Guardar el PDF en la ruta
                                $pdf->save($filePath);

                                // Descargar el PDF y eliminarlo al instante para que no quede en storage
                                return response()
                                        ->download($filePath, 'Constancia_Participacion.pdf')
                                        ->deleteFileAfterSend(true);
    
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

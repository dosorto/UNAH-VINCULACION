<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\CargoFirma;
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
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Repeater;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use PHPUnit\Framework\Reorderable;

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
                                ->label('Agregar Documento Intermedio')
                                ->form([

                                    Repeater::make('documentos')
                                        ->schema([
                                            Hidden::make('tipo_documento')
                                                ->default('Intermedio'),
                                            TextInput::make('dd')
                                                ->disabled()
                                                ->default('Intermedio'),

                                            FileUpload::make('documento_url')
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

                        ])
                        ->modalSubmitAction(false),
                ])
                    ->button()
                    ->color('primary')
                    ->label('Acciones')

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

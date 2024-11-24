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
                            Action::make('second')
                                ->label('Ver documentos')
                                ->modalContent(
                                    fn(Proyecto $proyecto): View =>
                                    view(
                                        'app.docente.proyectos.ver-documentos',
                                        ['proyecto' => $proyecto]
                                    )
                                )
                                ->icon('heroicon-o-document-arrow-up') // Icono para "Rechazar"
                                ->color('success')
                                ->modalHeading('Documentos del Proyecto')
                                ->modalSubheading('A continuación se muestran los documentos del proyecto y su estado')
                                ->visible(function (Proyecto $proyecto) {
                                    return $proyecto->estado->tipoestado->nombre == 'En curso';
                                })
                                ->modalSubmitAction(false)
                                ->modalWidth(MaxWidth::SevenExtraLarge),
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
                                    $revisoresIntermedio = collect(config('nexo.revisores_documento_intermedio'));
                                    // convertir el arreglo a colec
                                    // crear el documento
                                    $documento = $proyecto->documentos()->create(
                                        [
                                            'tipo_documento' => $data['documentos'][0]['tipo_documento'],
                                            'documento_url' => $data['documentos'][0]['documento_url'],
                                        ]
                                    );
                                    $revisoresIntermedio->each(function ($revisor) use ($documento, $proyecto) {
                                        $firmaProyecto = $proyecto->firma_proyecto()
                                            ->where('cargo_firma_id', CargoFirma::where('nombre', $revisor)
                                                ->first()
                                                ->id)
                                            ->first();
                                        $documento->firma_documento()->create([
                                            'empleado_id' => $firmaProyecto->empleado_id,
                                            'cargo_firma_id' => CargoFirma::where('nombre', $revisor)->first()->id,
                                            'estado_revision' => 'Pendiente',
                                            'estado_actual_id' => TipoEstado::where('nombre', 'Esperando Firma ' . $revisor)->first()->id,
                                            'hash' => 'hash'
                                        ]);
                                    });

                                    /*
                                    $cocumento = $proyecto->documentos()->create(
                                        [
                                            'tipo_documento' => $data['documentos'][0]['tipo_documento'],
                                            'documento_url' => $data['documentos'][0]['documento_url'],
                                        ]
                                    );
                                    $firmaDocumento  = $cocumento->firma_documento()->create([
                                        'empleado_id' => auth()->user()->empleado->id,
                                        'cargo_firma_id' => CargoFirma::where('nombre', 'Coordinador Vinculacion')->first()->id,
                                        'estado_revision' => 'Aprobado',
                                        'firma_id' => auth()->user()->empleado->firma->id,
                                        'sello_id' => auth()->user()->empleado->sello->id,
                                        'estado_actual_id' => TipoEstado::where('nombre', 'Esperando Firma Coordinador Vinculacion')->first()->id,
                                        'hash' => 'hash'
                                    ]);

                                    $cocumento->estado_documento()->create([
                                        'empleado_id' => auth()->user()->empleado->id,
                                        'tipo_estado_id' => $firmaDocumento->estado_actual->estado_siguiente_id,
                                        'fecha' => now(),
                                        'comentario' => 'Documento creado',
                                    ]);
                                    */
                                })
                                ->icon('heroicon-o-document-arrow-up') // Icono para "Rechazar"
                                ->color('success')
                                ->modalHeading('Documentos del Proyecto')
                                ->modalSubheading('A continuación se muestran los documentos del proyecto y su estado')
                                ->visible(function (Proyecto $proyecto) {
                                    return $proyecto->estado->tipoestado->nombre == 'En curso';
                                })
                                ->modalWidth(MaxWidth::SevenExtraLarge),

                        ])
                        ->modalSubmitAction(false),
                    Action::make('edit')
                        ->label('Editar/Subsanar')
                        ->url(fn(Proyecto $proyecto) => route('editarProyectoVinculacion', $proyecto))
                        ->visible(function (Proyecto $proyecto) {
                            return $proyecto->estado->tipoestado->nombre == 'Subsanacion'
                                || $proyecto->estado->tipoestado->nombre == 'Borrador';
                        }),

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

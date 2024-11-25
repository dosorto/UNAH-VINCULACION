<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\EstadoProyecto;
use App\Models\Personal\Empleado;
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
use Filament\Tables\Actions\ActionGroup;

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
                        ->modalContent(
                            fn(Proyecto $proyecto): View =>
                            view(
                                'components.fichas.ficha-proyecto-vinculacion',
                                ['proyecto' => $proyecto]
                            )
                        )
                        // ->stickyModalHeader()
                        ->modalWidth(MaxWidth::SevenExtraLarge)
                        ->modalSubmitAction(false),
                    Action::make('edit')
                        ->label('Editar/Subsanar')
                        ->url(fn(Proyecto $proyecto) => route('editarProyectoVinculacion', $proyecto))
                        ->visible(function (Proyecto $proyecto) {
                            return $proyecto->estado->tipoestado->nombre == 'Subsanacion'
                                || $proyecto->estado->tipoestado->nombre == 'Borrador';
                        }),
                    Action::make('second')
                        ->label('Agregar Documento')
                        ->form([
                            FileUpload::make('documento_url')
                                ->label('Documentos de Formalizacion')
                                ->disk('public')
                                ->acceptedFileTypes(['application/pdf'])
                                ->directory('instrumento_formalizacion')
                                ->required()
                        ])
                        ->icon('heroicon-o-document-arrow-up') // Icono para "Rechazar"
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Agregando documento de formalización')

                        ->modalSubheading('Por favor, suba un documento de tipo PDF')
                        ->action(function (Proyecto $record,  array $data) {
                            //  cambiar todos los estados de la revision a Pendiente
                            $record->firma_proyecto()->update(['estado_revision' => 'Pendiente']);

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
                        })
                        ->visible(function (Proyecto $proyecto) {
                            return $proyecto->estado->tipoestado->nombre == 'En curso';
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

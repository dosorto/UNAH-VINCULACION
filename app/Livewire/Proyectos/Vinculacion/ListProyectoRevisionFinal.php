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
use App\Models\Estado\TipoEstado;


use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use App\Models\Proyecto\FirmaProyecto;
use Filament\Forms\Components\Textarea;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;


use Filament\Forms\Components\DatePicker;


use Filament\Forms\Components\Section;


class ListProyectoRevisionFinal extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
        ->query(
            Proyecto::query()
                ->whereIn('id', function ($query) {
                    $query->select('estadoable_id')
                        ->from('estado_proyecto')
                        ->where('estadoable_type', Proyecto::class) // Asegúrate de filtrar por el modelo `Proyecto`
                        ->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision final')->first()->id)
                        ->where('es_actual', true);
                })

        )
            ->columns([
                Tables\Columns\TextColumn::make('nombre_proyecto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('modalidad_id')
                    ->numeric()
                    ->sortable(),
               
            ])
            ->filters([
                //
            ])
            ->actions([
               
                Action::make('first')
                    ->label('Ver')
                    ->modalContent(
                        fn(Proyecto $proyecto) =>  view(
                            'components.fichas.ficha-proyecto-vinculacion',
                            ['proyecto' => $proyecto]
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
                                $record->firma_proyecto()->update(['estado_revision' => 'Pendiente',
                                'firma_id' => null,
                                'sello_id' => null,

                                ]);

                                

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
                                    'comentario' => 'El proyecto ha sido aprobado correctamente',
                                ]);

                                $proyecto->update($data);

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
            ->layout('components.panel.modulos.modulo-proyectos');
    }
}

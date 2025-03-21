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

use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\UnidadAcademica\FacultadCentro;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use Filament\Forms\Components\Section;


class ListProyectosSolicitado extends Component implements HasForms, HasTable
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
                            ->where('tipo_estado_id', TipoEstado::where('nombre', 'En revision')->first()->id)
                            ->where('es_actual', true);
                    })

            )
            ->columns([

                Tables\Columns\TextColumn::make('nombre_proyecto')
                    ->searchable(),



                Tables\Columns\TextColumn::make('estado.tipoestado.nombre')
                    ->badge()
                    ->label('Estado Proyecto')
                    ->searchable(),
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
                                $record->firma_proyecto()->update([
                                    'estado_revision' => 'Pendiente',
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
                                            ->columnSpanFull()
                                            ->default(function (Proyecto $proyecto) {
                                                $prefix = 'VRA';
                                                $year = date('Y'); // Obtiene el año actual
                                                $nextId = $proyecto ? $proyecto->id + 1 : 1; // Incrementa el ID o lo inicia en 1
                                                $formattedId = str_pad($nextId, 3, '0', STR_PAD_LEFT); // Formatea el ID a 3 dígitos

                                                return "{$prefix}-{$year}-{$formattedId}";
                                            }),
                                        Select::make('categoria_id')
                                            ->label('Categoría')
                                            ->multiple()
                                            ->relationship(name: 'categoria', titleAttribute: 'nombre')
                                            ->required()
                                            ->preload(),
                                        Select::make('ods')
                                            ->label('ODS')
                                            ->multiple()
                                            ->relationship('ods', 'nombre')
                                            ->visible(fn(Proyecto $record): bool
                                            => $record->estado->tipoestado->nombre === 'En revision')
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
                                            ->numeric(),
                                        Hidden::make('numero_dictamen')
                                            ->default(function (Proyecto $proyecto) {
                                                $prefix = 'VRA';
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
                                $proyecto->estado_proyecto()->create([
                                    'empleado_id' => Auth::user()->empleado->id,
                                    'tipo_estado_id' => TipoEstado::where('nombre', 'En revision final')->first()->id,
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
                            ->modalWidth(MaxWidth::SevenExtraLarge)
                            ->button(),

                    ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }


    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.list-proyectos-vinculacion-solicitados')
            ->layout('components.panel.modulos.modulo-proyectos');
    }
}

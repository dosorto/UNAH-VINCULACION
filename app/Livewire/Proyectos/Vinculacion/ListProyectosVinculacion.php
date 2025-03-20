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

use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;

use Filament\Tables\Filters\Filter;
use Filament\Forms\Get;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\Layout\Split;
use App\Models\Estado\TipoEstado;

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
                            ->where('estadoable_type', Proyecto::class) // Asegúrate de filtrar por el modelo `Proyecto`
                            ->whereIn('tipo_estado_id', TipoEstado::whereIn('nombre', ['Borrador'])->pluck('id')->toArray()) // Excluir 'Borrador' y 'Subsanacion'
                            ->where('es_actual', true);
                    })
                    ->leftJoin('proyecto_centro_facultad', 'proyecto_centro_facultad.proyecto_id', '=', 'proyecto.id')
                    ->leftJoin('proyecto_depto_ac', 'proyecto_depto_ac.proyecto_id', '=', 'proyecto.id')
                    // unir con la tabla de estados
                    ->leftJoin('estado_proyecto', 'estado_proyecto.estadoable_id', '=', 'proyecto.id')
                    ->leftJoin('tipo_estado', 'estado_proyecto.tipo_estado_id', '=', 'tipo_estado.id')


                    ->select('proyecto.*')
                    ->distinct('proyecto.id')



            )
            ->columns([

                Tables\Columns\TextColumn::make('nombre_proyecto')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('departamentos_academicos.nombre')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->wrap()
                    ->label('Departamento')
                    ->searchable(),

                Tables\Columns\TextColumn::make('facultades_centros.nombre')
                    ->badge()
                    ->wrap()
                    ->label('Centro/Facultad'),

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
                    ->numeric(),



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
                            ->options(FacultadCentro::all()->pluck('nombre', 'id'))
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
                Action::make('Proyecto de Vinculación')
                    ->label('Ver')
                    ->modalContent(
                        fn(Proyecto $proyecto): View =>
                        view(
                            'components.fichas.ficha-proyecto-vinculacion',
                            ['proyecto' => $proyecto]
                        )
                    )
                    // ->stickyModalHeader()
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->modalSubmitAction(false)

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.list-proyectos-vinculacion')
            ->layout('components.panel.modulos.modulo-proyectos');
    }
}

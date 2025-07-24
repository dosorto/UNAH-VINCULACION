<?php

namespace App\Livewire\DirectorFacultadCentro\Proyectos;

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


use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;


class ListProyectos extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public FacultadCentro $facultadCentro;
    public string $titulo;
    public string $descripcion;

    public function mount($facultadCentro = null)
    {
        // si el usuario tiene el permiso de admin_centro_facultad-proyectos validar que la facultad/centro sea la misma a la que pertenece
        //  adminCentroFacultadProyectos
        // Verifica si el usuario tiene alguno de los permisos

        $user = auth()->user();
        $facultadCentro =  auth()->user()->empleado->centro_facultad;

        if ($user->hasPermissionTo('admin_centro_facultad-proyectos') && !$user->hasPermissionTo('proyectos-admin-proyectos')) {
            // Si tiene este permiso, valida que pertenezca a la misma facultad/centro
            $condicion =  $user->empleado->centro_facultad_id === $facultadCentro->id;
            if (!$condicion) {
                abort(403);
            }
        }


        $this->facultadCentro = $facultadCentro;

        $this->titulo = 'Proyectos de Vinculación de ' . $this->facultadCentro->nombre;
        $this->descripcion = 'Listado de proyectos de vinculación de la Facultad/Centro';
    }

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
                    // si  el usuario tiene el permiso de admin_centro_facultad-proyectos filtrar por el centro/facultad
                    ->where('proyecto_centro_facultad.centro_facultad_id', $this->facultadCentro->id)
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
                    ->label('Departamentos')
                    ->searchable(),

                Tables\Columns\TextColumn::make('facultades_centros.nombre')
                    ->badge()
                    ->wrap()
                    ->label('Centros/Facultades'),

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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric(),

                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->badge()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Categoría'),



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

                        Select::make('departamento_id')
                            ->label('Departamento')
                            ->options(fn() => DepartamentoAcademico::query()
                                ->where('centro_facultad_id', $this->facultadCentro->id)
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
                            ['proyecto' => $proyecto->load(['aporteInstitucional', 'presupuesto'])]
                        )
                    )
                    // ->stickyModalHeader()
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->modalSubmitAction(false),


            ])
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.plantillas.table-plantilla');
    }
}

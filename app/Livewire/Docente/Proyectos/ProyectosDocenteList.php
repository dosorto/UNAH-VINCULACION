<?php

namespace App\Livewire\Docente\Proyectos;

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

                Tables\Columns\TextColumn::make('modalidad.nombre')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('poblacion_participante')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
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
        return view('livewire.docente.proyectos.proyectos-docente-list')
        ->layout('components.panel.modulos.modulo-firmas-docente');
    }
}

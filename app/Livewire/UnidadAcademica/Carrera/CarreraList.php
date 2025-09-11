<?php

namespace App\Livewire\UnidadAcademica\Carrera;

use App\Models\UnidadAcademica\Carrera;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;


use Illuminate\Database\Eloquent\Model;

use Filament\Tables\Actions\EditAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\DeleteAction;

use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;

use App\Models\UnidadAcademica\CentroFacultad;




class CarreraList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Carrera::query())
            ->striped()
            ->headerActions([
                CreateAction::make()
                    ->label('Crear Carrera')
                    ->form([
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required(),
                        TextInput::make('siglas')
                            ->label('Siglas')
                            ->maxLength(10),
                        Select::make('facultad_centro_id')
                            ->label('Facultad a la que pertenece')
                            ->relationship(
                                name: 'facultadcentro',
                                titleAttribute: 'nombre',
                                modifyQueryUsing: function (\Illuminate\Database\Eloquent\Builder $query) {
                                    return $query->where('es_facultad', 1);
                                }
                            ),
                        Select::make('facultades_centros')
                            ->label('Facultades o Centros')
                            ->required()
                            ->searchable()
                            ->multiple()
                            ->live()
                            ->relationship(
                                name: 'facultadCentros',
                                titleAttribute: 'nombre',
                                modifyQueryUsing: function (\Illuminate\Database\Eloquent\Builder $query) {
                                    return $query->where('es_facultad', 0);
                                }
                            )
                            ->preload(),

                        Select::make('departamento_academico_id')
                            ->label('Departamento académico')
                            ->required()
                            ->relationship(
                                name: 'departamentoAcademico',
                                titleAttribute: 'nombre'
                            )
                            ->preload(),

                        // ...
                    ])
                    ->using(function (array $data, string $model): Model {
                        $carrera = $model::create($data);
                        $carrera->facultadCentros()->sync($data['facultad_centro_id']);
                        return  $carrera;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Exito!')
                            ->body('Carrera creada exitosamente.')
                    ),
                ExportAction::make()->exports([
                    ExcelExport::make('table')
                        ->fromTable()

                        ->askForFilename('Carreras')
                        ->askForWriterType(),
                ])
                    ->label('Exportar a Excel')
                    ->color('success'),

            ])
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('siglas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('facultadcentro.nombre')
                    ->label('Facultad')
                    ->searchable(),
                Tables\Columns\TextColumn::make('departamentoAcademico.nombre')
                    ->label('Departamento académico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('facultadCentros.nombre')
                    ->label('Facultad y centro en donde se imparte')
                    ->separator(',')
                    ->wrap()
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('centroFacultad')
                    ->label('Centro/Facultad')
                    ->multiple()
                    ->relationship('facultadcentro', 'nombre')
                    ->preload(),
                    Tables\Filters\TrashedFilter::make(),
            ],  layout: FiltersLayout::AboveContent)
            ->actions([
                EditAction::make()
                    ->form([
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required(),
                        TextInput::make('siglas')
                            ->label('Siglas')
                            ->maxLength(10),
                        Select::make('facultad_centro_id')
                            ->required()
                            ->label('Facultad a la que pertenece')
                            ->relationship(
                                name: 'facultadcentro',
                                titleAttribute: 'nombre',
                                modifyQueryUsing: function (\Illuminate\Database\Eloquent\Builder $query) {
                                    return $query->where('es_facultad', 1);
                                }
                            ),
                        Select::make('departamento_academico_id')
                            ->label('Departamento académico')
                            ->required()
                            ->relationship(
                                name: 'departamentoAcademico',
                                titleAttribute: 'nombre'
                            )
                            ->preload(),
                        Select::make('facultades_centros')
                            ->label('Facultades o Centros')
                            ->searchable()
                            ->multiple()
                            ->live()
                            ->relationship(
                                name: 'facultadCentros',
                                titleAttribute: 'nombre',
                                modifyQueryUsing: function (\Illuminate\Database\Eloquent\Builder $query) {
                                    return $query->where('es_facultad', 0);
                                }
                            )
                            ->preload(),
                        // ...
                            ]),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.unidad-academica.carrera.carrera-list')
            ;//->layout('components.panel.modulos.modulo-unidad-academica', ['title' => 'Campus']);
    }
}

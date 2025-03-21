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
            ->striped()
            ->headerActions([
                CreateAction::make()
                    ->label('Crear Carrera')
                    ->form([
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required(),
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
                        Select::make('facultad_centro_id')
                            ->required()
                            ->label('Centros en los que se imparte')
                            ->multiple()
                            ->relationship(
                                name: 'facultadCentros',
                                titleAttribute: 'nombre',
                                modifyQueryUsing: function (\Illuminate\Database\Eloquent\Builder $query) {
                                    return $query;
                                }
                            )

                        // ...
                    ])
                    ->using(function (array $data, string $model): Model {
                        return $model::create($data);
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Exito!')
                            ->body('Departamento Academico creado exitosamente.')
                    ),
                ExportAction::make()->exports([
                    ExcelExport::make('table')
                        ->fromTable()

                        ->askForFilename('Departamentos Academicos')
                        ->askForWriterType(),
                ])
                    ->label('Exportar a Excel')
                    ->color('success'),

            ])
            ->query(Carrera::query())
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('facultadcentro.nombre')
                    ->label('Facultad/Centro')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
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
            ->layout('components.panel.modulos.modulo-unidad-academica', ['title' => 'Campus']);
    }
}

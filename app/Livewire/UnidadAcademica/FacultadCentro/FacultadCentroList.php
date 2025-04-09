<?php

namespace App\Livewire\UnidadAcademica\FacultadCentro;

use App\Models\UnidadAcademica\FacultadCentro;
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

use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\EditAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\DeleteAction;

use Filament\Tables\Actions\RestoreAction;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;

use App\Models\UnidadAcademica\CentroFacultad;
use Filament\Forms\Components\Toggle;

use Filament\Tables\Filters\TernaryFilter;


class FacultadCentroList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(
                fn(Model $record): string => route('proyectosCentroFacultad', $record->id)
            )
            ->query(FacultadCentro::query())
            ->striped()
            ->headerActions([
                CreateAction::make()
                    ->label('Crear Facultad/Centro')
                    ->form([
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required(),
                        TextInput::make('siglas')
                            ->label('Siglas')
                            ->required(),
                        Toggle::make('es_facultad')
                            ->label('Es Facultad'),
                        Select::make('campus_id')
                            ->required()
                            ->label('Campus')
                            ->relationship(
                                name: 'campus',
                                titleAttribute: 'nombre_campus',

                            ),


                        // ...
                    ])
                    ->using(function (array $data, string $model): Model {
                        return  $model::create($data);
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Exito!')
                            ->body('Facultad/Centro creado exitosamente.')
                    ),
                ExportAction::make()->exports([
                    ExcelExport::make('table')
                        ->fromTable()

                        ->askForFilename('Facultades y Centros')
                        ->askForWriterType(),
                ])
                    ->label('Exportar a Excel')
                    ->color('success'),

            ])
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\IconColumn::make('es_facultad')
                    ->boolean(),
                Tables\Columns\TextColumn::make('siglas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('campus.nombre_campus')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // filtrar por campus
                SelectFilter::make('campus')
                    ->label('Campus')
                    ->multiple()
                    ->relationship('campus', 'nombre_campus')
                    ->preload(),
                TernaryFilter::make('es_facultad')
                    ->label('Es Facultad'),
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
                            ->required(),
                        Toggle::make('es_facultad')
                            ->label('Es Facultad'),
                        Select::make('campus_id')
                            ->required()
                            ->label('Campus')
                            ->relationship(
                                name: 'campus',
                                titleAttribute: 'nombre_campus',

                            ),
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
        return view('livewire.unidad-academica.facultad-centro.facultad-centro-list')
            ->layout('components.panel.modulos.modulo-unidad-academica', ['title' => 'Campus']);
    }
}

<?php

namespace App\Livewire\UnidadAcademica\Campus;

use App\Models\UnidadAcademica\Campus;
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

use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;

use App\Models\UnidadAcademica\CentroFacultad;
use Filament\Forms\Components\Toggle;

use Filament\Tables\Filters\TernaryFilter;

use Filament\Tables\Enums\FiltersLayout;
class CampusList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Campus::query())
            ->striped()
            ->headerActions([
                CreateAction::make()
                    ->label('Crear Campus')
                    ->form([
                        TextInput::make('nombre_campus')
                            ->label('Nombre')

                            ->maxLength(255)
                            ->required(),
                        TextInput::make('direccion')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('url')
                            ->label('URL')
                            ->url()
                            ->maxLength(255)
                            ->required(),



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
                Tables\Columns\TextColumn::make('nombre_campus')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('direccion')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->limit(50)
                    ->searchable(),
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

                Tables\Filters\TrashedFilter::make(),
            ],  layout: FiltersLayout::AboveContent)
            ->actions([
                EditAction::make()
                    ->form([
                        TextInput::make('nombre_campus')
                            ->label('Nombre')

                            ->maxLength(255)
                            ->required(),
                        TextInput::make('direccion')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('url')
                            ->label('URL')
                            ->url()
                            ->maxLength(255)
                            ->required(),
                        // ...
                    ])
                    ->using(function (array $data, string $model): Model {

                        return  $model::create($data);
                    }),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.unidad-academica.campus.campus-list')
            ->layout('components.panel.modulos.modulo-unidad-academica', ['title' => 'Campus']);
    }
}

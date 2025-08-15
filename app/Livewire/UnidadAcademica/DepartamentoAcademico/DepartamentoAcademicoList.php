<?php

namespace App\Livewire\UnidadAcademica\DepartamentoAcademico;

use App\Models\UnidadAcademica\DepartamentoAcademico;
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

use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;

use App\Models\UnidadAcademica\CentroFacultad;

use Illuminate\Database\Eloquent\Model;

use Filament\Tables\Actions\EditAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\DeleteAction;

use Filament\Tables\Actions\RestoreAction;




class DepartamentoAcademicoList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(DepartamentoAcademico::query())
            ->striped()
            ->headerActions([
                CreateAction::make()
                    ->label('Crear Departamento ')
                    ->form([
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required(),
                        TextInput::make('siglas')
                            ->label('Siglas')
                            ->maxLength(10)
                            ->required(),
                        Select::make('centro_facultad_id')
                            ->required()
                            ->label('Centro/Facultad')
                            ->relationship(name: 'centroFacultad', titleAttribute: 'nombre')

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
                        //->queue()->withChunkSize(100)
                        ->askForFilename('Departamentos Academicos')
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
                Tables\Columns\TextColumn::make('centroFacultad.nombre')
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
                SelectFilter::make('centroFacultad')
                    ->label('Centro/Facultad')
                    ->multiple()
                    ->relationship('centroFacultad', 'nombre')
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
                            ->maxLength(10)
                            ->required(),
                        Select::make('centro_facultad_id')
                            ->required()
                            ->label('Centro/Facultad')
                            ->relationship(name: 'centroFacultad', titleAttribute: 'nombre')
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
        return view('livewire.unidad-academica.departamento-academico.departamento-academico-list')
           ;// ->layout('components.panel.modulos.modulo-unidad-academica', ['title' => 'Campus']);
    }
}

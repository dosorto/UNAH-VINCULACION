<?php

namespace App\Livewire\Demografia\Departamento;

use App\Models\Demografia\Departamento;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Models\Demografia\Pais;



class ListDepartamentos extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Departamentos')
            ->query(Departamento::query())
            ->columns([
                Tables\Columns\TextColumn::make('pais.nombre')
                    ->label('País')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('codigo_departamento')
                    ->label('Código del Departamento')
                    ->numeric()
                    ->sortable(),
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
                //
            ])
            ->actions([
                DeleteAction::make(),

                EditAction::make()
                    ->form([
                        Select::make('pais_id')
                            ->label('País')
                            ->options(
                                Pais::All()
                                    ->pluck('nombre', 'id')
                            ),
                        TextInput::make('nombre')
                            ->label('Nombre'),
                        TextInput::make('codigo_departamento')
                            ->label('Código del Departamento')
                            ->columnSpanFull()
                        // ...
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.demografia.departamento.list-departamentos');
    }
}

<?php

namespace App\Livewire\Demografia\Aldea;

use App\Models\Demografia\Aldea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Demografia\Municipio;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class ListAldeas extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Aldeas')
            ->query(Aldea::query())
            ->columns([
                Tables\Columns\TextColumn::make('municipio.nombre')
                    ->label('Municipio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Aldea')
                    ->searchable(),
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
                    Select::make('municipio_id')
                        ->label('Municipio')
                        ->options(
                            Municipio::All()
                                ->pluck('nombre', 'id')
                        ),
                    TextInput::make('nombre')
                        ->label('Nombre Aldea')
                        ->columnSpanFull()
                    // ...
                ]),
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
        return view('livewire.demografia.aldea.list-aldeas');
    }
}

<?php

namespace App\Livewire\Demografia\Pais;

use App\Models\Demografia\Pais;
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

class ListPaises extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Pais::query())
            ->columns([

                TextColumn::make('nombre')
                    ->searchable(),

                TextColumn::make('gentilicio')
            ])
            ->filters([
                //
            ])
            ->actions([
                DeleteAction::make(),

                EditAction::make()
                ->form([
                    TextInput::make('codigo_area'),
                    TextInput::make('codigo_iso'),
                    TextInput::make('codigo_iso_numerico'),
                    TextInput::make('codigo_iso_alpha_2'),
                    TextInput::make('nombre'),
                    TextInput::make('gentilicio'),
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
        return view('livewire.demografia.pais.list-paises')
            ->layout('components.panel.modulos.modulo-demografia');
    }
}

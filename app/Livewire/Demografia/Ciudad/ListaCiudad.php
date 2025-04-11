<?php

namespace App\Livewire\Demografia\Ciudad;

use App\Models\Demografia\Ciudad;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Models\Demografia\Pais;
use App\Models\Demografia\Departamento;
use App\Models\Demografia\Municipio;


class ListaCiudad extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Ciudades')
            ->query(Ciudad::query())
            ->columns([
                Tables\Columns\TextColumn::make('municipio.nombre')
                    ->label('Municipio')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('codigo_postal')
                    ->label('Código Postal') 
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
                //
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
                        ->label('Nombre'),
                    TextInput::make('codigo_postal')
                        ->label('Código Postal') 
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
        return view('livewire.demografia.ciudad.lista-ciudad')
        ;//->layout('components.panel.modulos.modulo-demografia');
    }
}

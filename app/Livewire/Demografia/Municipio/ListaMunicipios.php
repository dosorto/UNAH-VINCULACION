<?php

namespace App\Livewire\Demografia\Municipio;

use App\Models\Demografia\Municipio;
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

class ListaMunicipios extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Municipios')
            ->query(Municipio::query())
            ->columns([
                Tables\Columns\TextColumn::make('departamento.nombre')
                    ->label('Departamento')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Municipio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('codigo_municipio')
                    ->label('Código del Municipio')
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
                    Select::make('departamento_id')
                        ->options(
                            Departamento::All()
                                ->pluck('nombre', 'id')
                        )
                        ->label('Departamento'),
                    TextInput::make('nombre')
                        ->label('Nombre'),
                    TextInput::make('codigo_municipio')
                        ->label('Código del Municipio')
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
        return view('livewire.demografia.municipio.lista-municipios')
        ->layout('components.panel.modulos.modulo-demografia');
    }
}

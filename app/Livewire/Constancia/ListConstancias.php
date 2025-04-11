<?php

namespace App\Livewire\Constancia;

use App\Models\Constancia\Constancia;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;


class ListConstancias extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Constancia::query())
            ->columns([
                Tables\Columns\TextColumn::make('origen_type')
                ->label('Motivo')
                ->getStateUsing(function ($record) {
                    return class_basename($record->origen_type);
                })
                    ->label('Origen Tipo')
                    ->searchable(),

                Tables\Columns\TextColumn::make('origen_id')
                    ->label('Numero de dictamen')
                    ->getStateUsing(function ($record) {
                        return $record->origen_type === Proyecto::class
                            ? $record->origen->numero_dictamen // Mostrar nombre del proyecto
                            : 'No relacionado';
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('destinatario_type')
                    ->label('Entregado a')
                    ->getStateUsing(function ($record) {
                        return class_basename($record->destinatario_type);
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('destinatario_id')
                    ->label('Nombre')
                    ->getStateUsing(function ($record) {
                        return $record->destinatario_type === Empleado::class
                            ? $record->destinatario->nombre_completo // Mostrar nombre del empleado
                            : 'No relacionado';
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('tipo_constancia_id')
                    ->label('Tipo de Constancia')
                    ->getStateUsing(fn($record) => $record->tipo->nombre)
                    ->searchable(),

                Tables\Columns\TextColumn::make('hash')
                    ->label('Hash')
                    ->searchable(),

                Tables\Columns\BooleanColumn::make('status')
                    ->label('Status'),

                Tables\Columns\TextColumn::make('validaciones')
                    ->label('Validaciones'),
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.constancia.list-constancias');
    }
}

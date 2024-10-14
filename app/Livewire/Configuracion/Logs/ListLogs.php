<?php

namespace App\Livewire\Configuracion\Logs;

use Spatie\Activitylog\Models\Activity;
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

class ListLogs extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query())
            ->columns([
                TextColumn::make('description')
                    ->label('Descripción')
                    ->numeric()
                    ->sortable(),
                    TextColumn::make('subject_type')
                    ->label('Tipo')
                    ->formatStateUsing(function (Activity $record) {
                        return class_basename($record->subject_type);
                    })
                    ->searchable(),
                TextColumn::make('subject_id')
                    ->label('Codigo del registro afectado')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('causer.name')
                ->default('Dato precargado')
                    ->label('Usuario')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
            ])
            ->paginated([10, 25, 50, 100]);
    }

    public function render(): View
    {
        return view('livewire.configuracion.logs.list-logs')
        ->layout('components.panel.modulos.modulo-configuracion');
    }
}

<?php

namespace App\Livewire\Personal\Empleado;

use App\Models\Personal\Empleado;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;

class ListEmpleado extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Empleado::query())
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_contratacion')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salario')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supervisor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jornada'),
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
                        TextInput::make('nombre')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('fecha_contratacion')
                            ->required(),
                        TextInput::make('salario')
                            ->required()
                            ->numeric(),
                        TextInput::make('supervisor')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('jornada')
                            ->required(),
                   
                //
                ])
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.personal.empleado.list-empleado')
        ->layout('components.panel.modulos.modulo-empleado');
    }
}

<?php

namespace App\Livewire\User;

use App\Models\User;
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
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;

class Users extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
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
                // ActionGroup::make([
                    EditAction::make()
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->required()
                            ->maxLength(255),
                        // TextInput::make('password')
                        //     ->required(),
                    ]),
                    // DeleteAction::make(),
                // ]),
                //
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                    //
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make('Crear nuevo Usuario')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->required(),
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
                    ])
                    ->action(function ($data) {
                        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);

                         $empleado = Empleado::create(['nombre' => $data['nombre'], 'fecha_contratacion' => $data['fecha_contratacion'], 'salario' => $data['salario'], 'supervisor' => $data['supervisor'], 'jornada' => $data['jornada']]);

                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.user.users')
        ->layout('components.panel.modulos.modulo-usuarios');
    }
}

// Comentario de prueba
<?php

namespace App\Livewire\User;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\MultiSelect;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\CreateAction;
use Filament\Forms\Components\Select;

class Roles extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;


    // roles que NO se pueden eliminar ni editar
    private $roles = [
        'admin',
        'docente',
        'admin_centro_facultad',
        'estudiante',
        'Validador'
    ];

    // Propiedad para manejar permisos seleccionados
    public ?array $selectedPermissions = [];

    public function table(Table $table): Table
    {
        return $table
            ->query(Role::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
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
            ->actions([
                EditAction::make()
                    ->form(function ($record) {
                        return [
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Select::make('permissions')
                                ->label('Permisos')
                                ->multiple()
                                ->relationship(name: 'permissions', titleAttribute: 'name')
                                ->preload(),
                        ];
                    })
                    ->visible(function ($record) {

                        // validar ademas que el rol no tenga usuarios asignados

                        $role = Role::find($record->id);
                        $users = $role->users;

                        return !in_array($record->name, $this->roles) && count($users) == 0;
                    }),
                DeleteAction::make()
                    ->visible(function ($record) {
                        $role = Role::find($record->id);
                        $users = $role->users;
                        return !in_array($record->name, $this->roles) && count($users) == 0;
                    }),
            ])
            ->bulkActions([])
            ->headerActions([
                CreateAction::make()
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('permissions')
                            ->label('Permisos')
                            ->multiple()
                            ->relationship(name: 'permissions', titleAttribute: 'name')
                            ->preload(),
                    ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.user.roles')
            ;//->layout('components.panel.modulos.modulo-usuarios');
    }
}

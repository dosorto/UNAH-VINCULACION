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
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
// use Filament\Pages\Actions\CreateAction;
// use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Contracts\HasActions;
use App\Models\Personal\FirmaSelloEmpleado;
use Filament\Forms\Components\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Get;
use Filament\Forms\Set;



use app\Models\User;

use Filament\Forms\Components\CheckboxList;
// importar modelo role de spatie
use Spatie\Permission\Models\Role;

class ListEmpleado extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->whereHas('empleado')
                    ->with('empleado')
                    ->with('empleado.categoria')
                    ->with('empleado.centro_facultad')
                    ->with('empleado.departamento_academico')
            )
            ->columns([
                Tables\Columns\TextColumn::make('empleado.nombre_completo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('empleado.numero_empleado')
                    ->sortable(),
                Tables\Columns\TextColumn::make('empleado.categoria.nombre')
                    ->sortable(),
             
            ])
            ->filters([
                //
            ])
            ->actions([

                EditAction::make()
                    ->form([
                      
                Section::make('user')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre de Usuario')
                        ->required()
                        ->unique('users', 'name',ignoreRecord: true)
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Correo Electrónico')
                        ->required()
                        ->unique('users', 'email', ignoreRecord: true)
                        ->email()
                        ->maxLength(255),

                ])
                ->columnSpanFull(),



            Section::make('Empleado')
                ->schema([
                    TextInput::make('nombre_completo')
                        ->label('Nombre Completo')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('numero_empleado')
                        ->label('Número de Empleado')
                        ->unique('empleado', 'numero_empleado', ignoreRecord: true)
                        ->required()
                        ->numeric()
                        ->maxLength(255),
                    TextInput::make('celular')
                        ->required()
                        ->numeric()
                        ->maxLength(255),
                    Select::make('categoria_id')
                        ->label('Categoría')
                        ->relationship('categoria', 'nombre')
                        ->required(),
                    Select::make('empleado.centro_facultad_id')
                        ->label('Facultades o Centros')
                        ->searchable()
                        ->live()
                        ->relationship(name: 'centro_facultad', titleAttribute: 'nombre')
                        ->afterStateUpdated(function (Set $set) {
                            $set('empleado.empleado.departamento_academico_id', null);
                        })
                        ->required()
                        ->preload(),
                    Select::make('empleado.departamento_academico_id')
                        ->label('Departamentos Académicos')
                        ->searchable()
                        ->relationship(
                            name: 'departamento_academico',
                            titleAttribute: 'nombre',
                            modifyQueryUsing: fn($query, Get $get) => $query->where('centro_facultad_id', $get('empleado.centro_facultad_id'))
                        )
                        ->visible(fn(Get $get) => !empty($get('empleado.centro_facultad_id')))
                        ->live()
                        ->required()
                        ->preload(),

                ])
                ->relationship('empleado')
                ->columns(2),
            Section::make('Roles')
                ->schema([
                    CheckboxList::make('technologies')
                    ->label('Roles')
                    ->columns(3)
                    ->relationship(name: 'roles', titleAttribute: 'name')

                ])
                
                ->columnSpanFull(),
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

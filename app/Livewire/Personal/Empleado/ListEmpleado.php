<?php

namespace App\Livewire\Personal\Empleado;

use App\Livewire\Personal\Empleado\Formularios\FormularioEmpleado;
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
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Model;

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
                    ->with('empleado.centro_facultad')
                    ->leftJoin('empleado', 'users.id', '=', 'empleado.user_id')
                    ->select('users.*')

            )
            ->columns([

                // rol de usuario
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->wrap(),

                Tables\Columns\TextColumn::make('empleado.nombre_completo')
                    ->searchable(isIndividual: true)
                    ->label('Nombre Completo'),
                Tables\Columns\TextColumn::make('empleado.numero_empleado')
                    ->searchable(isIndividual: true)
                    ->label('Número de Empleado'),
                Tables\Columns\TextColumn::make('empleado.categoria.nombre')
                    ->label('Categoría'),

                Tables\Columns\TextColumn::make('empleado.centro_facultad.nombre')
                    ->label('Facultad o Centro')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge(),
                Tables\Columns\TextColumn::make('empleado.departamento_academico.nombre')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Departamento Académico'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('empleado.celular')
                    ->label('Celular')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Fecha de Creación'),


            ])
            ->filters([
                // filter name can be anything you want
                Filter::make('created_at')
                    ->form([
                        Select::make('centro_facultad_id')
                            ->label('Centro/Facultad')
                            ->options(FacultadCentro::all()->pluck('nombre', 'id'))
                            ->live()
                            ->multiple(),
                        Select::make('departamento_id')
                            ->label('Departamento')
                            ->visible(fn(Get $get) => !empty($get('centro_facultad_id')))
                            ->options(fn(Get $get) => DepartamentoAcademico::query()
                                ->whereIn('centro_facultad_id', $get('centro_facultad_id') ?: [])
                                ->get()
                                ->pluck('nombre', 'id'))
                            ->live()
                            ->multiple(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['centro_facultad_id'])) {
                            $query

                                ->whereIn('centro_facultad_id', $data['centro_facultad_id']);
                        }
                        if (!empty($data['departamento_id'])) {
                            $query
                                ->whereIn('departamento_academico_id', $data['departamento_id']);
                        }
                        return $query;
                    })
            ],  layout: FiltersLayout::AboveContent)
            ->actions([

                EditAction::make()
                    ->form(
                    FormularioEmpleado::form()
                    )
                    ->using(function (User $record, array $data) {

                        $record->update($data);
                        
                        $primerRol = $record->roles()->first();

                        if ($primerRol) {
                            $record->active_role_id = $primerRol->id;
                            $record->save();
                        }

                        return $record;
                    })
            ])
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')
                        ->fromTable()
                    //->queue()->withChunkSize(100)
                    //->askForFilename('Empleados')
                    // ->askForWriterType(),
                ])
                    ->label('Exportar a Excel')
                    ->color('success')
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
        return view('livewire.personal.empleado.list-empleado')
            ;//->layout('components.panel.modulos.modulo-empleado');
    }
}

<?php


namespace App\Livewire\Personal\Perfil;

use App\Livewire\Personal\Empleado\Formularios\FormularioEmpleado;
use Filament\Forms;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Personal\Empleado;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
// use Filament\Pages\Actions\CreateAction;
// use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Contracts\HasActions;
use App\Models\Personal\FirmaSelloEmpleado;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Get;
use Filament\Forms\Set;

// importar modelo role de spatie
use Spatie\Permission\Models\Role;

use App\Models\User;


class EditPerfilDocente extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public ?array $data = [];
    public ?array $data_firma = [];
    public User $record;

    public function mount(): void
    {
        $this->record = auth()->user();
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Section::make('Perfil de Empleado')
                    ->schema(
                        FormularioEmpleado::form(disableTipoEmpleado:true)
                    )
                    ->visible($this->record->empleado->firma()->exists())
                    // deshabilitar si el usuario no tiene el permiso de 'docente-cambiar-datos-personales'
                    ->disabled(!auth()->user()->can('cambiar-datos-personales')),


                // Section para la firma
                Section::make('Firma (Requerido)')
                    ->description('Visualizar o agregar una nueva Firma.')
                    ->model($this->record->empleado)
                    ->headerActions([
                        Action::make('create')
                            ->label('Crear Nueva Firma')
                            ->icon('heroicon-c-arrow-up-on-square')
                            ->form([
                                // ...
                                Hidden::make('empleado_id')
                                    ->default($this->record->id),
                                FileUpload::make('ruta_storage')
                                    ->label('Firma')
                                    ->disk('public')
                                    ->directory('images/firmas')
                                    ->image()
                                    ->required(),
                                Hidden::make('tipo')
                                    ->default('firma'),
                            ])
                            ->action(function (array $data) {
                                // dd($data);
                                FirmaSelloEmpleado::create($data);
                                $this->mount();
                            })
                    ])
                    ->schema([
                        // ...
                        Repeater::make('firma')
                            ->label('')
                            ->relationship('firma')
                            ->deletable(false)
                            ->schema([
                                FileUpload::make('ruta_storage')
                                    ->label('')
                                    ->disk('public')
                                    ->directory('images/firmas')
                                    ->image()
                                    ->disabled()
                                    ->nullable(),
                                Hidden::make('tipo')
                                    ->default('firma'),
                            ])
                            ->minItems(1)
                            ->maxItems(1)
                            ->addable(false)
                            ->columns(1),
                    ]),

                // Section para el sello
                Section::make('Sello (opcional)')
                    ->description('Visualizar o agregar un nuevo Sello.')
                    ->headerActions([
                        Action::make('create')
                            ->label('Crear Nuevo Sello')
                            ->icon('heroicon-c-arrow-up-on-square')
                            ->form([
                                // ...
                                Hidden::make('empleado_id')
                                    ->default($this->record->id),
                                FileUpload::make('ruta_storage')
                                    ->label('Sello')
                                    ->disk('public')
                                    ->directory('images/firmas')
                                    ->image()
                                    ->required(),
                                Hidden::make('tipo')
                                    ->default('sello'),
                            ])
                            ->action(function (array $data) {
                                // dd($data);
                                FirmaSelloEmpleado::create($data);
                                $this->mount();
                            })
                    ])
                    ->schema([
                        // ...
                        Repeater::make('sello')
                            ->label('')
                            ->relationship()
                            ->deletable(false)
                            ->schema([
                                FileUpload::make('ruta_storage')
                                    ->label('')
                                    ->disk('public')
                                    ->directory('images/firmas')
                                    ->image()
                                    ->disabled()
                                    ->nullable(),
                                Hidden::make('tipo')
                                    ->default('sello'),
                            ])
                            ->minItems(0)
                            ->maxItems(1)
                            ->defaultItems(1)
                            ->addable(false)
                            ->columns(1),
                    ])
                    ->model($this->record->empleado)
            ])
            ->statePath('data')
            ->model($this->record);
    }



    public function save()
    {
        
        $data = $this->form->getState();

        // validar que el docente tenga almenos una firma y un sello



        $this->record->update($data);

        $this->record->assignRole('docente')->save();
        $this->record->active_role_id =  Role::where('name', 'docente')->first()->id;

        // quitar el permiso de 'configuracion-admin-mi-perfil al usuario
        $this->record->revokePermissionTo('cambiar-datos-personales');
        $this->record->save();

        Notification::make()
            ->title('Exito!')
            ->body('Perfil  actualizado correctamente.')
            ->success()
            ->send();
        return redirect()->route('inicio');
    }

    public function render(): View
    {
        return view('livewire.personal.perfil.edit-perfil-docente', [
            'record' => $this->record,
        ]);
    }
}

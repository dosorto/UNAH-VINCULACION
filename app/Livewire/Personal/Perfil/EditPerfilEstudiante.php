<?php

namespace App\Livewire\Personal\Perfil;

use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioEstudiante;
use App\Models\Estudiante\Estudiante;
use Livewire\Component;


use Filament\Forms;
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
use App\Models\UnidadAcademica\FacultadCentro;
use Filament\Forms\Set;

// importar modelo role de spatie
use Spatie\Permission\Models\Role;

class EditPerfilEstudiante    extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public ?array $data = [];
    public ?array $data_firma = [];
    public Estudiante $record;

    public function mount(): void
    {
        $this->record =   auth()->user()->estudiante;
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Perfil de Empleado')
                    ->schema([
                       FormularioEstudiante::form()
                    ])
                    // deshabilitar si el usuario no tiene el permiso de 'docente-cambiar-datos-personales'
                    ->disabled(!auth()->user()->can('cambiar-datos-personales')),

            ])
            ->statePath('data')
            ->model($this->record);
    }



    public function save()
    {
        $data = $this->form->getState();

        // dd($data);
        // validar que el docente tenga almenos una firma y un sello

        $this->record->update($data);
        $this->record->user->assignRole('estudiante')->save();
        $this->record->user->active_role_id =  Role::where('name', 'estudiante')->first()->id;
        // quitar el permiso de 'configuracion-admin-mi-perfil al usuario
        $this->record->user->revokePermissionTo('cambiar-datos-personales');
        $this->record->user->save();

        Notification::make()
            ->title('Exito!')
            ->body('Perfil  actualizado correctamente.')
            ->success()
            ->send();
        return redirect()->route('inicio');
    }




    public function render()
    {
        return view('livewire.personal.perfil.edit-perfil-estudiante');
    }
}

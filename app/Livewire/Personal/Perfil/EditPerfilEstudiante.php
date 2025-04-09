<?php

namespace App\Livewire\Personal\Perfil;

use App\Livewire\Proyectos\Vinculacion\Formularios\FormularioEstudiante;
use App\Models\Estudiante\Estudiante;
use Livewire\Component;


use Filament\Forms\Form;

use Filament\Forms\Contracts\HasForms;

use Filament\Forms\Components\TextInput;
// use Filament\Pages\Actions\CreateAction;
// use Filament\Actions\Action;
use Filament\Notifications\Notification;

use Filament\Actions\Contracts\HasActions;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Section;
// importar modelo role de spatie
use Spatie\Permission\Models\Role;

class EditPerfilEstudiante    extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public ?array $data = [];

    public Estudiante $record;

    public function mount(): void
    {
        $this->record =   auth()->user()->estudiante;
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema(
                [
                    Section::make('Perfil Estudiante')
                        ->schema(
                            FormularioEstudiante::form(false)
                        )
                ]
            )
            ->disabled(!auth()->user()->can('cambiar-datos-personales'))
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
        return view(
            'livewire.personal.perfil.edit-perfil-estudiante',
            [
                'record' => $this->record,
            ]
        );
    }
}

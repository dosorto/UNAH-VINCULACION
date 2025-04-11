<?php

namespace App\Livewire\Personal\Empleado;

use App\Livewire\Personal\Empleado\Formularios\FormularioEmpleado;
use App\Livewire\User\Users;
use App\Models\Personal\Empleado;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use app\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
// use Filament\Pages\Actions\CreateAction;
// use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Contracts\HasActions;
use App\Models\Personal\FirmaSelloEmpleado;
use Filament\Forms\Components\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\CheckboxList;
// importar modelo role de spatie
use Spatie\Permission\Models\Role;

class CreateEmpleado extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema(FormularioEmpleado::form())
            ->statePath('data')
            ->model(User::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();
        $record = User::create($data);
        $this->form->model($record)->saveRelationships();

        // cambiar el rol activo al primer rol que tenga asignado
        $record->active_role_id = $record->roles->first()->id;
        $record->save();

        
        Notification::make()
            ->title('Exito!')
            ->body('Empleado creado correctamente.')
            ->success()
            ->send();
        $this->js('location.reload();');
        // limpiar formulario
        $this->data = [];
    }

    public function render(): View
    {
        return view('livewire.personal.empleado.create-empleado')
            ;//->layout('components.panel.modulos.modulo-empleado');
    }
}

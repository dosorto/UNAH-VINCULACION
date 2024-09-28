<?php

namespace App\Livewire\Demografia;

use App\Models\Demografia\Pais;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class CreatePais extends Component implements HasForms
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
            ->schema([
                TextInput::make('codigo_area'),
                TextInput::make('codigo_iso'),
                TextInput::make('codigo_iso_numerico'),
                TextInput::make('codigo_iso_alpha_2'),
                TextInput::make('nombre'),
                TextInput::make('gentilicio'),
            ])
            ->columns(2)
            ->statePath('data')
            ->model(Pais::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $record = Pais::create($data);
        $this->form->model($record)->saveRelationships();

        Notification::make()
        ->title('Exito!')
        ->body('Docente creado correctamente.')
        ->success()
        ->send();
    $this->js('location.reload();');
    // limpiar formulario
    $this->data = [];
    }

    public function render(): View
    {
        return view('livewire.demografia.create-pais');
    }
}

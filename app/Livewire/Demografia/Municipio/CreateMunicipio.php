<?php

namespace App\Livewire\Demografia\Municipio;

use App\Models\Demografia\Municipio;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

use Filament\Forms\Components\Select;
use App\Models\Demografia\Departamento;

class CreateMunicipio extends Component implements HasForms
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
                select::make('departamento_id')
                    ->options(
                        Departamento::All()
                        ->pluck('nombre', 'id')
                    ),
                textInput::make('nombre'),
                textInput::make('codigo_municipio'),
                //
            ])
            ->columns(2)
            ->statePath('data')
            ->model(Municipio::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $record = Municipio::create($data);

        $this->form->model($record)->saveRelationships();
    }

    public function render(): View
    {
        return view('livewire.demografia.municipio.create-municipio')
        ->layout('components.panel.modulos.modulo-demografia');
    }
}
<?php

namespace App\Livewire\Demografia\Departamento;

use App\Models\Demografia\Departamento;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

use Filament\Forms\Components\Select;
use App\Models\Demografia\Pais;


class CreateDepartamento extends Component implements HasForms
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

                Select::make('pais_id')
                    ->options(
                        Pais::All()
                        ->pluck('nombre', 'id')
                    ),
                TextInput::make('nombre'),
                TextInput::make('codigo_departamento')
                    ->columnSpanFull()
            ])
            ->columns(2)
            ->statePath('data')
            ->model(Departamento::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $record = Departamento::create($data);

        $this->form->model($record)->saveRelationships();
    }

    public function render(): View
    {
        return view('livewire.demografia.departamento.create-departamento');
    }
}

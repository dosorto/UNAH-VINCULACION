<?php

namespace App\Livewire\Demografia\Aldea;

use App\Models\Demografia\Aldea;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use App\Models\Demografia\Municipio;

class CreateAldea extends Component implements HasForms
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
                //
                Select::make('municipio_id')
                    ->options(
                        Municipio::All()
                        ->pluck('nombre', 'id')
                    ),
                TextInput::make('nombre')
                    
            ])
            ->columns(2)
            ->statePath('data')
            ->model(Aldea::class);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $record = Aldea::create($data);

        $this->form->model($record)->saveRelationships();
    }

    public function render(): View
    {
        return view('livewire.demografia.aldea.create-aldea')
        ->layout('components.panel.modulos.modulo-demografia');
    }
}
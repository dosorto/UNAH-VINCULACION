<?php

namespace App\Livewire\Personal\Perfil;

use App\Models\Estudiante\Estudiante;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Component;
use Illuminate\Contracts\View\View;

class EditPerfilEstudiante2 extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Estudiante $record;

    public function mount(): void
    {
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('nombre')
                    ->maxLength(255),
                Forms\Components\TextInput::make('apellido')
                    ->maxLength(255),
                Forms\Components\TextInput::make('cuenta')
                    ->maxLength(255),
                Forms\Components\TextInput::make('centro_facultdad_id')
                    ->numeric(),
                Forms\Components\TextInput::make('carrera_id')
                    ->numeric(),
            ])
            ->statePath('data')
            ->model($this->record);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->record->update($data);
    }

    public function render(): View
    {
        return view('livewire.personal.perfil.edit-perfil-estudiante2');
    }
}

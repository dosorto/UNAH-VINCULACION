<?php

namespace App\Livewire\Personal\Perfil;

use App\Models\Estudiante\Estudiante;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EditPerfilEstudiante2 extends Component
{
    public Estudiante $record;

    public string $nombre = '';
    public string $apellido = '';
    public string $cuenta = '';
    public ?int $centro_facultad_id = null;
    public ?int $carrera_id = null;

    public function mount(): void
    {
        $this->nombre             = $this->record->nombre ?? '';
        $this->apellido           = $this->record->apellido ?? '';
        $this->cuenta             = $this->record->cuenta ?? '';
        $this->centro_facultad_id = $this->record->centro_facultad_id ?? null;
        $this->carrera_id         = $this->record->carrera_id ?? null;
    }

    public function save(): void
    {
        $this->validate([
            'nombre'             => 'nullable|string|max:255',
            'apellido'           => 'nullable|string|max:255',
            'cuenta'             => 'nullable|string|max:255',
            'centro_facultad_id' => 'nullable|integer',
            'carrera_id'         => 'nullable|integer',
        ]);

        $this->record->update([
            'nombre'             => $this->nombre,
            'apellido'           => $this->apellido,
            'cuenta'             => $this->cuenta,
            'centro_facultad_id' => $this->centro_facultad_id,
            'carrera_id'         => $this->carrera_id,
        ]);

        Notification::make()->title('Guardado')->body('Perfil actualizado correctamente.')->success()->send();
    }

    public function render(): View
    {
        return view('livewire.personal.perfil.edit-perfil-estudiante2');
    }
}
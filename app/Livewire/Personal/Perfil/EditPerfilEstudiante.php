<?php

namespace App\Livewire\Personal\Perfil;

use App\Models\Estudiante\Estudiante;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class EditPerfilEstudiante extends Component
{
    public Estudiante $record;

    public string $nombre = '';
    public string $apellido = '';
    public string $sexo = '';
    public ?int $centro_facultad_id = null;
    public ?int $carrera_id = null;
    public string $cuenta = '';

    public function mount(): void
    {
        $this->record = auth()->user()->estudiante;
        $this->nombre            = $this->record->nombre ?? '';
        $this->apellido          = $this->record->apellido ?? '';
        $this->sexo              = $this->record->sexo ?? '';
        $this->centro_facultad_id = $this->record->centro_facultad_id;
        $this->carrera_id        = $this->record->carrera_id;
        $this->cuenta            = $this->record->cuenta ?? '';
    }

    public function save(): void
    {
        if (!auth()->user()->can('cambiar-datos-personales')) {
            return;
        }

        $this->validate([
            'nombre'           => 'required|string|max:255',
            'apellido'         => 'required|string|max:255',
            'sexo'             => 'required|in:Masculino,Femenino',
            'centro_facultad_id' => 'required|exists:centro_facultad,id',
            'carrera_id'       => 'nullable|exists:carrera,id',
            'cuenta'           => 'required|numeric',
        ]);

        $this->record->update([
            'nombre'            => $this->nombre,
            'apellido'          => $this->apellido,
            'sexo'              => $this->sexo,
            'centro_facultad_id' => $this->centro_facultad_id,
            'carrera_id'        => $this->carrera_id,
            'cuenta'            => $this->cuenta,
        ]);

        $this->record->user->assignRole('estudiante');
        $this->record->user->active_role_id = Role::where('name', 'estudiante')->first()?->id;
        $this->record->user->revokePermissionTo('cambiar-datos-personales');
        $this->record->user->save();

        Notification::make()->title('Exito!')->body('Perfil actualizado correctamente.')->success()->send();

        return redirect()->route('inicio');
    }

    public function render(): View
    {
        $centros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');
        $carreras = Carrera::orderBy('nombre')->pluck('nombre', 'id');

        return view('livewire.personal.perfil.edit-perfil-estudiante', compact('centros', 'carreras'));
    }
}
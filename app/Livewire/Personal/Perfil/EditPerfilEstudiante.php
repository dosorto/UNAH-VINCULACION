<?php

namespace App\Livewire\Personal\Perfil;

use App\Models\Estudiante\Estudiante;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Support\Notification;
use App\Support\ProfileCompletion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $this->nombre = $this->record->nombre ?? '';
        $this->apellido = $this->record->apellido ?? '';
        $this->sexo = $this->record->sexo ?? '';
        $this->centro_facultad_id = $this->record->centro_facultad_id;
        $this->carrera_id = $this->record->carrera_id;
        $this->cuenta = $this->record->cuenta ?? '';
    }

    public function updatingCentroFacultadId(): void
    {
        $this->carrera_id = null;
    }

    public function save(): void
    {
        abort_unless(ProfileCompletion::isRequired(auth()->user()), 403);

        $this->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'sexo' => 'required|in:Masculino,Femenino',
            'centro_facultad_id' => 'required|exists:centro_facultad,id',
            'carrera_id' => [
                'required',
                'integer',
                'exists:carrera,id',
            ],
            'cuenta' => 'required|numeric',
        ], [
            'nombre.required' => 'Ingrese sus nombres.',
            'apellido.required' => 'Ingrese sus apellidos.',
            'sexo.required' => 'Seleccione el sexo.',
            'centro_facultad_id.required' => 'Seleccione una facultad o centro.',
            'carrera_id.required' => 'Seleccione una carrera.',
            'carrera_id.exists' => 'La carrera seleccionada no es válida.',
            'cuenta.required' => 'Ingrese el número de cuenta.',
            'cuenta.numeric' => 'El número de cuenta solo debe contener dígitos.',
        ]);

        if (! $this->carreraPerteneceAlCentro()) {
            throw ValidationException::withMessages([
                'carrera_id' => 'La carrera seleccionada no pertenece a la facultad o centro.',
            ]);
        }

        DB::transaction(function (): void {
            $this->record->update([
                'nombre' => $this->nombre,
                'apellido' => $this->apellido,
                'sexo' => $this->sexo,
                'centro_facultad_id' => $this->centro_facultad_id,
                'carrera_id' => $this->carrera_id,
                'cuenta' => $this->cuenta,
            ]);

            $user = $this->record->user;
            $user->assignRole('estudiante');
            $user->active_role_id = Role::where('name', 'estudiante')->first()?->id;
            ProfileCompletion::clear($user);
            $user->save();
        });

        Notification::make()->title('Exito!')->body('Perfil actualizado correctamente.')->success()->send();

        $this->redirectRoute('inicio');
    }

    public function render(): View
    {
        $centros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');
        $carreras = $this->centro_facultad_id
            ? $this->carrerasDelCentro()->orderBy('nombre')->pluck('nombre', 'id')
            : collect();

        return view('livewire.personal.perfil.edit-perfil-estudiante', compact('centros', 'carreras'));
    }

    private function carrerasDelCentro()
    {
        return Carrera::query()->where(function ($query): void {
            $query->where('facultad_centro_id', $this->centro_facultad_id)
                ->orWhereHas('facultadCentros', function ($centros): void {
                    $centros->where('centro_facultad.id', $this->centro_facultad_id);
                });
        });
    }

    private function carreraPerteneceAlCentro(): bool
    {
        return $this->carrerasDelCentro()
            ->whereKey($this->carrera_id)
            ->exists();
    }
}

<?php

namespace App\Livewire\Estudiante;

use App\Models\Estudiante\Estudiante;
use App\Models\UnidadAcademica\Carrera;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class CreateEstudiante extends Component
{
    public string $user_name = '';
    public string $user_email = '';
    public string $nombre = '';
    public string $apellido = '';
    public string $cuenta = '';
    public ?int $centro_facultad_id = null;
    public ?int $carrera_id = null;

    protected array $rules = [
        'user_name'          => 'required|string|max:255|unique:users,name',
        'user_email'         => 'required|email|max:255|unique:users,email',
        'nombre'             => 'required|string|max:255',
        'apellido'           => 'required|string|max:255',
        'cuenta'             => 'required|numeric|unique:estudiante,cuenta',
        'centro_facultad_id' => 'required|exists:centro_facultad,id',
        'carrera_id'         => 'nullable|exists:carrera,id',
    ];

    public function updatingCentroFacultadId(): void
    {
        $this->carrera_id = null;
    }

    public function create(): void
    {
        $this->validate();

        $user = User::create([
            'name'     => $this->user_name,
            'email'    => $this->user_email,
            'password' => bcrypt(str()->random(12)),
        ]);

        $user->assignRole('estudiante');
        $user->active_role_id = Role::where('name', 'estudiante')->first()?->id;
        $user->save();

        Estudiante::create([
            'user_id'            => $user->id,
            'nombre'             => $this->nombre,
            'apellido'           => $this->apellido,
            'cuenta'             => $this->cuenta,
            'centro_facultad_id' => $this->centro_facultad_id,
            'carrera_id'         => $this->carrera_id,
        ]);

        Notification::make()->title('¡Éxito!')->body('Estudiante creado correctamente.')->success()->send();

        $this->reset(['user_name', 'user_email', 'nombre', 'apellido', 'cuenta', 'centro_facultad_id', 'carrera_id']);
    }

    public function render(): View
    {
        $centros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');
        $carreras = $this->carrerasPorCentro($this->centro_facultad_id);

        return view('livewire.estudiante.create-estudiante', compact('centros', 'carreras'));
    }

    private function carrerasPorCentro(?int $centroId)
    {
        if (!$centroId) {
            return collect();
        }

        return Carrera::query()
            ->where(function ($query) use ($centroId) {
                $query->where('facultad_centro_id', $centroId)
                    ->orWhereHas('facultadCentros', fn($q) => $q->where('centro_facultad.id', $centroId));
            })
            ->orderBy('nombre')
            ->pluck('nombre', 'id');
    }
}

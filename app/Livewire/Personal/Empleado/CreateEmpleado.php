<?php

namespace App\Livewire\Personal\Empleado;

use App\Models\Personal\Empleado;
use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class CreateEmpleado extends Component
{
    public string $name = '';
    public string $email = '';
    public string $nombre_completo = '';
    public string $numero_empleado = '';
    public string $celular = '';
    public ?int $centro_facultad_id = null;
    public ?int $departamento_academico_id = null;
    public array $create_roles = [];

    protected array $rules = [
        'name'               => 'required|string|max:255|unique:users,name',
        'email'              => 'required|email|max:255|unique:users,email',
        'nombre_completo'    => 'required|string|max:255',
        'numero_empleado'    => 'required|numeric|unique:empleado,numero_empleado',
        'celular'            => 'required|numeric',
        'centro_facultad_id' => 'required|exists:centro_facultad,id',
    ];

    public function updatingCentroFacultadId(): void
    {
        $this->departamento_academico_id = null;
    }

    public function create(): void
    {
        $this->validate();

        $user = User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => bcrypt(str()->random(12)),
        ]);

        Empleado::create([
            'user_id'                   => $user->id,
            'nombre_completo'           => $this->nombre_completo,
            'numero_empleado'           => $this->numero_empleado,
            'celular'                   => $this->celular,
            'centro_facultad_id'        => $this->centro_facultad_id,
            'departamento_academico_id' => $this->departamento_academico_id,
        ]);

        $user->syncRoles($this->create_roles);
        $primerRol = $user->roles()->first();
        if ($primerRol) {
            $user->active_role_id = $primerRol->id;
            $user->save();
        }

        Notification::make()->title('Exito!')->body('Empleado creado correctamente.')->success()->send();

        $this->reset(['name', 'email', 'nombre_completo', 'numero_empleado', 'celular', 'centro_facultad_id', 'departamento_academico_id', 'create_roles']);
        $this->js('location.reload();');
    }

    public function render(): View
    {
        $centros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');
        $departamentos = $this->centro_facultad_id
            ? DepartamentoAcademico::where('centro_facultad_id', $this->centro_facultad_id)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();
        $allRoles = Role::orderBy('name')->get(['id', 'name']);
        return view('livewire.personal.empleado.create-empleado', compact('centros', 'departamentos', 'allRoles'));
    }
}
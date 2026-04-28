<?php

namespace App\Livewire\Personal\Empleado;

use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class ListEmpleado extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $filterCentroFacultad = null;
    public ?int $filterDepartamento = null;

    public bool $editModal = false;
    public ?int $editId = null;
    public string $edit_name = '';
    public string $edit_email = '';
    public string $edit_nombre_completo = '';
    public string $edit_numero_empleado = '';
    public string $edit_celular = '';
    public ?int $edit_centro_facultad_id = null;
    public ?int $edit_departamento_academico_id = null;
    public array $edit_roles = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCentroFacultad(): void
    {
        $this->filterDepartamento = null;
        $this->resetPage();
    }

    public function updatingFilterDepartamento(): void
    {
        $this->resetPage();
    }

    public function updatingEditCentroFacultadId(): void
    {
        $this->edit_departamento_academico_id = null;
    }

    public function openEdit(int $id): void
    {
        $user = User::with(['empleado', 'roles'])->findOrFail($id);
        $this->editId                        = $id;
        $this->edit_name                     = $user->name;
        $this->edit_email                    = $user->email;
        $this->edit_nombre_completo          = $user->empleado?->nombre_completo ?? '';
        $this->edit_numero_empleado          = $user->empleado?->numero_empleado ?? '';
        $this->edit_celular                  = $user->empleado?->celular ?? '';
        $this->edit_centro_facultad_id       = $user->empleado?->centro_facultad_id;
        $this->edit_departamento_academico_id = $user->empleado?->departamento_academico_id;
        $this->edit_roles                    = $user->roles->pluck('id')->toArray();
        $this->editModal = true;
    }

    public function save(): void
    {
        $user = User::findOrFail($this->editId);

        $this->validate([
            'edit_name'            => ['required', 'string', 'max:255', Rule::unique('users', 'name')->ignore($user->id)],
            'edit_email'           => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'edit_nombre_completo' => 'required|string|max:255',
            'edit_numero_empleado' => ['required', 'numeric', Rule::unique('empleado', 'numero_empleado')->ignore($user->empleado?->id)],
            'edit_celular'         => 'required|numeric',
            'edit_centro_facultad_id' => 'required|exists:centro_facultad,id',
        ]);

        $user->update(['name' => $this->edit_name, 'email' => $this->edit_email]);

        if ($user->empleado) {
            $user->empleado->update([
                'nombre_completo'           => $this->edit_nombre_completo,
                'numero_empleado'           => $this->edit_numero_empleado,
                'celular'                   => $this->edit_celular,
                'centro_facultad_id'        => $this->edit_centro_facultad_id,
                'departamento_academico_id' => $this->edit_departamento_academico_id,
            ]);
        }

        $user->syncRoles($this->edit_roles);
        $primerRol = $user->roles()->first();
        if ($primerRol) {
            $user->active_role_id = $primerRol->id;
            $user->save();
        }

        $this->editModal = false;
        $this->editId    = null;

        Notification::make()->title('¡Éxito!')->body('Empleado actualizado correctamente.')->success()->send();
    }

    public function render(): View
    {
        $records = User::query()
            ->whereHas('empleado')
            ->with(['empleado.categoria', 'empleado.centro_facultad', 'empleado.departamento_academico', 'roles'])
            ->when($this->search, fn($q) =>
                $q->whereHas('empleado', fn($q) =>
                    $q->where('nombre_completo', 'like', '%' . $this->search . '%')
                      ->orWhere('numero_empleado', 'like', '%' . $this->search . '%')
                )->orWhere('email', 'like', '%' . $this->search . '%')
            )
            ->when($this->filterCentroFacultad, fn($q) =>
                $q->whereHas('empleado', fn($q) => $q->where('centro_facultad_id', $this->filterCentroFacultad))
            )
            ->when($this->filterDepartamento, fn($q) =>
                $q->whereHas('empleado', fn($q) => $q->where('departamento_academico_id', $this->filterDepartamento))
            )
            ->paginate(10);

        $centros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');

        $departamentos = $this->filterCentroFacultad
            ? DepartamentoAcademico::where('centro_facultad_id', $this->filterCentroFacultad)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();

        $allRoles = Role::orderBy('name')->get(['id', 'name']);

        $editDepartamentos = $this->edit_centro_facultad_id
            ? DepartamentoAcademico::where('centro_facultad_id', $this->edit_centro_facultad_id)->orderBy('nombre')->pluck('nombre', 'id')
            : collect();

        return view('livewire.personal.empleado.list-empleado', compact('records', 'centros', 'departamentos', 'allRoles', 'editDepartamentos'));
    }
}
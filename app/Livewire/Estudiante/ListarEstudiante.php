<?php

namespace App\Livewire\Estudiante;

use App\Models\Estudiante\Estudiante;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class ListarEstudiante extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $editModal = false;
    public ?int $editId = null;
    public string $edit_nombre_usuario = '';
    public string $edit_correo_electronico = '';
    public string $edit_nombre = '';
    public string $edit_apellido = '';
    public string $edit_cuenta = '';
    public ?int $edit_centro_facultad_id = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openEdit(int $id): void
    {
        $estudiante = Estudiante::with('user')->findOrFail($id);
        $this->editId            = $id;
        $this->edit_nombre_usuario     = $estudiante->user?->name ?? '';
        $this->edit_correo_electronico = $estudiante->user?->email ?? '';
        $this->edit_nombre             = $estudiante->nombre;
        $this->edit_apellido           = $estudiante->apellido;
        $this->edit_cuenta             = $estudiante->cuenta;
        $this->edit_centro_facultad_id = $estudiante->centro_facultad_id;
        $this->editModal = true;
    }

    public function save(): void
    {
        $estudiante = Estudiante::findOrFail($this->editId);

        $this->validate([
            'edit_nombre_usuario'     => ['required', 'string', 'max:255', Rule::unique('users', 'name')->ignore($estudiante->user?->id)],
            'edit_correo_electronico' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($estudiante->user?->id)],
            'edit_nombre'             => 'required|string|max:255',
            'edit_apellido'           => 'required|string|max:255',
            'edit_cuenta'             => ['required', 'numeric', Rule::unique('estudiante', 'cuenta')->ignore($estudiante->id)],
            'edit_centro_facultad_id' => 'required|exists:centro_facultad,id',
        ]);

        $estudiante->update([
            'nombre'            => $this->edit_nombre,
            'apellido'          => $this->edit_apellido,
            'cuenta'            => $this->edit_cuenta,
            'centro_facultad_id' => $this->edit_centro_facultad_id,
        ]);

        if ($estudiante->user) {
            $estudiante->user->update([
                'name'  => $this->edit_nombre_usuario,
                'email' => $this->edit_correo_electronico,
            ]);
        }

        $this->editModal = false;
        $this->editId = null;

        Notification::make()->title('¡Éxito!')->body('Estudiante actualizado correctamente.')->success()->send();
    }

    public function render(): View
    {
        $records = Estudiante::query()
            ->with(['user', 'proyectosEstudiante.proyecto'])
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                  ->orWhere('apellido', 'like', '%' . $this->search . '%')
                  ->orWhere('cuenta', 'like', '%' . $this->search . '%');
            }))
            ->paginate(10);

        $centros = FacultadCentro::orderBy('nombre')->pluck('nombre', 'id');

        return view('livewire.estudiante.listar-estudiante', compact('records', 'centros'));
    }
}
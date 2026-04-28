<?php

namespace App\Livewire\UnidadAcademica\DepartamentoAcademico;

use App\Models\UnidadAcademica\DepartamentoAcademico;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class DepartamentoAcademicoList extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showTrashed = false;

    public bool $createModal = false;
    public string $create_nombre = '';
    public string $create_siglas = '';
    public ?int $create_centro_facultad_id = null;

    public bool $editModal = false;
    public ?int $editId = null;
    public string $edit_nombre = '';
    public string $edit_siglas = '';
    public ?int $edit_centro_facultad_id = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['create_nombre', 'create_siglas', 'create_centro_facultad_id']);
        $this->createModal = true;
    }

    public function store(): void
    {
        $this->validate([
            'create_nombre'            => 'required|string|max:100',
            'create_siglas'            => 'nullable|string|max:10',
            'create_centro_facultad_id'=> 'required|exists:centro_facultad,id',
        ]);

        DepartamentoAcademico::create([
            'nombre'            => $this->create_nombre,
            'siglas'            => $this->create_siglas,
            'centro_facultad_id'=> $this->create_centro_facultad_id,
        ]);

        $this->createModal = false;
        Notification::make()->title('Departamento académico creado.')->success()->send();
    }

    public function openEdit(int $id): void
    {
        $dep = DepartamentoAcademico::findOrFail($id);
        $this->editId                = $id;
        $this->edit_nombre           = $dep->nombre;
        $this->edit_siglas           = $dep->siglas ?? '';
        $this->edit_centro_facultad_id = $dep->centro_facultad_id;
        $this->editModal             = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_nombre'            => 'required|string|max:100',
            'edit_siglas'            => 'nullable|string|max:10',
            'edit_centro_facultad_id'=> 'required|exists:centro_facultad,id',
        ]);

        DepartamentoAcademico::findOrFail($this->editId)->update([
            'nombre'            => $this->edit_nombre,
            'siglas'            => $this->edit_siglas,
            'centro_facultad_id'=> $this->edit_centro_facultad_id,
        ]);

        $this->editModal = false;
        Notification::make()->title('Departamento académico actualizado.')->success()->send();
    }

    public function delete(int $id): void
    {
        DepartamentoAcademico::findOrFail($id)->delete();
        Notification::make()->title('Departamento académico eliminado.')->success()->send();
    }

    public function restore(int $id): void
    {
        DepartamentoAcademico::withTrashed()->findOrFail($id)->restore();
        Notification::make()->title('Departamento académico restaurado.')->success()->send();
    }

    public function render(): View
    {
        $records = DepartamentoAcademico::with('centroFacultad')
            ->when($this->showTrashed, fn($q) => $q->withTrashed())
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', '%'.$this->search.'%')
                  ->orWhere('siglas', 'like', '%'.$this->search.'%')
            )
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.unidad-academica.departamento-academico.departamento-academico-list', [
            'records'         => $records,
            'centrosFacultad' => FacultadCentro::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}

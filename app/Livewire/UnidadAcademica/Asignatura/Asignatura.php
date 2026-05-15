<?php

namespace App\Livewire\UnidadAcademica\Asignatura;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Asignatura as AsignaturaModel;
use App\Models\UnidadAcademica\Carrera;
use App\Support\Notification;

class Asignatura extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $createModal = false;
    public string $create_nombre = '';
    public string $create_codigo = '';
    public string $create_descripcion = '';
    public ?int $create_carrera_id = null;

    public bool $editModal = false;
    public ?int $editId = null;
    public string $edit_nombre = '';
    public string $edit_codigo = '';
    public string $edit_descripcion = '';
    public ?int $edit_carrera_id = null;

    public array $carrerasList = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->carrerasList = Carrera::orderBy('nombre')->pluck('nombre', 'id')->toArray();
    }

    public function openCreate(): void
    {
        $this->reset(['create_nombre', 'create_codigo', 'create_descripcion']);
        $this->createModal = true;
    }

    public function store(): void
    {
        $this->validate([
            'create_nombre' => 'required|string|max:255',
            'create_codigo' => 'nullable|string|max:50',
            'create_carrera_id' => 'nullable|exists:carrera,id',
        ]);

        AsignaturaModel::create([
            'nombre' => $this->create_nombre,
            'codigo' => $this->create_codigo,
            'descripcion' => $this->create_descripcion,
            'carrera_id' => $this->create_carrera_id,
        ]);

        $this->createModal = false;
        Notification::make()->title('Asignatura creada')->success()->send();
    }

    public function openEdit(int $id): void
    {
        $r = AsignaturaModel::findOrFail($id);
        $this->editId = $r->id;
        $this->edit_nombre = $r->nombre;
        $this->edit_codigo = $r->codigo ?? '';
        $this->edit_descripcion = $r->descripcion ?? '';
        $this->edit_carrera_id = $r->carrera_id;
        $this->editModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_nombre' => 'required|string|max:255',
            'edit_codigo' => 'nullable|string|max:50',
            'edit_carrera_id' => 'nullable|exists:carrera,id',
        ]);

        $r = AsignaturaModel::findOrFail($this->editId);
        $r->update([
            'nombre' => $this->edit_nombre,
            'codigo' => $this->edit_codigo,
            'descripcion' => $this->edit_descripcion,
            'carrera_id' => $this->edit_carrera_id,
        ]);

        $this->editModal = false;
        Notification::make()->title('Asignatura actualizada')->success()->send();
    }

    public function delete(int $id): void
    {
        $r = AsignaturaModel::findOrFail($id);
        $r->delete();
        Notification::make()->title('Asignatura eliminada')->success()->send();
    }

    public function render()
    {
        $records = AsignaturaModel::when($this->search, fn($q) => $q->where('nombre', 'like', '%'.$this->search.'%')->orWhere('codigo', 'like', '%'.$this->search.'%'))
            ->orderBy('nombre')
            ->paginate(15);

        return view('livewire.unidad_academica.asignatura.asignatura', compact('records'));
    }
}

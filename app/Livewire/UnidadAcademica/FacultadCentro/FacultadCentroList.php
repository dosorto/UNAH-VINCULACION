<?php

namespace App\Livewire\UnidadAcademica\FacultadCentro;

use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class FacultadCentroList extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showTrashed = false;

    public bool $createModal = false;
    public string $create_nombre = '';
    public string $create_siglas = '';
    public bool $create_es_facultad = false;
    public ?int $create_campus_id = null;

    public bool $editModal = false;
    public ?int $editId = null;
    public string $edit_nombre = '';
    public string $edit_siglas = '';
    public bool $edit_es_facultad = false;
    public ?int $edit_campus_id = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['create_nombre', 'create_siglas', 'create_es_facultad', 'create_campus_id']);
        $this->createModal = true;
    }

    public function store(): void
    {
        $this->validate([
            'create_nombre'     => 'required|string|max:255',
            'create_siglas'     => 'nullable|string|max:20',
            'create_campus_id'  => 'required|exists:campus,id',
        ]);

        FacultadCentro::create([
            'nombre'      => $this->create_nombre,
            'siglas'      => $this->create_siglas,
            'es_facultad' => $this->create_es_facultad,
            'campus_id'   => $this->create_campus_id,
        ]);

        $this->createModal = false;
        Notification::make()->title('Facultad/Centro creado.')->success()->send();
    }

    public function openEdit(int $id): void
    {
        $fc = FacultadCentro::findOrFail($id);
        $this->editId          = $id;
        $this->edit_nombre     = $fc->nombre;
        $this->edit_siglas     = $fc->siglas ?? '';
        $this->edit_es_facultad = (bool) $fc->es_facultad;
        $this->edit_campus_id  = $fc->campus_id;
        $this->editModal       = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_nombre'    => 'required|string|max:255',
            'edit_siglas'    => 'nullable|string|max:20',
            'edit_campus_id' => 'required|exists:campus,id',
        ]);

        FacultadCentro::findOrFail($this->editId)->update([
            'nombre'      => $this->edit_nombre,
            'siglas'      => $this->edit_siglas,
            'es_facultad' => $this->edit_es_facultad,
            'campus_id'   => $this->edit_campus_id,
        ]);

        $this->editModal = false;
        Notification::make()->title('Facultad/Centro actualizado.')->success()->send();
    }

    public function delete(int $id): void
    {
        FacultadCentro::findOrFail($id)->delete();
        Notification::make()->title('Facultad/Centro eliminado.')->success()->send();
    }

    public function restore(int $id): void
    {
        FacultadCentro::withTrashed()->findOrFail($id)->restore();
        Notification::make()->title('Facultad/Centro restaurado.')->success()->send();
    }

    public function render(): View
    {
        $records = FacultadCentro::with('campus')
            ->when($this->showTrashed, fn($q) => $q->withTrashed())
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', '%'.$this->search.'%')
                  ->orWhere('siglas', 'like', '%'.$this->search.'%')
            )
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.unidad-academica.facultad-centro.facultad-centro-list', [
            'records'  => $records,
            'campuses' => Campus::orderBy('nombre_campus')->pluck('nombre_campus', 'id'),
        ]);
    }
}

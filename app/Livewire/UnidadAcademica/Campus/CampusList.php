<?php

namespace App\Livewire\UnidadAcademica\Campus;

use App\Models\UnidadAcademica\Campus;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CampusList extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showTrashed = false;

    public bool $createModal = false;
    public string $create_nombre_campus = '';
    public string $create_siglas = '';
    public string $create_direccion = '';
    public string $create_telefono = '';
    public string $create_url = '';

    public bool $editModal = false;
    public ?int $editId = null;
    public string $edit_nombre_campus = '';
    public string $edit_siglas = '';
    public string $edit_direccion = '';
    public string $edit_telefono = '';
    public string $edit_url = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['create_nombre_campus', 'create_siglas', 'create_direccion', 'create_telefono', 'create_url']);
        $this->createModal = true;
    }

    public function store(): void
    {
        $this->validate([
            'create_nombre_campus' => 'required|string|max:255',
            'create_siglas'        => 'nullable|string|max:10',
            'create_direccion'     => 'required|string|max:255',
            'create_telefono'      => 'required|string|max:255',
            'create_url'           => 'required|url|max:255',
        ]);

        Campus::create([
            'nombre_campus' => $this->create_nombre_campus,
            'siglas'        => $this->create_siglas,
            'direccion'     => $this->create_direccion,
            'telefono'      => $this->create_telefono,
            'url'           => $this->create_url,
        ]);

        $this->createModal = false;
        Notification::make()->title('Campus creado.')->success()->send();
    }

    public function openEdit(int $id): void
    {
        $campus = Campus::findOrFail($id);
        $this->editId             = $id;
        $this->edit_nombre_campus = $campus->nombre_campus;
        $this->edit_siglas        = $campus->siglas ?? '';
        $this->edit_direccion     = $campus->direccion;
        $this->edit_telefono      = $campus->telefono;
        $this->edit_url           = $campus->url;
        $this->editModal          = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_nombre_campus' => 'required|string|max:255',
            'edit_siglas'        => 'nullable|string|max:10',
            'edit_direccion'     => 'required|string|max:255',
            'edit_telefono'      => 'required|string|max:255',
            'edit_url'           => 'required|url|max:255',
        ]);

        Campus::findOrFail($this->editId)->update([
            'nombre_campus' => $this->edit_nombre_campus,
            'siglas'        => $this->edit_siglas,
            'direccion'     => $this->edit_direccion,
            'telefono'      => $this->edit_telefono,
            'url'           => $this->edit_url,
        ]);

        $this->editModal = false;
        Notification::make()->title('Campus actualizado.')->success()->send();
    }

    public function delete(int $id): void
    {
        Campus::findOrFail($id)->delete();
        Notification::make()->title('Campus eliminado.')->success()->send();
    }

    public function restore(int $id): void
    {
        Campus::withTrashed()->findOrFail($id)->restore();
        Notification::make()->title('Campus restaurado.')->success()->send();
    }

    public function render(): View
    {
        $records = Campus::when($this->showTrashed, fn($q) => $q->withTrashed())
            ->when($this->search, fn($q) =>
                $q->where('nombre_campus', 'like', '%'.$this->search.'%')
                  ->orWhere('siglas', 'like', '%'.$this->search.'%')
            )
            ->orderBy('nombre_campus')
            ->paginate(10);

        return view('livewire.unidad-academica.campus.campus-list', compact('records'));
    }
}

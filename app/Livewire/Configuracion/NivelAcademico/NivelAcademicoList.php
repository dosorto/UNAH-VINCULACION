<?php

namespace App\Livewire\Configuracion\NivelAcademico;

use App\Models\NivelAcademico;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class NivelAcademicoList extends Component
{
    use WithPagination;

    public bool $showTrashed = false;

    public bool $createModal = false;
    public string $create_nombre = '';
    public bool $create_activo = true;
    public int $create_orden = 0;

    public bool $editModal = false;
    public ?int $editId = null;
    public string $edit_nombre = '';
    public bool $edit_activo = true;
    public int $edit_orden = 0;

    public function openCreate(): void
    {
        $this->reset(['create_nombre', 'create_activo', 'create_orden']);
        $this->create_activo = true;
        $this->createModal = true;
    }

    public function store(): void
    {
        $this->validate([
            'create_nombre' => 'required|string|max:255',
            'create_activo' => 'boolean',
            'create_orden'  => 'nullable|integer|min:0',
        ]);

        NivelAcademico::create([
            'nombre' => $this->create_nombre,
            'activo' => $this->create_activo,
            'orden'  => $this->create_orden ?: 0,
        ]);

        $this->createModal = false;
        Notification::make()->title('Nivel académico creado.')->success()->send();
    }

    public function openEdit(int $id): void
    {
        $nivel = NivelAcademico::findOrFail($id);
        $this->editId      = $id;
        $this->edit_nombre = $nivel->nombre;
        $this->edit_activo = $nivel->activo;
        $this->edit_orden  = $nivel->orden;
        $this->editModal   = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_nombre' => 'required|string|max:255',
            'edit_activo' => 'boolean',
            'edit_orden'  => 'nullable|integer|min:0',
        ]);

        NivelAcademico::findOrFail($this->editId)->update([
            'nombre' => $this->edit_nombre,
            'activo' => $this->edit_activo,
            'orden'  => $this->edit_orden ?: 0,
        ]);

        $this->editModal = false;
        Notification::make()->title('Nivel académico actualizado.')->success()->send();
    }

    public function delete(int $id): void
    {
        NivelAcademico::findOrFail($id)->delete();
        Notification::make()->title('Nivel académico eliminado.')->success()->send();
    }

    public function restore(int $id): void
    {
        NivelAcademico::withTrashed()->findOrFail($id)->restore();
        Notification::make()->title('Nivel académico restaurado.')->success()->send();
    }

    private function recordsQuery()
    {
        return NivelAcademico::when($this->showTrashed, fn ($q) => $q->withTrashed())
            ->orderBy('orden')
            ->orderBy('nombre');
    }

    public function render(): View
    {
        $records = $this->recordsQuery()->paginate(10);

        return view('livewire.configuracion.nivel-academico.nivel-academico-list', compact('records'));
    }
}

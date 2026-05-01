<?php

namespace App\Livewire\Demografia\Departamento;

use App\Models\Demografia\Departamento;
use App\Models\Demografia\Pais;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ListDepartamentos extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $editModal = false;
    public ?int $editId = null;
    public ?int $edit_pais_id = null;
    public string $edit_nombre = '';
    public string $edit_codigo_departamento = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openEdit(int $id): void
    {
        $dep = Departamento::findOrFail($id);
        $this->editId                   = $id;
        $this->edit_pais_id             = $dep->pais_id;
        $this->edit_nombre              = $dep->nombre;
        $this->edit_codigo_departamento = (string) ($dep->codigo_departamento ?? '');
        $this->editModal                = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_pais_id'             => 'required|exists:pais,id',
            'edit_nombre'              => 'required|string|max:100',
            'edit_codigo_departamento' => 'nullable|numeric',
        ]);

        Departamento::findOrFail($this->editId)->update([
            'pais_id'             => $this->edit_pais_id,
            'nombre'              => $this->edit_nombre,
            'codigo_departamento' => $this->edit_codigo_departamento ?: null,
        ]);

        $this->editModal = false;
        Notification::make()->title('Departamento actualizado.')->success()->send();
    }

    public function delete(int $id): void
    {
        Departamento::findOrFail($id)->delete();
        Notification::make()->title('Departamento eliminado.')->success()->send();
    }

    public function render(): View
    {
        $records = Departamento::with('pais')
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', '%'.$this->search.'%')
                  ->orWhere('codigo_departamento', 'like', '%'.$this->search.'%')
            )
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.demografia.departamento.list-departamentos', [
            'records' => $records,
            'paises'  => Pais::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}

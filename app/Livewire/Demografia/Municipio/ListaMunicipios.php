<?php

namespace App\Livewire\Demografia\Municipio;

use App\Models\Demografia\Departamento;
use App\Models\Demografia\Municipio;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ListaMunicipios extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $editModal = false;
    public ?int $editId = null;
    public ?int $edit_departamento_id = null;
    public string $edit_nombre = '';
    public string $edit_codigo_municipio = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openEdit(int $id): void
    {
        $municipio = Municipio::findOrFail($id);
        $this->editId                = $id;
        $this->edit_departamento_id  = $municipio->departamento_id;
        $this->edit_nombre           = $municipio->nombre;
        $this->edit_codigo_municipio = $municipio->codigo_municipio;
        $this->editModal             = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_departamento_id'  => 'required|exists:departamentos,id',
            'edit_nombre'           => 'required|string|max:100',
            'edit_codigo_municipio' => 'required|string|max:20',
        ]);

        Municipio::findOrFail($this->editId)->update([
            'departamento_id'  => $this->edit_departamento_id,
            'nombre'           => $this->edit_nombre,
            'codigo_municipio' => $this->edit_codigo_municipio,
        ]);

        $this->editModal = false;
        Notification::make()->title('Municipio actualizado.')->success()->send();
    }

    public function delete(int $id): void
    {
        Municipio::findOrFail($id)->delete();
        Notification::make()->title('Municipio eliminado.')->success()->send();
    }

    public function render(): View
    {
        $records = Municipio::with('departamento')
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', '%'.$this->search.'%')
                  ->orWhere('codigo_municipio', 'like', '%'.$this->search.'%')
            )
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.demografia.municipio.lista-municipios', [
            'records'      => $records,
            'departamentos' => Departamento::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}

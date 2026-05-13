<?php

namespace App\Livewire\Demografia\Pais;

use App\Models\Demografia\Pais;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ListPaises extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $editModal = false;
    public ?int $editId = null;
    public string $edit_codigo_area = '';
    public string $edit_codigo_iso = '';
    public string $edit_codigo_iso_numerico = '';
    public string $edit_codigo_iso_alpha_2 = '';
    public string $edit_nombre = '';
    public string $edit_gentilicio = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openEdit(int $id): void
    {
        $pais = Pais::findOrFail($id);
        $this->editId              = $id;
        $this->edit_codigo_area    = (string) $pais->codigo_area;
        $this->edit_codigo_iso     = $pais->codigo_iso;
        $this->edit_codigo_iso_numerico = (string) $pais->codigo_iso_numerico;
        $this->edit_codigo_iso_alpha_2  = $pais->codigo_iso_alpha_2;
        $this->edit_nombre         = $pais->nombre;
        $this->edit_gentilicio     = $pais->gentilicio;
        $this->editModal           = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_codigo_area'          => 'required|numeric',
            'edit_codigo_iso'           => 'required|string|max:10',
            'edit_codigo_iso_numerico'  => 'required|numeric',
            'edit_codigo_iso_alpha_2'   => 'required|string|max:5',
            'edit_nombre'               => 'required|string|max:100',
            'edit_gentilicio'           => 'required|string|max:100',
        ]);

        Pais::findOrFail($this->editId)->update([
            'codigo_area'          => $this->edit_codigo_area,
            'codigo_iso'           => $this->edit_codigo_iso,
            'codigo_iso_numerico'  => $this->edit_codigo_iso_numerico,
            'codigo_iso_alpha_2'   => $this->edit_codigo_iso_alpha_2,
            'nombre'               => $this->edit_nombre,
            'gentilicio'           => $this->edit_gentilicio,
        ]);

        $this->editModal = false;
        Notification::make()->title('País actualizado.')->success()->send();
    }

    public function delete(int $id): void
    {
        Pais::findOrFail($id)->delete();
        Notification::make()->title('País eliminado.')->success()->send();
    }

    public function render(): View
    {
        $records = Pais::when($this->search, fn($q) =>
                $q->where('nombre', 'like', '%'.$this->search.'%')
                  ->orWhere('gentilicio', 'like', '%'.$this->search.'%')
            )
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.demografia.pais.list-paises', compact('records'));
    }
}

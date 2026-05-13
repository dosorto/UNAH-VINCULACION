<?php

namespace App\Livewire\Demografia\Ciudad;

use App\Models\Demografia\Ciudad;
use App\Models\Demografia\Municipio;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ListaCiudad extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $editModal = false;
    public ?int $editId = null;
    public ?int $edit_municipio_id = null;
    public string $edit_nombre = '';
    public string $edit_codigo_postal = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openEdit(int $id): void
    {
        $ciudad = Ciudad::findOrFail($id);
        $this->editId             = $id;
        $this->edit_municipio_id  = $ciudad->municipio_id;
        $this->edit_nombre        = $ciudad->nombre;
        $this->edit_codigo_postal = $ciudad->codigo_postal;
        $this->editModal          = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_municipio_id'  => 'required|exists:municipios,id',
            'edit_nombre'        => 'required|string|max:100',
            'edit_codigo_postal' => 'required|string|max:20',
        ]);

        Ciudad::findOrFail($this->editId)->update([
            'municipio_id'  => $this->edit_municipio_id,
            'nombre'        => $this->edit_nombre,
            'codigo_postal' => $this->edit_codigo_postal,
        ]);

        $this->editModal = false;
        Notification::make()->title('Ciudad actualizada.')->success()->send();
    }

    public function delete(int $id): void
    {
        Ciudad::findOrFail($id)->delete();
        Notification::make()->title('Ciudad eliminada.')->success()->send();
    }

    public function render(): View
    {
        $records = Ciudad::with('municipio')
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', '%'.$this->search.'%')
                  ->orWhere('codigo_postal', 'like', '%'.$this->search.'%')
            )
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.demografia.ciudad.lista-ciudad', [
            'records'    => $records,
            'municipios' => Municipio::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}

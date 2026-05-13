<?php

namespace App\Livewire\Demografia\Aldea;

use App\Models\Demografia\Aldea;
use App\Models\Demografia\Municipio;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ListAldeas extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $editModal = false;
    public ?int $editId = null;
    public ?int $edit_municipio_id = null;
    public string $edit_nombre = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openEdit(int $id): void
    {
        $aldea = Aldea::findOrFail($id);
        $this->editId           = $id;
        $this->edit_municipio_id = $aldea->municipio_id;
        $this->edit_nombre      = $aldea->nombre;
        $this->editModal        = true;
    }

    public function save(): void
    {
        $this->validate([
            'edit_municipio_id' => 'required|exists:municipios,id',
            'edit_nombre'       => 'required|string|max:100',
        ]);

        Aldea::findOrFail($this->editId)->update([
            'municipio_id' => $this->edit_municipio_id,
            'nombre'       => $this->edit_nombre,
        ]);

        $this->editModal = false;
        Notification::make()->title('Aldea actualizada.')->success()->send();
    }

    public function delete(int $id): void
    {
        Aldea::findOrFail($id)->delete();
        Notification::make()->title('Aldea eliminada.')->success()->send();
    }

    public function render(): View
    {
        $records = Aldea::with('municipio')
            ->when($this->search, fn($q) =>
                $q->where('nombre', 'like', '%'.$this->search.'%')
            )
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.demografia.aldea.list-aldeas', [
            'records'    => $records,
            'municipios' => Municipio::orderBy('nombre')->pluck('nombre', 'id'),
        ]);
    }
}

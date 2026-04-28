<?php

namespace App\Livewire\Personal\Permiso;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

class ListPermisos extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $records = Permission::when($this->search, fn($q) =>
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('guard_name', 'like', '%'.$this->search.'%')
            )
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.personal.permiso.list-permisos', compact('records'));
    }
}

<?php

namespace App\Livewire\Constancia;

use App\Models\Constancia\Constancia;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\Proyecto;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ListConstancias extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $records = Constancia::with(['tipo'])
            ->when($this->search, fn($q) =>
                $q->where('hash', 'like', '%'.$this->search.'%')
            )
            ->latest()
            ->paginate(15);

        return view('livewire.constancia.list-constancias', compact('records'));
    }
}

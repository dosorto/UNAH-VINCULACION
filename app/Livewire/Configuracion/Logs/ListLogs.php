<?php

namespace App\Livewire\Configuracion\Logs;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class ListLogs extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $records = Activity::with('causer')
            ->when($this->search, fn($q) =>
                $q->where('description', 'like', '%'.$this->search.'%')
                  ->orWhereHas('causer', fn($q2) => $q2->where('name', 'like', '%'.$this->search.'%'))
            )
            ->latest()
            ->paginate(25);

        return view('livewire.configuracion.logs.list-logs', compact('records'));
    }
}

<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\PpsServicioSocial;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ListPpsServicioSocial extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterEstado = '';
    public string $filterTipo = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterEstado(): void
    {
        $this->resetPage();
    }

    public function updatingFilterTipo(): void
    {
        $this->resetPage();
    }

    private function recordsQuery()
    {
        return PpsServicioSocial::query()
            ->when(!$this->canViewAllRecords(), fn ($query) => $query->where('created_by', auth()->id()))
            ->when($this->search, fn ($query) => $query->where(function ($subQuery) {
                $subQuery->where('codigo_registro', 'like', '%' . $this->search . '%')
                    ->orWhere('nombre_estudiante', 'like', '%' . $this->search . '%')
                    ->orWhere('numero_cuenta', 'like', '%' . $this->search . '%')
                    ->orWhere('nombre_institucion', 'like', '%' . $this->search . '%');
            }))
            ->when($this->filterEstado, fn ($query) => $query->where('estado', $this->filterEstado))
            ->when($this->filterTipo, fn ($query) => $query->where('tipo_pps_ss', $this->filterTipo))
            ->orderByDesc('created_at');
    }

    private function canViewAllRecords(): bool
    {
        $user = auth()->user();

        return (bool) (
            $user?->can('proyectos.historial')
            || $user?->can('director.proyectos')
            || $user?->hasRole(['admin', 'Director/Enlace'])
        );
    }

    public function render(): View
    {
        $records = $this->recordsQuery()->paginate(10);
        $estados = PpsServicioSocial::query()->distinct()->orderBy('estado')->pluck('estado')->filter();
        $tipos = PpsServicioSocial::query()->distinct()->orderBy('tipo_pps_ss')->pluck('tipo_pps_ss')->filter();

        return view('livewire.proyectos.vinculacion.list-pps-servicio-social', [
            'records' => $records,
            'estados' => $estados,
            'tipos' => $tipos,
            'canViewAllRecords' => $this->canViewAllRecords(),
        ]);
    }
}

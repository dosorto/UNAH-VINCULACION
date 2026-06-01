<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\PpsServicioSocial;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ShowPpsServicioSocial extends Component
{
    public PpsServicioSocial $registro;

    public function mount(int $id): void
    {
        $registro = PpsServicioSocial::findOrFail($id);

        abort_unless($this->canViewRecord($registro), 403);

        $this->registro = $registro;
    }

    private function canViewRecord(PpsServicioSocial $registro): bool
    {
        $user = auth()->user();

        if (
            $user?->can('proyectos.historial')
            || $user?->can('director.proyectos')
            || $user?->hasRole(['admin', 'Director/Enlace'])
        ) {
            return true;
        }

        return $registro->created_by !== null && (int) $registro->created_by === (int) auth()->id();
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.show-pps-servicio-social');
    }
}

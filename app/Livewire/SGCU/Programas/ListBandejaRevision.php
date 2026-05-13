<?php

namespace App\Livewire\SGCU\Programas;

use App\Models\SGCU\ProgramaRevision;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Illuminate\Support\Collection;

class ListBandejaRevision extends Component
{
    public function render(): View
    {
        $revisiones = ProgramaRevision::query()
            ->with('programa')
            ->orderByDesc('id')
            ->get();

        $programasPendientes = $revisiones->filter(fn ($rev) => in_array($rev->estado, ['PENDIENTE', 'PENDIENTE_ASIGNACION'], true));
        $programasEnProceso = $revisiones->filter(fn ($rev) => in_array($rev->estado, ['ASIGNADO', 'EN_PROCESO'], true));
        $programasAprobados = $revisiones->filter(fn ($rev) => ($rev->programa?->estado_flujo ?? null) === 'APROBADO');

        return view('livewire.sgcu.programas.list-bandeja-revision', compact('programasPendientes', 'programasEnProceso', 'programasAprobados'))
            ->layout('layouts.app', ['hideHorizontalNav' => true]);
    }
}

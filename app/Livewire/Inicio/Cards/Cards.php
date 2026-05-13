<?php

namespace App\Livewire\Inicio\Cards;

use App\Models\Proyecto\Proyecto;
use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Cards extends Component
{
    public int $totalUsuarios = 0;
    public int $totalProyectos = 0;
    public int $totalCentros = 0;
    public array $usuariosPorMes = [];

    public function mount(): void
    {
        $this->totalUsuarios = User::count();
        $this->totalProyectos = Proyecto::count();
        $this->totalCentros = FacultadCentro::count();
        $this->usuariosPorMes = User::select(DB::raw('MONTH(created_at) as month, COUNT(*) as count'))
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->pluck('count')
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.inicio.cards.cards');
    }
}

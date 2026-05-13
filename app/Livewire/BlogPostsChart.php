<?php

namespace App\Livewire;

use App\Models\UnidadAcademica\FacultadCentro;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class BlogPostsChart extends Component
{
    public int $totalUsuarios;
    public int $totalProyectos;
    public int $totalFacultades;
    public array $usuariosPorMes = [];

    public function mount(): void
    {
        $this->totalUsuarios   = User::count();
        $this->totalProyectos  = Proyecto::count();
        $this->totalFacultades = FacultadCentro::count();

        $this->usuariosPorMes = User::select(DB::raw('MONTH(created_at) as month, COUNT(*) as count'))
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->pluck('count')
            ->toArray();
    }

    public function render()
    {
        return view('livewire.blog-posts-chart');
    }
}

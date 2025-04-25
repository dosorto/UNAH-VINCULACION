<?php

namespace App\Livewire\Inicio\Dashboards;
use Livewire\Component;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;


class DashboardEstudiante extends Component
{
    public function render()
    {
        return view('livewire.inicio.dashboards.dashboard-estudiante');
    }
}

<?php

namespace App\Livewire\DAFT\Catalogos;

use App\Models\Asignatura;
use App\Models\PeriodoAcademico;
use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\FacultadCentro;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DaftCatalogos extends Component
{
    public function render(): View
    {
        $metricas = [
            'campus' => Campus::count(),
            'centros' => FacultadCentro::count(),
            'asignaturas' => Asignatura::count(),
            'periodos' => PeriodoAcademico::count(),
        ];

        return view('livewire.daft.catalogos.daft-catalogos', compact('metricas'))
            ->layout('layouts.app', ['hideHorizontalNav' => true]);
    }
}

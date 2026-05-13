<?php

namespace App\Livewire\SGCU\Catalogos;

use App\Models\Asignatura;
use App\Models\PeriodoAcademico;
use App\Models\UnidadAcademica\Campus;
use App\Models\UnidadAcademica\FacultadCentro;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SgcuCatalogos extends Component
{
    public function render(): View
    {
        $metricas = [
            'campus' => Campus::count(),
            'centros' => FacultadCentro::count(),
            'asignaturas' => Asignatura::count(),
            'periodos' => PeriodoAcademico::count(),
        ];

        return view('livewire.sgcu.catalogos.sgcu-catalogos', compact('metricas'))
            ->layout('layouts.app', ['hideHorizontalNav' => true]);
    }
}

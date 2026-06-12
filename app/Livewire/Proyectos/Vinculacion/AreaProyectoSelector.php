<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AreaProyectoSelector extends Component
{
    public bool $mostrarFormulariosPps = false;
    public bool $mostrarFormulariosDesarrolloLocal = false;
    public bool $mostrarFormulariosEducacionNoFormal = false;
    public ?int $tipoAccionDesarrolloLocalId = null;
    public ?int $tipoAccionEnfId = null;
    public ?int $tipoAccionVoluntariadoId = null;

    public function mount(): void
    {
        $grupo = request()->query('grupo');

        $this->mostrarFormulariosPps = $grupo === 'pps';
        $this->mostrarFormulariosDesarrolloLocal = $grupo === 'desarrollo-local';
        $this->mostrarFormulariosEducacionNoFormal = $grupo === 'educacion-no-formal';
        $this->tipoAccionDesarrolloLocalId = DB::table('vinculacion_tipos_accion')
            ->where('codigo', 'DESARROLLO_LOCAL_REGIONAL')
            ->value('id');
        $this->tipoAccionEnfId = DB::table('enf_catalogos')
            ->where('tipo', 'tipo_accion_enf')
            ->where('nombre', 'Programa de educacion continua')
            ->where('activo', true)
            ->value('id');
        $this->tipoAccionVoluntariadoId = DB::table('vinculacion_tipos_accion')
            ->where('codigo', 'VOLUNTARIADO')
            ->value('id');
    }

    public function mostrarFormulariosPps(): void
    {
        $this->mostrarFormulariosPps = true;
        $this->mostrarFormulariosDesarrolloLocal = false;
        $this->mostrarFormulariosEducacionNoFormal = false;
    }

    public function mostrarFormulariosDesarrolloLocal(): void
    {
        $this->mostrarFormulariosDesarrolloLocal = true;
        $this->mostrarFormulariosPps = false;
        $this->mostrarFormulariosEducacionNoFormal = false;
    }

    public function mostrarFormulariosEducacionNoFormal(): void
    {
        $this->mostrarFormulariosEducacionNoFormal = true;
        $this->mostrarFormulariosPps = false;
        $this->mostrarFormulariosDesarrolloLocal = false;
    }

    public function volverSelectorPrincipal(): void
    {
        $this->mostrarFormulariosPps = false;
        $this->mostrarFormulariosDesarrolloLocal = false;
        $this->mostrarFormulariosEducacionNoFormal = false;
    }

    public function mostrarMensajeDesarrolloLocal(): void
    {
        Notification::make()
            ->title('Importante')
            ->body('Para registrar un proyecto de Vinculación, todos los integrantes deben estar registrados en NEXO.')
            ->warning()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.areas-proyecto-selector');
    }
}

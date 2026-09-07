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
    public ?int $tipoAccionPasantiasId = null;
    public bool $pasantiasDisponible = false;

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
            ->whereIn('nombre', ['Proyecto de educacion continua', 'Programa de educacion continua'])
            ->where('activo', true)
            ->orderByRaw("CASE WHEN nombre = 'Proyecto de educacion continua' THEN 0 ELSE 1 END")
            ->value('id');
        $this->tipoAccionVoluntariadoId = DB::table('vinculacion_tipos_accion')
            ->where('codigo', 'VOLUNTARIADO')
            ->value('id');
        $this->tipoAccionPasantiasId = DB::table('vinculacion_tipos_accion')
            ->where('codigo', 'PASANTIAS')
            ->where('activo', true)
            ->value('id');
        $this->pasantiasDisponible = $this->tipoAccionPasantiasId !== null
            && DB::table('flujos_aprobacion')
                ->where('proceso', 'PASANTIAS_DEFAULT')
                ->where('codigo_formulario', 'FORM-DVUS-013')
                ->where('tipo_accion_id', $this->tipoAccionPasantiasId)
                ->where('activo', true)
                ->exists();
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

<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\PpsServicioSocial;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ShowPpsServicioSocial extends Component
{
    public PpsServicioSocial $registro;
    public array $camposFaltantesEnvio = [];
    public bool $rechazoModal = false;
    public string $motivoRechazo = '';

    public function mount(int $id): void
    {
        $registro = PpsServicioSocial::findOrFail($id);

        abort_unless($this->canViewRecord($registro), 403);

        $this->registro = $registro;
    }

    public function enviarRevision(): void
    {
        $this->registro->refresh();

        if ($this->registro->estado !== PpsServicioSocial::ESTADO_BORRADOR) {
            Notification::make()
                ->title('Envio no disponible')
                ->body('Solo los registros en estado borrador pueden enviarse a revision.')
                ->warning()
                ->send();

            return;
        }

        abort_unless($this->registro->perteneceAlUsuario(auth()->id()), 403);

        $camposFaltantes = $this->registro->camposFaltantesParaEnvio();

        if ($camposFaltantes !== []) {
            $this->camposFaltantesEnvio = $camposFaltantes;

            Notification::make()
                ->title('Formulario incompleto')
                ->body('Complete los campos obligatorios antes de enviar a revision.')
                ->warning()
                ->send();

            return;
        }

        try {
            $this->registro->update([
                'estado' => PpsServicioSocial::ESTADO_ENVIADO,
                'fecha_envio' => now(),
                'enviado_por' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->registro->refresh();
            $this->camposFaltantesEnvio = [];
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Error')
                ->body('No se pudo enviar el registro a revision. Intente nuevamente.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Registro enviado')
            ->body('El FORM-DVUS-015/016 fue enviado a revision correctamente.')
            ->success()
            ->send();
    }

    public function aprobar(): void
    {
        $this->registro->refresh();

        if ($this->registro->estado !== PpsServicioSocial::ESTADO_ENVIADO) {
            Notification::make()
                ->title('Revision no disponible')
                ->body('Solo los registros enviados pueden aprobarse.')
                ->warning()
                ->send();

            return;
        }

        abort_unless($this->registro->puedeAprobarse(auth()->id(), auth()->user()), 403);

        try {
            $this->registro->update([
                'estado' => PpsServicioSocial::ESTADO_APROBADO,
                'fecha_revision' => now(),
                'revisado_por' => auth()->id(),
                'motivo_rechazo' => null,
                'updated_by' => auth()->id(),
            ]);

            $this->registro->refresh();
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Error')
                ->body('No se pudo aprobar el registro. Intente nuevamente.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Registro aprobado')
            ->body('El FORM-DVUS-015/016 fue aprobado correctamente.')
            ->success()
            ->send();
    }

    public function abrirModalRechazo(): void
    {
        $this->registro->refresh();

        if ($this->registro->estado !== PpsServicioSocial::ESTADO_ENVIADO) {
            Notification::make()
                ->title('Revision no disponible')
                ->body('Solo los registros enviados pueden rechazarse.')
                ->warning()
                ->send();

            return;
        }

        abort_unless($this->registro->puedeRechazarse(auth()->id(), auth()->user()), 403);

        $this->resetErrorBag('motivoRechazo');
        $this->motivoRechazo = '';
        $this->rechazoModal = true;
    }

    public function cerrarModalRechazo(): void
    {
        $this->rechazoModal = false;
        $this->motivoRechazo = '';
        $this->resetErrorBag('motivoRechazo');
    }

    public function rechazar(): void
    {
        $this->registro->refresh();

        if ($this->registro->estado !== PpsServicioSocial::ESTADO_ENVIADO) {
            $this->cerrarModalRechazo();

            Notification::make()
                ->title('Revision no disponible')
                ->body('Solo los registros enviados pueden rechazarse.')
                ->warning()
                ->send();

            return;
        }

        abort_unless($this->registro->puedeRechazarse(auth()->id(), auth()->user()), 403);

        $this->validate([
            'motivoRechazo' => 'required|string|min:5|max:5000',
        ], [], [
            'motivoRechazo' => 'motivo de rechazo',
        ]);

        try {
            $this->registro->update([
                'estado' => PpsServicioSocial::ESTADO_RECHAZADO,
                'fecha_revision' => now(),
                'revisado_por' => auth()->id(),
                'motivo_rechazo' => trim($this->motivoRechazo),
                'updated_by' => auth()->id(),
            ]);

            $this->registro->refresh();
            $this->cerrarModalRechazo();
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Error')
                ->body('No se pudo rechazar el registro. Intente nuevamente.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Registro rechazado')
            ->body('El FORM-DVUS-015/016 fue rechazado correctamente.')
            ->success()
            ->send();
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

        return $registro->perteneceAlUsuario(auth()->id());
    }

    public function render(): View
    {
        return view('livewire.proyectos.vinculacion.show-pps-servicio-social');
    }
}

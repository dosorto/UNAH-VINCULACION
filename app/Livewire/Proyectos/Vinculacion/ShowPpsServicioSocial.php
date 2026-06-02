<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\PpsServicioSocial;
use App\Services\PpsServicioSocial\PpsServicioSocialWorkflowService;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
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
            $this->registro = app(PpsServicioSocialWorkflowService::class)
                ->enviarARevision($this->registro, auth()->id());
            $this->camposFaltantesEnvio = [];
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Flujo PPS/SS incompleto')
                ->body($e->getMessage())
                ->warning()
                ->send();

            return;
        } catch (\Throwable $e) {
            Log::error('Error enviando PPS/SS a revision', [
                'registro_id' => $this->registro?->id,
                'estado' => $this->registro?->estado,
                'method' => 'enviarRevision',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())
                    ->take(8)
                    ->map(fn (array $frame): array => [
                        'file' => $frame['file'] ?? null,
                        'line' => $frame['line'] ?? null,
                        'function' => $frame['function'] ?? null,
                    ])
                    ->all(),
            ]);

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
        $user = auth()->user();

        abort_unless(
            !$this->registro->perteneceAlUsuario(auth()->id())
            && $this->registro->usuarioPuedeRevisar($user),
            403
        );

        if (!$this->registro->puedeAprobarse(auth()->id(), $user)) {
            Notification::make()
                ->title('Revision no disponible')
                ->body('El registro no esta en una etapa revisable del flujo PPS/SS.')
                ->warning()
                ->send();

            return;
        }

        try {
            $this->registro = app(PpsServicioSocialWorkflowService::class)
                ->aprobarEtapa($this->registro, auth()->id(), $user);
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Flujo PPS/SS incompleto')
                ->body($e->getMessage())
                ->warning()
                ->send();

            return;
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Error')
                ->body('No se pudo aprobar el registro. Intente nuevamente.')
                ->danger()
                ->send();

            return;
        }

        $esAprobacionFinal = $this->registro->estado === PpsServicioSocial::ESTADO_APROBADO;

        Notification::make()
            ->title($esAprobacionFinal ? 'Registro aprobado' : 'Etapa aprobada')
            ->body($esAprobacionFinal
                ? 'El FORM-DVUS-015/016 fue aprobado correctamente.'
                : 'El registro avanzo a la siguiente etapa del flujo PPS/SS.')
            ->success()
            ->send();
    }

    public function abrirModalRechazo(): void
    {
        $this->registro->refresh();
        $user = auth()->user();

        abort_unless(
            !$this->registro->perteneceAlUsuario(auth()->id())
            && $this->registro->usuarioPuedeRevisar($user),
            403
        );

        if (!$this->registro->puedeRechazarse(auth()->id(), $user)) {
            Notification::make()
                ->title('Revision no disponible')
                ->body('La etapa actual del flujo PPS/SS no permite rechazo.')
                ->warning()
                ->send();

            return;
        }

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
        $user = auth()->user();

        abort_unless(
            !$this->registro->perteneceAlUsuario(auth()->id())
            && $this->registro->usuarioPuedeRevisar($user),
            403
        );

        if (!$this->registro->puedeRechazarse(auth()->id(), $user)) {
            Notification::make()
                ->title('Revision no disponible')
                ->body('La etapa actual del flujo PPS/SS no permite rechazo.')
                ->warning()
                ->send();

            return;
        }

        $this->validate([
            'motivoRechazo' => 'required|string|min:5|max:5000',
        ], [], [
            'motivoRechazo' => 'motivo de rechazo',
        ]);

        try {
            $this->registro = app(PpsServicioSocialWorkflowService::class)
                ->rechazar($this->registro, $this->motivoRechazo, auth()->id(), $user);
            $this->cerrarModalRechazo();
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Revision no disponible')
                ->body($e->getMessage())
                ->warning()
                ->send();

            return;
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

    public function iniciarSubsanacion(): void
    {
        $this->registro->refresh();

        if (!$this->registro->puedeSubsanarse(auth()->id())) {
            Notification::make()
                ->title('Subsanacion no disponible')
                ->body('Solo el usuario creador puede subsanar registros rechazados con flujo PPS/SS valido.')
                ->warning()
                ->send();

            return;
        }

        try {
            $this->registro = app(PpsServicioSocialWorkflowService::class)
                ->iniciarSubsanacion($this->registro, auth()->id());
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Subsanacion no disponible')
                ->body($e->getMessage())
                ->warning()
                ->send();

            return;
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Error')
                ->body('No se pudo iniciar la subsanacion. Intente nuevamente.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Subsanacion iniciada')
            ->body('El registro volvio a borrador para que pueda corregirlo.')
            ->success()
            ->send();

        $this->redirectRoute('pps-servicio-social.edit', ['id' => $this->registro->id]);
    }

    private function canViewRecord(PpsServicioSocial $registro): bool
    {
        $user = auth()->user();

        if (
            $user?->can('proyectos.historial')
            || $user?->can('proyectos.revision-final')
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

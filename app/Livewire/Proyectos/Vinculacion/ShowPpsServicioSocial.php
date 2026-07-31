<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\PpsServicioSocial;
use App\Services\PpsServicioSocial\PpsServicioSocialWorkflowService;
use App\Support\Notification;
use App\Support\PpsServicioSocial\FormDvus014Data;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ShowPpsServicioSocial extends Component
{
    public PpsServicioSocial $registro;
    public array $camposFaltantesEnvio = [];
    public bool $subsanarModal = false;
    public string $subsanarComentario = '';

    public function mount(int $id): void
    {
        $registro = PpsServicioSocial::with(['flujoAprobacion', 'etapaActual'])->findOrFail($id);

        abort_unless($this->canViewRecord($registro), 403);

        $this->registro = $registro;
    }

    public function enviarRevision(): void
    {
        $this->registro->refresh();

        if ($this->registro->estado !== 'borrador') {
            Notification::make()
                ->title('Envio no disponible')
                ->body('Solo los registros en estado borrador pueden enviarse a revisión.')
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
                ->body('Complete los campos obligatorios antes de enviar a revisión.')
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
            Log::error('Error enviando PPS/SS a revisión', [
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
                ->body('No se pudo enviar el registro a revisión. Intente nuevamente.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Registro enviado')
            ->body('El FORM-DVUS-014 fue enviado a revisión correctamente.')
            ->success()
            ->send();
    }

    public function aprobar(): void
    {
        $this->aprobarEtapa();
    }

    public function aprobarEtapa(): void
    {
        $this->registro->refresh();
        $user = auth()->user();

        abort_unless($this->registro->usuarioPuedeRevisar($user), 403);

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

        $esAprobacionFinal = $this->registro->estado === 'aprobado';

        Notification::make()
            ->title($esAprobacionFinal ? 'Registro aprobado' : 'Etapa aprobada')
            ->body($esAprobacionFinal
                ? 'El FORM-DVUS-014 fue aprobado correctamente.'
                : 'El registro avanzó a la siguiente etapa del flujo PPS/SS.')
            ->success()
            ->send();
    }

    public function abrirModalRechazo(): void
    {
        $this->abrirModalSubsanacion();
    }

    public function abrirModalSubsanacion(): void
    {
        $this->registro->refresh();
        $user = auth()->user();

        abort_unless($this->registro->usuarioPuedeRevisar($user), 403);

        if (!$this->registro->puedeRechazarse(auth()->id(), $user)) {
            Notification::make()
                ->title('Revision no disponible')
                ->body('La etapa actual del flujo PPS/SS no permite enviar a subsanación.')
                ->warning()
                ->send();

            return;
        }

        $this->resetErrorBag('subsanarComentario');
        $this->subsanarComentario = '';
        $this->subsanarModal = true;
    }

    public function cerrarModalRechazo(): void
    {
        $this->cerrarModalSubsanacion();
    }

    public function cerrarModalSubsanacion(): void
    {
        $this->subsanarModal = false;
        $this->subsanarComentario = '';
        $this->resetErrorBag('subsanarComentario');
    }

    public function rechazar(): void
    {
        $this->enviarASubsanar();
    }

    public function enviarASubsanar(): void
    {
        $this->registro->refresh();
        $user = auth()->user();

        abort_unless($this->registro->usuarioPuedeRevisar($user), 403);

        if (!$this->registro->puedeRechazarse(auth()->id(), $user)) {
            Notification::make()
                ->title('Revision no disponible')
                ->body('La etapa actual del flujo PPS/SS no permite enviar a subsanación.')
                ->warning()
                ->send();

            return;
        }

        $this->validate([
            'subsanarComentario' => 'required|string|min:5|max:5000',
        ], [], [
            'subsanarComentario' => 'observaciones',
        ]);

        try {
            $this->registro = app(PpsServicioSocialWorkflowService::class)
                ->rechazar($this->registro, $this->subsanarComentario, auth()->id(), $user);
            $this->cerrarModalSubsanacion();
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
                ->body('No se pudo enviar el registro a subsanación. Intente nuevamente.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Registro enviado a subsanación')
            ->body('El FORM-DVUS-014 fue devuelto para correcciones.')
            ->warning()
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
                ->body('No se pudo iniciar la subsanación. Intente nuevamente.')
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
        $activeRole = $user?->activeRole;

        if (
            $activeRole?->hasPermissionTo('proyectos.historial')
            || $activeRole?->hasPermissionTo('proyectos.revision-final')
            || in_array($activeRole?->name, ['admin', 'Director/Enlace'], true)
        ) {
            return true;
        }

        return $registro->perteneceAlUsuario(auth()->id())
            || $registro->usuarioPuedeRevisar($user);
    }

    public function render(): View
    {
        $this->registro->loadMissing(['flujoAprobacion', 'etapaActual']);

        return view('livewire.proyectos.vinculacion.show-pps-servicio-social', [
            'historialRouteName' => $this->historialRouteName(),
            'anexos' => $this->anexosRegistrados(),
            'formData' => FormDvus014Data::from($this->registro),
        ]);
    }

    private function historialRouteName(): string
    {
        $activeRole = auth()->user()?->activeRole;

        if ($activeRole?->hasPermissionTo('docente.proyectos')) {
            return 'proyectosDocente';
        }

        if ($activeRole?->hasPermissionTo('director.proyectos')) {
            return 'proyectosCentroFacultad';
        }

        if ($activeRole?->hasPermissionTo('proyectos.historial')) {
            return 'listarProyectosVinculacion';
        }

        return 'inicio';
    }

    private function anexosRegistrados(): array
    {
        return collect([
            [
                'tipo' => 'carta-formalizacion',
                'titulo' => 'Carta de formalización',
                'path' => $this->registro->archivo_carta_formalizacion,
                'marcado' => (bool) $this->registro->adjunta_carta_formalizacion,
            ],
            [
                'tipo' => 'convenio-marco',
                'titulo' => 'Convenio marco',
                'path' => $this->registro->archivo_convenio_marco,
                'marcado' => (bool) $this->registro->adjunta_convenio_marco,
            ],
        ])
            ->filter(fn (array $anexo): bool => filled($anexo['path']) || $anexo['marcado'])
            ->map(function (array $anexo): array {
                $path = filled($anexo['path']) ? $this->normalizePublicPath((string) $anexo['path']) : null;
                $exists = $path ? Storage::disk('public')->exists($path) : false;

                return [
                    'tipo' => $anexo['tipo'],
                    'titulo' => $anexo['titulo'],
                    'archivo' => $path ? basename($path) : null,
                    'marcado' => $anexo['marcado'],
                    'exists' => $exists,
                    'view_url' => $exists ? route('pps-servicio-social.anexo', [
                        'id' => $this->registro->id,
                        'tipo' => $anexo['tipo'],
                    ]) : null,
                    'download_url' => $exists ? route('pps-servicio-social.anexo', [
                        'id' => $this->registro->id,
                        'tipo' => $anexo['tipo'],
                        'download' => 1,
                    ]) : null,
                ];
            })
            ->values()
            ->all();
    }

    private function normalizePublicPath(string $path): string
    {
        $path = ltrim($path, '/');
        $path = preg_replace('#^storage/#', '', $path);
        $path = preg_replace('#^public/#', '', $path);
        $path = preg_replace('#^app/public/#', '', $path);

        return $path;
    }
}

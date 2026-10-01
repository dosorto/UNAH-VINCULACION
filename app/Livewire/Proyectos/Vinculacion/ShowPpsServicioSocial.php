<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\PpsDocumentoGenerado;
use App\Models\PpsServicioSocial;
use App\Services\PpsServicioSocial\PpsDocumentoGenerator;
use App\Services\PpsServicioSocial\PpsServicioSocialWorkflowService;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
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

        abort_unless($registro->puedeConsultarse(auth()->id(), auth()->user()), 403);

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
                ->body('No se pudo enviar el registro a revisión. Detalle: '.$e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Registro enviado')
            ->body('El FORM-DVUS-014 fue enviado a revisión y se generó la autorización de PPS.')
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

    public function eliminarBorrador(): void
    {
        $this->registro->refresh();
        abort_unless($this->registro->puedeEliminarBorrador(auth()->id()), 403);

        DB::transaction(function (): void {
            activity('PPS / Servicio Social')->performedOn($this->registro)->causedBy(auth()->user())
                ->withProperties(['accion' => 'eliminacion_logica', 'estado' => 'borrador'])
                ->log('Borrador eliminado lógicamente');
            $this->registro->delete();
        });

        Notification::make()->title('Borrador eliminado')->body('El borrador de PPS / Servicio Social fue eliminado.')->success()->send();
        $this->redirectRoute($this->historialRouteName());
    }

    public function render(): View
    {
        $this->registro->loadMissing([
            'flujoAprobacion',
            'etapaActual',
            'documentosGenerados',
            'historialEstados' => fn ($query) => $query->with(['empleado', 'tipoestado'])->orderByDesc('created_at'),
        ]);

        return view('livewire.proyectos.vinculacion.show-pps-servicio-social', [
            'historialRouteName' => $this->historialRouteName(),
            'documentos' => $this->documentos(),
            'etapasVisuales' => $this->etapasVisuales(),
            'movimientos' => $this->registro->historialEstados,
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

    /**
     * Pestañas del visor: la ficha y sus adjuntos en el orden del proceso (solicitud, respuesta de
     * la institución, convenio y autorización). Cada una se muestra en el visor de PDF.
     *
     * @return list<array{clave: string, titulo: string, ver_url: ?string, descargar_url: ?string, detalle: ?string, versiones: list<array{version: int, url: string}>}>
     */
    private function documentos(): array
    {
        $ficha = [
            'clave' => 'ficha',
            'titulo' => 'Ficha FORM-DVUS-014',
            // La marca de tiempo renueva el visor cuando cambian los datos del registro.
            'ver_url' => route('pps-servicio-social.pdf', ['id' => $this->registro->id, 'ver' => 1, 'v' => $this->registro->updated_at?->timestamp]),
            'descargar_url' => route('pps-servicio-social.pdf', $this->registro->id),
            'detalle' => null,
            'versiones' => [],
        ];

        $adjuntos = array_filter([
            $this->documentoGenerado(PpsDocumentoGenerator::SOLICITUD, 'Solicitud de práctica'),
            $this->anexo('carta-formalizacion', 'Carta de formalización', $this->registro->archivo_carta_formalizacion, (bool) $this->registro->adjunta_carta_formalizacion),
            $this->anexo('convenio-marco', 'Convenio marco', $this->registro->archivo_convenio_marco, (bool) $this->registro->adjunta_convenio_marco),
            $this->documentoGenerado(PpsDocumentoGenerator::AUTORIZACION, 'Autorización de PPS'),
        ]);

        return [$ficha, ...array_values($adjuntos)];
    }

    /** Última versión de una carta generada; las anteriores quedan para descargar. */
    private function documentoGenerado(string $tipo, string $titulo): ?array
    {
        $versiones = $this->registro->documentosGenerados
            ->where('tipo', $tipo)
            ->sortByDesc('version')
            ->values();
        $ultima = $versiones->first();

        if (! $ultima instanceof PpsDocumentoGenerado) {
            return null;
        }

        return [
            'clave' => $tipo,
            'titulo' => $titulo,
            'ver_url' => route('pps-servicio-social.documento-generado', ['documento' => $ultima->id, 'ver' => 1]),
            'descargar_url' => route('pps-servicio-social.documento-generado', $ultima->id),
            'detalle' => 'Versión '.$ultima->version.($ultima->generado_en ? ' · generada el '.$ultima->generado_en->format('d/m/Y H:i') : ''),
            'versiones' => $versiones->slice(1)
                ->map(fn (PpsDocumentoGenerado $documento): array => [
                    'version' => (int) $documento->version,
                    'url' => route('pps-servicio-social.documento-generado', $documento->id),
                ])
                ->values()
                ->all(),
        ];
    }

    /** Anexo subido en el formulario; si está marcado pero sin archivo, la pestaña lo indica. */
    private function anexo(string $tipo, string $titulo, ?string $path, bool $marcado): ?array
    {
        if (blank($path) && ! $marcado) {
            return null;
        }

        $path = filled($path) ? $this->normalizePublicPath((string) $path) : null;
        $existe = $path !== null && Storage::disk('public')->exists($path);

        return [
            'clave' => $tipo,
            'titulo' => $titulo,
            'ver_url' => $existe ? route('pps-servicio-social.anexo', ['id' => $this->registro->id, 'tipo' => $tipo]) : null,
            'descargar_url' => $existe ? route('pps-servicio-social.anexo', ['id' => $this->registro->id, 'tipo' => $tipo, 'download' => 1]) : null,
            'detalle' => $path ? basename($path) : null,
            'versiones' => [],
        ];
    }

    /**
     * Etapas del flujo con el estado de su firma más reciente, para los pasos del encabezado.
     *
     * @return list<array{nombre: string, estado: string, actual: bool}>
     */
    private function etapasVisuales(): array
    {
        $flujo = $this->registro->resolveFlujoAprobacion();

        if (! $flujo) {
            return [];
        }

        $firmas = $this->registro->firmasDeEtapa()
            ->where('flujo_aprobacion_id', $flujo->id)
            ->where('estado_revision', '!=', 'Anulado')
            ->orderByDesc('revision_ciclo')
            ->orderByDesc('id')
            ->get()
            ->groupBy('flujo_aprobacion_etapa_id');
        $enRevision = in_array($this->registro->estado, ['enviado', 'en_revision'], true);

        return $this->registro->etapasActivasDelFlujo($flujo)
            ->map(fn ($etapa): array => [
                'nombre' => (string) $etapa->nombre,
                'estado' => (string) ($firmas->get($etapa->id)?->first()?->estado_revision ?? 'Pendiente'),
                'actual' => $enRevision && (int) $etapa->id === (int) $this->registro->etapa_actual_id,
            ])
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

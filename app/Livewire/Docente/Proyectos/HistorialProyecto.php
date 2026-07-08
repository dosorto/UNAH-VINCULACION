<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\EstadoProyecto;
use App\Models\User;
use App\Models\Proyecto\EmpleadoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Estado\TipoEstado;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class HistorialProyecto extends Component
{
    use WithFileUploads;

    public Proyecto $proyecto;
    public bool $esCoordinador = false;

    public bool $informeIntermedioModal = false;
    public $informeIntermedioFile = null;

    public bool $informeFinalModal = false;
    public $informeFinalFile = null;

    public bool $subsanarModal = false;
    public string $subsanarComentario = '';

    public function mount(Proyecto $proyecto): void
    {
        $this->proyecto = $proyecto;

        $user = auth()->user();
        $esAdminSistema = $user && $user->hasAnyRole(['admin', 'Director/Enlace', 'Revisor Vinculacion']);

        if ($user && $user->empleado) {
            $this->esCoordinador = $proyecto->coordinador && $proyecto->coordinador->id === $user->empleado->id;
        }

        if (!$esAdminSistema) {
            if (!$user || !$user->empleado) {
                abort(403, 'No tiene permiso para ver este proyecto');
            }

            $empleadoProyecto = EmpleadoProyecto::where('proyecto_id', $proyecto->id)->first();

            if ($empleadoProyecto) {
                $this->authorize('view', $empleadoProyecto);
            } else {
                $esFirmante = FirmaProyecto::where('firmable_type', Proyecto::class)
                    ->where('firmable_id', $proyecto->id)
                    ->where('empleado_id', $user->empleado->id)
                    ->exists();

                if (!$this->esCoordinador && !$esFirmante) {
                    abort(403, 'No tiene permiso para ver este proyecto. Solo el coordinador, firmantes o un administrador pueden acceder.');
                }
            }
        }

    }

    public function openSubirIntermedio(): void
    {
        $this->informeIntermedioFile = null;
        $this->informeIntermedioModal = true;
    }

    public function subirInformeIntermedio(): void
    {
        $this->validate(['informeIntermedioFile' => 'required|file|mimes:pdf|max:20480']);

        $path = $this->informeIntermedioFile->store('documentos', 'public');
        $proyecto = $this->proyecto;

        try {
            $proyecto->registrarDocumentoDesdeFlujo('Informe Intermedio', $path, auth()->user()->empleado);
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo enviar el informe')->body($e->getMessage())->danger()->send();
            return;
        }

        $this->informeIntermedioModal = false;
        $this->informeIntermedioFile = null;

        Notification::make()->title('Éxito')->body('Informe Intermedio subido correctamente')->success()->send();
    }

    public function openSubirFinal(): void
    {
        $this->informeFinalFile = null;
        $this->informeFinalModal = true;
    }

    public function subirInformeFinal(): void
    {
        $this->validate(['informeFinalFile' => 'required|file|mimes:pdf|max:20480']);

        $path = $this->informeFinalFile->store('documentos', 'public');
        $proyecto = $this->proyecto;

        try {
            $proyecto->registrarDocumentoDesdeFlujo('Informe Final', $path, auth()->user()->empleado);
        } catch (\Throwable $e) {
            Notification::make()->title('No se pudo enviar el informe')->body($e->getMessage())->danger()->send();
            return;
        }

        $this->informeFinalModal = false;
        $this->informeFinalFile = null;

        Notification::make()->title('Éxito')->body('Informe Final subido correctamente')->success()->send();
    }

    public function firmaPendienteRevision(): ?FirmaProyecto
    {
        $estadoActualId = $this->estadoActualProyectoId();

        if (! $estadoActualId) {
            return null;
        }

        return $this->proyecto
            ->firma_proyecto()
            ->with(['cargo_firma.tipoCargoFirma', 'proyecto.estadoActual'])
            ->where('estado_revision', 'Pendiente')
            ->whereHas('cargo_firma', fn ($query) => $query->where('tipo_estado_id', $estadoActualId))
            ->get()
            ->first(fn (FirmaProyecto $firma) => $this->canActOnFirma($firma));
    }

    public function puedeSubsanar(): bool
    {
        return (bool) $this->firmaPendienteRevision();
    }

    public function openSubsanar(): void
    {
        $this->authorizeFirmaPendiente();
        $this->subsanarComentario = '';
        $this->subsanarModal = true;
    }

    public function subsanar(): void
    {
        $this->validate(['subsanarComentario' => 'required|string']);

        $proyecto = $this->proyecto->fresh();
        $user = auth()->user();

        try {
            $firmaRechazadaPorEtapa = $this->firmaRechazadaActualPorEtapa($proyecto);

            if ($firmaRechazadaPorEtapa) {
                if (! $user) {
                    throw new \RuntimeException('No tiene autorización para reenviar este registro desde subsanación.');
                }

                $this->reenviarDesdeSubsanacionPorEtapa(
                    $firmaRechazadaPorEtapa,
                    $user,
                    $this->empleadosPorEtapaParaReenvio($firmaRechazadaPorEtapa)
                );

                $this->subsanarModal = false;
                $this->subsanarComentario = '';
                $this->proyecto = $this->proyecto->fresh();

                Notification::make()
                    ->title('Éxito')
                    ->body('El registro fue reenviado correctamente para continuar su revisión.')
                    ->success()
                    ->send();

                return;
            }
        } catch (\RuntimeException $exception) {
            Notification::make()
                ->title('No se pudo reenviar el registro')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $firma = $this->authorizeFirmaPendiente();

        $this->proyecto->firma_proyecto()->update([
            'estado_revision' => 'Pendiente',
            'firma_id'        => null,
            'sello_id'        => null,
            'fecha_firma'     => null,
        ]);

        $this->proyecto->estado_proyecto()->create([
            'empleado_id'    => auth()->user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Subsanacion')->first()->id,
            'fecha'          => now(),
            'comentario'     => $this->subsanarComentario,
        ]);

        $this->subsanarModal = false;
        $this->subsanarComentario = '';
        $this->proyecto = $this->proyecto->fresh();

        Notification::make()
            ->title('Proyecto enviado a subsanacion')
            ->body('La etapa '.$firma->cargo_firma?->tipoCargoFirma?->nombre.' devolvio el proyecto para correcciones.')
            ->warning()
            ->send();
    }

    protected function firmaRechazadaActualPorEtapa(Proyecto $proyecto): ?FirmaProyecto
    {
        $firmasRechazadas = $proyecto->firma_proyecto()
            ->whereNull('deleted_at')
            ->where('estado_revision', 'Rechazado')
            ->whereNotNull('flujo_aprobacion_id')
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNotNull('revision_ciclo')
            ->whereNotNull('orden_revision')
            ->orderBy('orden_revision')
            ->orderBy('id')
            ->get()
            ->filter(function (FirmaProyecto $firma): bool {
                $ultimoCiclo = FirmaProyecto::query()
                    ->where('firmable_type', $firma->firmable_type)
                    ->where('firmable_id', $firma->firmable_id)
                    ->where('flujo_aprobacion_id', $firma->flujo_aprobacion_id)
                    ->whereNotNull('flujo_aprobacion_etapa_id')
                    ->whereNull('deleted_at')
                    ->max('revision_ciclo');

                return $firma->usaFlujoPorEtapa()
                    && $ultimoCiclo !== null
                    && (int) $firma->revision_ciclo === (int) $ultimoCiclo;
            })
            ->values();

        if ($firmasRechazadas->count() > 1) {
            throw new \RuntimeException('El último ciclo contiene más de una etapa rechazada y no puede reenviarse automáticamente.');
        }

        return $firmasRechazadas->first();
    }

    protected function empleadosPorEtapaParaReenvio(FirmaProyecto $firmaRechazada): array
    {
        $firmas = FirmaProyecto::query()
            ->with('empleado')
            ->where('firmable_type', $firmaRechazada->firmable_type)
            ->where('firmable_id', $firmaRechazada->firmable_id)
            ->where('flujo_aprobacion_id', $firmaRechazada->flujo_aprobacion_id)
            ->where('revision_ciclo', $firmaRechazada->revision_ciclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNotNull('orden_revision')
            ->where('orden_revision', '>=', $firmaRechazada->orden_revision)
            ->whereNull('deleted_at')
            ->orderBy('orden_revision')
            ->orderBy('id')
            ->get()
            ->reject(fn (FirmaProyecto $firma): bool => $firma->estado_revision === 'Anulado')
            ->values();

        $empleadosPorEtapa = [];

        foreach ($firmas as $firma) {
            $etapaId = (int) $firma->flujo_aprobacion_etapa_id;
            $nombreEtapa = $firma->etapa_nombre ?: $firma->flujoEtapa?->nombre ?: 'sin nombre';

            if (array_key_exists($etapaId, $empleadosPorEtapa)) {
                throw new \RuntimeException(sprintf(
                    'El ciclo contiene más de una asignación activa para la etapa "%s".',
                    $nombreEtapa
                ));
            }

            if (! $firma->empleado_id || ! $firma->empleado || $firma->empleado->trashed()) {
                throw new \RuntimeException(sprintf(
                    'No existe un empleado válido para reenviar la etapa "%s".',
                    $nombreEtapa
                ));
            }

            $empleadosPorEtapa[$etapaId] = (int) $firma->empleado_id;
        }

        return $empleadosPorEtapa;
    }

    public function aprobarFirmaPendiente(): void
    {
        $firma = $this->authorizeFirmaPendiente();

        $firma->update([
            'estado_revision' => 'Aprobado',
            'firma_id'        => auth()->user()?->empleado?->firma?->id,
            'sello_id'        => auth()->user()?->empleado?->sello?->id,
            'fecha_firma'     => now(),
        ]);

        $this->proyecto->anularFirmasPendientesDuplicadasDeCargo($firma->cargo_firma_id, $firma->id);

        $this->proyecto->sincronizarFirmasDelFlujo();

        $nextEstadoId = $this->proyecto->nextEstadoIdEnFlujo($firma->cargo_firma_id)
            ?? $this->proyecto->estadoFinalProcesoId(Proyecto::FLUJO_INSCRIPCION);

        if ($nextEstadoId) {
            $this->proyecto->estado_proyecto()->create([
                'empleado_id'    => auth()->user()->empleado->id,
                'tipo_estado_id' => $nextEstadoId,
                'fecha'          => now(),
                'comentario'     => 'Firmado y aprobado en este estado',
            ]);
        }

        $this->proyecto = $this->proyecto->fresh();

        Notification::make()->title('Proyecto aprobado correctamente')->success()->send();
    }

    protected function reenviarDesdeSubsanacionPorEtapa(
        FirmaProyecto $firmaRechazada,
        User $user,
        array $empleadosPorEtapa
    ): Collection {
        return DB::transaction(function () use ($firmaRechazada, $user, $empleadosPorEtapa): Collection {
            $firmaBloqueada = FirmaProyecto::query()
                ->whereKey($firmaRechazada->id)
                ->lockForUpdate()
                ->first();

            if (! $firmaBloqueada) {
                throw new \RuntimeException('La firma indicada no corresponde a una etapa rechazada.');
            }

            [$proyecto, $documento] = $this->resolverFirmableParaReenvioPorEtapa($firmaBloqueada);

            Proyecto::query()->whereKey($proyecto->id)->lockForUpdate()->firstOrFail();

            if ($documento) {
                DocumentoProyecto::query()->whereKey($documento->id)->lockForUpdate()->firstOrFail();
            }

            $this->bloquearFirmasDelCicloRechazadoParaReenvio($proyecto, $firmaBloqueada, $documento);
            $firmaBloqueada = $firmaBloqueada->fresh();

            if (! $this->usuarioPuedeReenviarDesdeSubsanacion($proyecto, $user)) {
                throw new \RuntimeException('No tiene autorización para reenviar este registro desde subsanación.');
            }

            $this->validarRegistroEnSubsanacionParaReenvio($proyecto, $documento);

            $firmasNuevoCiclo = $proyecto->crearNuevoCicloDesdeFirmaRechazada($firmaBloqueada, $empleadosPorEtapa);

            if ($firmasNuevoCiclo->isEmpty()) {
                throw new \RuntimeException('No se pudo crear el nuevo ciclo de revisión.');
            }

            $primeraFirma = $firmasNuevoCiclo->first()->fresh();
            $this->validarPrimeraFirmaDeReenvioPorEtapa($proyecto, $firmaBloqueada, $primeraFirma, $documento);

            $tipoEstadoId = $primeraFirma->cargo_firma()->value('tipo_estado_id');
            $empleadoId = $user->empleado?->id;

            if (! $tipoEstadoId || ! $empleadoId) {
                throw new \RuntimeException('No se pudo determinar de forma segura la primera etapa del nuevo ciclo.');
            }

            $this->registrarEstadoDeReenvioPorEtapa($proyecto, $documento, (int) $tipoEstadoId, (int) $empleadoId, $primeraFirma);
            $this->validarReenvioPorEtapaCompletado($proyecto, $documento, $firmaBloqueada, $primeraFirma);

            return $firmasNuevoCiclo->map(fn (FirmaProyecto $firma): FirmaProyecto => $firma->fresh())->values();
        });
    }

    protected function resolverFirmableParaReenvioPorEtapa(FirmaProyecto $firma): array
    {
        if ($firma->firmable_type === Proyecto::class) {
            $proyecto = Proyecto::query()->whereKey($firma->firmable_id)->first();

            if (! $proyecto || $proyecto->trashed()) {
                throw new \RuntimeException('La firma no pertenece al proyecto indicado.');
            }

            $this->validarProyectoDelHistorialParaReenvio($proyecto);

            return [$proyecto, null];
        }

        if ($firma->firmable_type === DocumentoProyecto::class) {
            $documento = DocumentoProyecto::query()->whereKey($firma->firmable_id)->first();
            $proyecto = $documento?->proyecto()->first();

            if (! $documento || ! $proyecto || $proyecto->trashed()) {
                throw new \RuntimeException('La firma no pertenece al proyecto indicado.');
            }

            $this->validarProyectoDelHistorialParaReenvio($proyecto);

            return [$proyecto, $documento];
        }

        throw new \RuntimeException('El tipo de registro no admite reenvío mediante flujo por etapa.');
    }

    protected function validarProyectoDelHistorialParaReenvio(Proyecto $proyecto): void
    {
        if (isset($this->proyecto) && $this->proyecto->exists && (int) $this->proyecto->id !== (int) $proyecto->id) {
            throw new \RuntimeException('La firma no pertenece al proyecto indicado.');
        }
    }

    protected function bloquearFirmasDelCicloRechazadoParaReenvio(
        Proyecto $proyecto,
        FirmaProyecto $firma,
        ?DocumentoProyecto $documento = null
    ): void {
        $relation = $documento
            ? $documento->firma_documento()
            : $proyecto->firma_proyecto();

        $relation
            ->where('flujo_aprobacion_id', $firma->flujo_aprobacion_id)
            ->where('revision_ciclo', $firma->revision_ciclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->get();
    }

    protected function usuarioPuedeReenviarDesdeSubsanacion(Proyecto $proyecto, User $user): bool
    {
        $empleado = $user->empleado;

        if (! $empleado || $empleado->trashed() || ! $user->can('docente.crear-proyecto')) {
            return false;
        }

        return $proyecto->coordinador_proyecto()
            ->where('empleado_id', $empleado->id)
            ->exists();
    }

    protected function validarRegistroEnSubsanacionParaReenvio(Proyecto $proyecto, ?DocumentoProyecto $documento = null): void
    {
        $estado = $documento
            ? $documento->estado
            : $proyecto->estado;

        if ($estado?->tipoestado?->nombre !== 'Subsanacion') {
            throw new \RuntimeException('El registro no se encuentra en estado de Subsanación.');
        }
    }

    protected function validarPrimeraFirmaDeReenvioPorEtapa(
        Proyecto $proyecto,
        FirmaProyecto $firmaRechazada,
        FirmaProyecto $primeraFirma,
        ?DocumentoProyecto $documento = null
    ): void {
        if ($primeraFirma->estado_revision !== 'Pendiente'
            || (int) $primeraFirma->revision_ciclo !== (int) $firmaRechazada->revision_ciclo + 1
            || (int) $primeraFirma->flujo_aprobacion_id !== (int) $firmaRechazada->flujo_aprobacion_id
            || (int) $primeraFirma->flujo_aprobacion_etapa_id !== (int) $firmaRechazada->flujo_aprobacion_etapa_id
            || (int) $primeraFirma->orden_revision !== (int) $firmaRechazada->orden_revision
            || $primeraFirma->firmable_type !== $firmaRechazada->firmable_type
            || (int) $primeraFirma->firmable_id !== (int) $firmaRechazada->firmable_id
            || ! $primeraFirma->cargo_firma_id
            || ! $primeraFirma->cargo_firma()->exists()
            || ! $primeraFirma->cargo_firma()->value('tipo_estado_id')
            || ! $proyecto->firmaEsActualEnFlujoPorEtapa($primeraFirma)
        ) {
            throw new \RuntimeException('No se pudo determinar de forma segura la primera etapa del nuevo ciclo.');
        }
    }

    protected function registrarEstadoDeReenvioPorEtapa(
        Proyecto $proyecto,
        ?DocumentoProyecto $documento,
        int $tipoEstadoId,
        int $empleadoId,
        FirmaProyecto $primeraFirma
    ): void {
        $relation = $documento
            ? $documento->estado_documento()
            : $proyecto->estado_proyecto();

        $relation->update(['es_actual' => false]);

        EstadoProyecto::withoutEvents(function () use ($relation, $tipoEstadoId, $empleadoId, $primeraFirma): void {
            $relation->create([
                'empleado_id' => $empleadoId,
                'tipo_estado_id' => $tipoEstadoId,
                'fecha' => now(),
                'comentario' => sprintf(
                    'Registro reenviado después de subsanación a la etapa "%s".',
                    $primeraFirma->etapa_nombre ?: $primeraFirma->etapa_codigo
                ),
                'es_actual' => true,
            ]);
        });
    }

    protected function validarReenvioPorEtapaCompletado(
        Proyecto $proyecto,
        ?DocumentoProyecto $documento,
        FirmaProyecto $firmaRechazada,
        FirmaProyecto $primeraFirma
    ): void {
        $estado = $documento
            ? $documento->estado
            : $proyecto->estado;

        $tipoEstadoId = $primeraFirma->cargo_firma()->value('tipo_estado_id');

        if ($primeraFirma->fresh()->estado_revision !== 'Pendiente'
            || ! $estado
            || (int) $estado->tipo_estado_id !== (int) $tipoEstadoId
            || ! $proyecto->firmaEsActualEnFlujoPorEtapa($primeraFirma->fresh())
            || $proyecto->firmasDeEtapasCompletadas((int) $firmaRechazada->flujo_aprobacion_id, (int) $primeraFirma->revision_ciclo, $documento)
        ) {
            throw new \RuntimeException('No se pudo determinar de forma segura la primera etapa del nuevo ciclo.');
        }

        $firmasPosteriores = $proyecto->firmasDeEtapasDelFlujo(
            (int) $firmaRechazada->flujo_aprobacion_id,
            (int) $primeraFirma->revision_ciclo,
            $documento
        )->filter(fn (FirmaProyecto $firma): bool => (int) $firma->id !== (int) $primeraFirma->id);

        foreach ($firmasPosteriores as $firmaPosterior) {
            if ($proyecto->firmaEsActualEnFlujoPorEtapa($firmaPosterior)) {
                throw new \RuntimeException('No se pudo determinar de forma segura la primera etapa del nuevo ciclo.');
            }
        }
    }

    public function render(): View
    {
        $proyecto = $this->proyecto;

        $documentosIds = DocumentoProyecto::where('proyecto_id', $proyecto->id)->pluck('id')->toArray();

        $estados = EstadoProyecto::where(function ($query) use ($proyecto, $documentosIds) {
            $query->where(function ($q) use ($proyecto) {
                $q->where('estadoable_type', Proyecto::class)->where('estadoable_id', $proyecto->id);
            });
            if (!empty($documentosIds)) {
                $query->orWhere(function ($q) use ($documentosIds) {
                    $q->where('estadoable_type', DocumentoProyecto::class)->whereIn('estadoable_id', $documentosIds);
                });
            }
        })
        ->with(['empleado', 'tipoestado'])
        ->orderByDesc('created_at')
        ->get();

        $diasTranscurridos = $proyecto->created_at
            ? (int) $proyecto->created_at->diffInDays(now())
            : 0;

        return view('livewire.docente.proyectos.historial-proyecto', compact('proyecto', 'estados', 'diasTranscurridos'));
    }

    private function authorizeFirmaPendiente(): FirmaProyecto
    {
        $firma = $this->firmaPendienteRevision();

        abort_unless($firma, 403);

        return $firma;
    }

    private function canActOnFirma(FirmaProyecto $firma): bool
    {
        if ($firma->estado_revision !== 'Pendiente') {
            return false;
        }

        $estadoActualId = $this->estadoActualProyectoId();
        $estadoFirmaId = $firma->cargo_firma?->tipo_estado_id;

        if (! $estadoActualId || ! $estadoFirmaId || (int) $estadoActualId !== (int) $estadoFirmaId) {
            return false;
        }

        $user = auth()->user();
        $activeRoleName = $user?->activeRole?->name;
        $cargoRoleName = $firma->cargo_firma?->tipoCargoFirma?->nombre;

        if (filled($activeRoleName)) {
            return $activeRoleName === $cargoRoleName;
        }

        return $user?->empleado && (int) $firma->empleado_id === (int) $user->empleado->id;
    }

    private function estadoActualProyectoId(): ?int
    {
        return $this->proyecto
            ->estado_proyecto()
            ->where('es_actual', true)
            ->value('tipo_estado_id');
    }
}

<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\EstadoProyecto;
use App\Models\User;
use App\Models\Proyecto\EmpleadoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Estado\TipoEstado;
use App\Concerns\ReenviaDesdeSubsanacionPorEtapa;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class HistorialProyecto extends Component
{
    use WithFileUploads;
    use ReenviaDesdeSubsanacionPorEtapa;

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

    protected function proyectoEsperadoIdParaReenvio(): ?int
    {
        return isset($this->proyecto) && $this->proyecto->exists ? (int) $this->proyecto->id : null;
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

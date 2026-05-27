<?php

namespace App\Livewire\Docente\Proyectos;

use App\Models\Estado\EstadoProyecto;
use App\Models\Proyecto\EmpleadoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Estado\TipoEstado;
use App\Support\Notification;
use Illuminate\Contracts\View\View;
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

        $proyecto->documentos()->where('tipo_documento', 'Informe Intermedio')->each(function ($doc) {
            $doc->firma_documento()->delete();
            $doc->estado_documento()->delete();
        });
        $proyecto->documentos()->where('tipo_documento', 'Informe Intermedio')->delete();

        $documento = $proyecto->documentos()->create([
            'tipo_documento' => 'Informe Intermedio',
            'documento_url'  => $path,
        ]);

        $cargosFirmas = CargoFirma::where('descripcion', 'Documento_intermedio')->get();
        $cargosFirmas->each(function ($cargo) use ($proyecto, $documento) {
            $documento->firma_documento()->create([
                'empleado_id'    => $proyecto->getFirmabyCargo($cargo->tipoCargoFirma->nombre)->empleado->id,
                'cargo_firma_id' => $cargo->id,
                'estado_revision' => 'Pendiente',
                'hash'           => 'hash',
            ]);
        });

        $documento->estado_documento()->create([
            'empleado_id'    => auth()->user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Enlace Vinculacion')->first()->id,
            'fecha'          => now(),
            'comentario'     => 'Documento creado',
        ]);

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

        $proyecto->documentos()->where('tipo_documento', 'Informe Final')->each(function ($doc) {
            $doc->firma_documento()->delete();
            $doc->estado_documento()->delete();
        });
        $proyecto->documentos()->where('tipo_documento', 'Informe Final')->delete();

        $documento = $proyecto->documentos()->create([
            'tipo_documento' => 'Informe Final',
            'documento_url'  => $path,
        ]);

        $cargosFirmas = CargoFirma::where('descripcion', 'Documento_final')->get();
        $cargosFirmas->each(function ($cargo) use ($proyecto, $documento) {
            $documento->firma_documento()->create([
                'empleado_id'    => $proyecto->getFirmabyCargo($cargo->tipoCargoFirma->nombre)->empleado->id,
                'cargo_firma_id' => $cargo->id,
                'estado_revision' => 'Pendiente',
                'hash'           => 'hash',
            ]);
        });

        $documento->estado_documento()->create([
            'empleado_id'    => auth()->user()->empleado->id,
            'tipo_estado_id' => TipoEstado::where('nombre', 'Enlace Vinculacion')->first()->id,
            'fecha'          => now(),
            'comentario'     => 'Documento creado',
        ]);

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

        $nextEstadoId = $this->proyecto->nextEstadoIdForCargo($firma->cargo_firma_id)
            ?? $firma->cargo_firma?->estado_siguiente_id;

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

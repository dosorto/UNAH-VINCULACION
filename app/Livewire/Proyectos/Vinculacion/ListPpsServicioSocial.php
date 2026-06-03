<?php

namespace App\Livewire\Proyectos\Vinculacion;

use App\Models\PpsServicioSocial;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ListPpsServicioSocial extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterEstado = '';
    public string $filterTipo = '';
    public string $viewMode = 'mis';

    public function mount(): void
    {
        if (!$this->ownRecordsQuery()->exists() && $this->pendingReviewRecordsQuery()->exists()) {
            $this->viewMode = 'pendientes';
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterEstado(): void
    {
        $this->resetPage();
    }

    public function updatingFilterTipo(): void
    {
        $this->resetPage();
    }

    public function updatingViewMode(): void
    {
        $this->resetPage();
    }

    public function updatedViewMode(): void
    {
        $this->viewMode = $this->normalizedViewMode();
    }

    private function recordsQuery(): Builder
    {
        return $this->visibleRecordsQuery()
            ->with(['etapaActual.rolRevisor', 'etapaActual.usuarioResponsable'])
            ->when($this->search, fn ($query) => $query->where(function ($subQuery) {
                $subQuery->where('codigo_registro', 'like', '%' . $this->search . '%')
                    ->orWhere('nombre_estudiante', 'like', '%' . $this->search . '%')
                    ->orWhere('numero_cuenta', 'like', '%' . $this->search . '%')
                    ->orWhere('nombre_institucion', 'like', '%' . $this->search . '%');
            }))
            ->when($this->filterEstado, fn ($query) => $query->where('estado', $this->filterEstado))
            ->when($this->filterTipo, fn ($query) => $query->where('tipo_pps_ss', $this->filterTipo))
            ->orderByDesc('created_at');
    }

    private function visibleRecordsQuery(): Builder
    {
        return match ($this->normalizedViewMode()) {
            'pendientes' => $this->pendingReviewRecordsQuery(),
            'todos' => $this->allRecordsQuery(),
            default => $this->ownRecordsQuery(),
        };
    }

    private function ownRecordsQuery(): Builder
    {
        return PpsServicioSocial::query()
            ->where('created_by', auth()->id());
    }

    private function pendingReviewRecordsQuery(): Builder
    {
        $user = auth()->user();

        if (!$user) {
            return PpsServicioSocial::query()->whereRaw('1 = 0');
        }

        if (empty($user->active_role_id)) {
            return PpsServicioSocial::query()->whereRaw('1 = 0');
        }

        $activeRole = $user->activeRole;

        if (!$activeRole) {
            return PpsServicioSocial::query()->whereRaw('1 = 0');
        }

        $activeRoleId = (int) $activeRole->id;
        $isActiveAdmin = $activeRole->name === 'admin';

        return PpsServicioSocial::query()
            ->whereNotIn('estado', $this->nonReviewableStates())
            ->whereNotNull('flujo_aprobacion_id')
            ->whereNotNull('etapa_actual_id')
            ->whereHas('flujoAprobacion', fn (Builder $query) => $query
                ->where('proceso', PpsServicioSocial::PROCESO_FLUJO))
            ->whereHas('etapaActual', function (Builder $query) use ($user, $activeRoleId, $isActiveAdmin): void {
                $query
                    ->whereColumn('flujos_aprobacion_etapas.flujo_aprobacion_id', 'pps_servicio_social.flujo_aprobacion_id')
                    ->where('activo', true)
                    ->whereHas('flujo', fn (Builder $flujoQuery) => $flujoQuery
                        ->where('proceso', PpsServicioSocial::PROCESO_FLUJO));

                if ($isActiveAdmin) {
                    return;
                }

                $query->where(function (Builder $responsableQuery) use ($user, $activeRoleId): void {
                    $responsableQuery
                        ->where(function (Builder $asignacionQuery) use ($user, $activeRoleId): void {
                            $asignacionQuery
                                ->where('requiere_asignacion', true)
                                ->where('usuario_responsable_id', $user->id)
                                ->where(function (Builder $roleQuery) use ($activeRoleId): void {
                                    $roleQuery
                                        ->whereNull('rol_revisor_id')
                                        ->orWhere('rol_revisor_id', $activeRoleId);
                                });
                        })
                        ->orWhere(function (Builder $rolQuery) use ($activeRoleId): void {
                            $rolQuery
                                ->where('requiere_asignacion', false)
                                ->where('rol_revisor_id', $activeRoleId);
                        });
                });
            });
    }

    private function allRecordsQuery(): Builder
    {
        return PpsServicioSocial::query();
    }

    private function canViewAllRecords(): bool
    {
        $user = auth()->user();
        $activeRole = $user?->activeRole;

        return (bool) (
            $activeRole?->hasPermissionTo('proyectos.historial')
            || $activeRole?->hasPermissionTo('proyectos.revision-final')
            || in_array($activeRole?->name, ['admin', 'Director/Enlace'], true)
        );
    }

    private function canCreateRecord(): bool
    {
        return (bool) auth()->user()?->activeRole?->hasPermissionTo('docente.crear-proyecto');
    }

    private function normalizedViewMode(): string
    {
        if ($this->viewMode === 'todos' && !$this->canViewAllRecords()) {
            return 'mis';
        }

        if (in_array($this->viewMode, ['mis', 'pendientes', 'todos'], true)) {
            return $this->viewMode;
        }

        return 'mis';
    }

    private function nonReviewableStates(): array
    {
        return [
            PpsServicioSocial::ESTADO_BORRADOR,
            PpsServicioSocial::ESTADO_APROBADO,
            PpsServicioSocial::ESTADO_RECHAZADO,
            'subsanacion',
        ];
    }

    public function render(): View
    {
        $records = $this->recordsQuery()->paginate(10);
        $visibleRecordsQuery = $this->visibleRecordsQuery();
        $estados = (clone $visibleRecordsQuery)->distinct()->orderBy('estado')->pluck('estado')->filter();
        $tipos = (clone $visibleRecordsQuery)->distinct()->orderBy('tipo_pps_ss')->pluck('tipo_pps_ss')->filter();
        $viewMode = $this->normalizedViewMode();

        return view('livewire.proyectos.vinculacion.list-pps-servicio-social', [
            'records' => $records,
            'estados' => $estados,
            'tipos' => $tipos,
            'viewMode' => $viewMode,
            'ownRecordsCount' => (clone $this->ownRecordsQuery())->count(),
            'pendingReviewCount' => (clone $this->pendingReviewRecordsQuery())->count(),
            'allRecordsCount' => $this->canViewAllRecords() ? (clone $this->allRecordsQuery())->count() : null,
            'canViewAllRecords' => $this->canViewAllRecords(),
            'canCreateRecord' => $this->canCreateRecord(),
        ]);
    }
}

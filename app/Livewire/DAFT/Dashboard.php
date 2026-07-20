<?php

namespace App\Livewire\DAFT;

use App\Models\DAFT\ProgramaCertificacion;
use App\Models\DAFT\ProgramaRevision;
use App\Services\DAFT\ProgramaWorkflowService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    private const ESTADOS_PENDIENTES = ['PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO'];

    public function render(): View
    {
        $programas = ProgramaCertificacion::query();
        $metricas = [
            'total' => (clone $programas)->count(),
            'elaboracion' => (clone $programas)->whereIn('estado_flujo', ['BORRADOR', 'ELABORACION'])->count(),
            'revision' => (clone $programas)->where('estado_flujo', 'EN_REVISION')->count(),
            'subsanacion' => (clone $programas)->where('estado_flujo', 'SUBSANACION')->count(),
            'aprobados' => (clone $programas)->where('estado_flujo', 'APROBADO')->count(),
        ];

        return view('livewire.daft.dashboard', [
            'metricas' => $metricas,
            'actividad' => $this->actividadMensual(),
            'revisionesPendientes' => $this->revisionesPendientes(),
            'programasRecientes' => ProgramaCertificacion::query()
                ->with(['centroFacultad', 'tipoPrograma'])
                ->latest('updated_at')
                ->limit(6)
                ->get(),
        ])->layout('layouts.app', ['hideHorizontalNav' => true]);
    }

    private function revisionesPendientes(): Collection
    {
        $user = Auth::user();
        $workflow = app(ProgramaWorkflowService::class);

        return ProgramaRevision::query()
            ->with([
                'programa.centroFacultad',
                'programa.tipoPrograma',
                'flujoEtapa.rolRevisor',
                'asignadoUsuario',
                'responsableUsuario',
            ])
            ->whereIn('estado', self::ESTADOS_PENDIENTES)
            ->latest('id')
            ->get()
            ->filter(fn (ProgramaRevision $revision): bool => $workflow->usuarioPuedeVer($revision, $user))
            ->values();
    }

    private function actividadMensual(): Collection
    {
        $inicio = CarbonImmutable::now()->startOfMonth()->subMonths(5);
        $creados = ProgramaCertificacion::query()
            ->where('created_at', '>=', $inicio)
            ->get(['created_at']);
        $aprobados = ProgramaCertificacion::query()
            ->where('estado_flujo', 'APROBADO')
            ->where('updated_at', '>=', $inicio)
            ->get(['updated_at']);

        return collect(range(0, 5))->map(function (int $offset) use ($inicio, $creados, $aprobados): array {
            $mes = $inicio->addMonths($offset);

            return [
                'label' => mb_strtoupper($mes->locale('es')->isoFormat('MMM')),
                'creados' => $creados->filter(fn ($programa) => $programa->created_at->isSameMonth($mes))->count(),
                'aprobados' => $aprobados->filter(fn ($programa) => $programa->updated_at->isSameMonth($mes))->count(),
            ];
        });
    }
}

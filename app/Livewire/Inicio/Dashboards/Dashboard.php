<?php

namespace App\Livewire\Inicio\Dashboards;

use App\Models\Personal\Empleado;
use App\Services\Dashboard\ActividadRecienteService;
use App\Services\Dashboard\PanelEstadisticoService;
use App\Services\Dashboard\PanelFormulariosService;
use App\Services\Dashboard\PendientesRevisionService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Panel institucional (rol admin).
 *
 * Antes eran seiscientas líneas que repetían cuatro veces el mismo bloque de
 * consultas por estado —trayendo los proyectos enteros solo para contarlos— y
 * calculaban además las cifras personales del usuario, que este panel nunca
 * llega a mostrar. Todo eso vive ahora en PanelEstadisticoService.
 */
class Dashboard extends Component
{
    /** id del contenedor del gráfico; panel-charts.js indexa las instancias por él. */
    private const ID_GRAFICO = 'panel-serie-admin';

    public int $mesesGrafico = 12;

    public string $buscarDocente = '';

    /** Formulario abierto en «Recorrido»; null abre el que más trámites tiene esperando. */
    public ?string $formularioDetalle = null;

    public function mount(): void
    {
        if (auth()->user()?->can('perfil.editar')) {
            $this->redirect(route('completar_perfil'), navigate: true);
        }
    }

    public function verFormulario(string $codigo): void
    {
        $this->formularioDetalle = $codigo;
    }

    public function alternarRangoGrafico(PanelEstadisticoService $panel): void
    {
        $this->mesesGrafico = $this->mesesGrafico === 12 ? 36 : 12;

        $this->dispatch(
            'panel-grafico-actualizado',
            id: self::ID_GRAFICO,
            config: $panel->serieMensual($panel->ambito(), $this->mesesGrafico),
        );
    }

    public function render(
        PanelEstadisticoService $panel,
        PanelFormulariosService $formularios,
        PendientesRevisionService $pendientes,
        ActividadRecienteService $actividad,
    ): View {
        $usuario = auth()->user();
        $ambito = $panel->ambito($usuario);
        $codigoDetalle = $this->formularioDetalle ?? $formularios->formularioConMasEspera($ambito);

        return view('livewire.inicio.dashboards.dashboard', [
            'ambito' => $ambito,
            'resumen' => $panel->resumenEstados($ambito),

            // Todos los formularios, resumidos en su estado general.
            'tramites' => $formularios->resumen($ambito),
            'matriz' => $formularios->matriz($ambito),
            'atascos' => $formularios->atascos($ambito, 8),
            'detalleFormulario' => $formularios->detalle($ambito, $codigoDetalle),
            'opcionesDetalle' => $formularios->opcionesDetalle($ambito),

            'serieGrafico' => $panel->serieMensual($ambito, $this->mesesGrafico),
            'idGrafico' => self::ID_GRAFICO,

            'tiempos' => $panel->tiemposPorEtapa($ambito, 6),
            'esperando' => $panel->detenidosPorEtapa($ambito),
            'detenidos' => $panel->cuellosDeBotella($ambito, 6, 14),

            'ods' => $panel->rankingPorOds($ambito, 17),
            'centros' => $panel->rankingPorCentro($ambito, 8),
            'deptos' => $panel->rankingPorDepartamento($ambito, 8),
            'categorias' => $panel->rankingPorCategoria($ambito, 6),
            'modalidad' => $panel->distribucionModalidad($ambito),
            'coberturaOds' => $panel->coberturaDimension($ambito, 'ods'),
            'coberturaCentros' => $panel->coberturaDimension($ambito, 'centro'),
            'coberturaDeptos' => $panel->coberturaDimension($ambito, 'departamento'),

            'poblacion' => $panel->alcancePoblacional($ambito),
            'estudiantes' => $panel->participacionEstudiantil($ambito),
            'esfuerzo' => $panel->esfuerzoInstitucional($ambito),

            'docentes' => $this->docentesConProyectos(),
            'totalPendientes' => $pendientes->total($usuario),
            'actividad' => $actividad->para($ambito, 10),
        ]);
    }

    /**
     * Quiénes concentran la vinculación. Reutiliza la forma del mosaico, así
     * que basta con etiqueta y valor.
     *
     * @return list<array{etiqueta:string,valor:int}>
     */
    private function docentesConProyectos(): array
    {
        return Empleado::query()
            ->when(
                $this->buscarDocente !== '',
                fn ($q) => $q->where('nombre_completo', 'like', '%'.$this->buscarDocente.'%')
            )
            ->withCount('proyectos')
            ->having('proyectos_count', '>', 0)
            ->orderByDesc('proyectos_count')
            ->limit(8)
            ->get()
            ->map(fn (Empleado $e): array => [
                'etiqueta' => $e->nombre_completo,
                'valor' => (int) $e->proyectos_count,
            ])
            ->all();
    }
}

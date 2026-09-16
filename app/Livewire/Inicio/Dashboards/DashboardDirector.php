<?php

namespace App\Livewire\Inicio\Dashboards;

use App\Models\Proyecto\Proyecto;
use App\Services\Dashboard\ActividadRecienteService;
use App\Services\Dashboard\MisFormulariosService;
use App\Services\Dashboard\PanelEstadisticoService;
use App\Services\Dashboard\PendientesRevisionService;
use App\Services\Dashboard\RegistroFamiliasTramite;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\TipoAmbito;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Panel de los roles que revisan: Director/Enlace, Director centro, Jefe
 * Departamento, Enlace Vinculación, Coordinador Proyecto, Revisor Vinculación y
 * Director Vinculación.
 *
 * Antes lo calculaba todo sobre empleado_proyecto, es decir, sobre proyectos
 * propios. Quien solo revisa —un Director centro, un Director Vinculación— no
 * tiene ninguno, así que veía las siete tarjetas en cero, el gráfico vacío y la
 * tabla oculta: el panel entero en blanco justo para el rol al que más debía
 * servir.
 *
 * Ahora el contenido se arma según el ámbito que resuelve AmbitoPanelResolver
 * —su centro, su departamento o toda la UNAH— y los proyectos propios pasan a
 * ser una sección más, que solo aparece si de verdad tiene.
 */
class DashboardDirector extends Component
{
    use WithPagination;

    /** id del contenedor del gráfico; panel-charts.js indexa las instancias por él. */
    private const ID_GRAFICO = 'panel-serie-director';

    public int $mesesGrafico = 12;

    public int $pendientesVisibles = 8;

    public int $proyectosVisibles = 10;

    public function verMasPendientes(): void
    {
        $this->pendientesVisibles += 10;
    }

    public function verMasProyectos(): void
    {
        $this->proyectosVisibles += 10;
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
        PendientesRevisionService $pendientes,
        MisFormulariosService $formularios,
        ActividadRecienteService $actividad,
        RegistroFamiliasTramite $familias,
    ): View {
        $usuario = auth()->user();
        $empleadoId = $usuario?->empleado?->id;
        $ambito = $panel->ambito($usuario);
        $institucional = $ambito->esInstitucional();

        $misProyectos = $this->misProyectos($empleadoId);
        $totalPendientes = $pendientes->total($usuario);

        return view('livewire.inicio.dashboards.dashboard-director', [
            'ambito' => $ambito,
            'institucional' => $institucional,
            'esGlobal' => $ambito->tipo === TipoAmbito::Global,

            // Mi bandeja: siempre visible, es la función del rol.
            'pendientes' => $pendientes->paraRolActivo($usuario, $this->pendientesVisibles),
            'totalPendientes' => $totalPendientes,
            'pendientesPorTipo' => $pendientes->porTipo($usuario),
            'esperaMasLarga' => $pendientes->masAntiguoEnDias($usuario),
            'hayMasPendientes' => $totalPendientes > $this->pendientesVisibles,

            // Lo institucional: lo que llena el hueco del panel vacío.
            'resumen' => $institucional ? $panel->resumenEstados($ambito) : null,
            'carriles' => $institucional ? $familias->carriles($ambito) : [],
            'tiempos' => $institucional ? $panel->tiemposPorEtapa($ambito, 6) : [],
            'esperando' => $institucional ? $panel->detenidosPorEtapa($ambito) : [],
            'detenidos' => $institucional ? $panel->cuellosDeBotella($ambito, 6, 14) : [],
            'centros' => $institucional ? $panel->rankingPorCentro($ambito, 8) : [],
            'deptos' => $institucional ? $panel->rankingPorDepartamento($ambito, 8) : [],
            'ods' => $institucional ? $panel->rankingPorOds($ambito, 17) : [],
            'coberturaOds' => $panel->coberturaDimension($ambito, 'ods'),
            'coberturaCentros' => $panel->coberturaDimension($ambito, 'centro'),
            'coberturaDeptos' => $panel->coberturaDimension($ambito, 'departamento'),
            'proyectosAmbito' => $institucional ? $this->proyectosDelAmbito($panel, $ambito) : collect(),

            // Mis proyectos, solo si los hay.
            'tienePropios' => $misProyectos->isNotEmpty(),
            'misProyectos' => $misProyectos,
            'resumenPropios' => $misProyectos->isNotEmpty()
                ? $formularios->resumen($empleadoId, $usuario?->id)
                : null,

            'actividad' => $actividad->para($ambito, 8),
            'idGrafico' => self::ID_GRAFICO,
            'serieGrafico' => $institucional ? $panel->serieMensual($ambito, $this->mesesGrafico) : null,
        ]);
    }

    /**
     * Proyectos donde el usuario participa, con las firmas necesarias para
     * pintar su stepper.
     *
     * El eager-load restringido a firmas con flujo y etapa está comprobado por
     * DashboardMisProyectosLayoutTest: sin él se colarían firmas antiguas sin
     * flujo asociado y el stepper mostraría etapas inexistentes.
     *
     * @return Collection<int, Proyecto>
     */
    private function misProyectos(?int $empleadoId): Collection
    {
        if (! $empleadoId) {
            return collect();
        }

        return Proyecto::query()
            ->whereExists(
                fn ($sub) => $sub->selectRaw('1')
                    ->from('empleado_proyecto')
                    ->whereColumn('empleado_proyecto.proyecto_id', 'proyecto.id')
                    ->where('empleado_proyecto.empleado_id', $empleadoId)
                    ->whereNull('empleado_proyecto.deleted_at')
            )
            ->with([
                'estadoActual.tipoestado',
                'firmasDeEtapa' => fn ($q) => $q
                    ->whereNotNull('flujo_aprobacion_id')
                    ->whereNotNull('flujo_aprobacion_etapa_id')
                    ->where('estado_revision', '!=', 'Anulado')
                    ->orderByDesc('revision_ciclo')
                    ->orderByDesc('id'),
            ])
            ->orderByDesc('proyecto.created_at')
            ->limit($this->proyectosVisibles)
            ->get();
    }

    /**
     * Últimos proyectos del ámbito, para el listado institucional.
     *
     * @return Collection<int, Proyecto>
     */
    private function proyectosDelAmbito(PanelEstadisticoService $panel, AmbitoPanel $ambito): Collection
    {
        return $panel->proyectos($ambito)
            ->with(['estadoActual.tipoestado'])
            ->orderByDesc('proyecto.created_at')
            ->limit(10)
            ->get();
    }
}

<?php

namespace App\Livewire\Inicio\Dashboards;

use App\Services\Dashboard\ActividadRecienteService;
use App\Services\Dashboard\MisFormulariosService;
use App\Services\Dashboard\PanelEstadisticoService;
use App\Services\Dashboard\PendientesRevisionService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Panel del docente: su portafolio de trámites y lo que le toca hacer.
 *
 * Antes calculaba también las cifras institucionales —proyectos de toda la
 * UNAH, total de empleados, últimos registros del sistema— que este panel nunca
 * llega a mostrar, y repetía las consultas por estado que hoy viven en
 * PanelEstadisticoService. Aquí solo queda lo del usuario.
 */
class DasboardDocente extends Component
{
    /** id del contenedor del gráfico; panel-charts.js indexa las instancias por él. */
    private const ID_GRAFICO = 'panel-serie-docente';

    public int $mesesGrafico = 12;

    public int $formulariosVisibles = 15;

    public function mount(): void
    {
        // Quien todavía no completó su perfil no tiene nada que mirar aquí.
        if (auth()->user()?->can('perfil.editar')) {
            $this->redirect(route('completar_perfil'), navigate: true);
        }
    }

    public function verMasFormularios(): void
    {
        $this->formulariosVisibles += 10;
    }

    /** Alterna el gráfico entre el último año y los tres últimos. */
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
        MisFormulariosService $formularios,
        PendientesRevisionService $pendientes,
        ActividadRecienteService $actividad,
    ): View {
        $usuario = auth()->user();
        $empleadoId = $usuario?->empleado?->id;
        $ambito = $panel->ambito($usuario);

        $mis = $formularios->para($empleadoId, $usuario?->id, $this->formulariosVisibles);

        // Lo que exige acción del docente: primero sus propias subsanaciones,
        // después lo que espera su firma. Antes había que deducirlo recorriendo
        // la tabla entera.
        $porSubsanar = $formularios->porSubsanar($empleadoId, $usuario?->id);

        return view('livewire.inicio.dashboards.dasboard-docente', [
            'ambito' => $ambito,
            'resumen' => $formularios->resumen($empleadoId, $usuario?->id),
            'misFormularios' => $mis,
            'porSubsanar' => $porSubsanar,
            'pendientesFirma' => $pendientes->paraRolActivo($usuario, 6),
            'totalPendientes' => $pendientes->total($usuario),
            'actividad' => $actividad->para($ambito, 8),
            'esfuerzo' => $panel->esfuerzoInstitucional($ambito),
            'estudiantes' => $panel->participacionEstudiantil($ambito),
            'poblacion' => $panel->alcancePoblacional($ambito),
            'ods' => $panel->rankingPorOds($ambito, 17),
            'coberturaOds' => $panel->coberturaDimension($ambito, 'ods'),
            'categorias' => $panel->rankingPorCategoria($ambito, 6),
            'idGrafico' => self::ID_GRAFICO,
            'serieGrafico' => $panel->serieMensual($ambito, $this->mesesGrafico),
            'hayMasFormularios' => $formularios->para($empleadoId, $usuario?->id, PHP_INT_MAX)->count() > $this->formulariosVisibles,
        ]);
    }
}

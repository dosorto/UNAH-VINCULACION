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

<<<<<<< HEAD
    /**
     * Unifica en una sola lista "Mis proyectos" todos los formularios que el
     * docente tiene: Proyecto (Desarrollo Local/Voluntariado), PPS/Servicio
     * Social y ENF (Educación No Formal). Cada fila trae su propio stepper
     * de progreso, calculado según el flujo de aprobación real configurado
     * para ese formulario (no una lista fija de 5 etapas).
     */
    private function misFormularios(?int $empleadoId, ?int $userId): Collection
    {
        $rows = collect();

        if ($empleadoId) {
            Proyecto::query()
                ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
                ->where('empleado_proyecto.empleado_id', $empleadoId)
                ->select('proyecto.*')
                ->distinct()
                ->with(['categoria'])
                ->get()
                ->each(function (Proyecto $proyecto) use ($rows): void {
                    [$proceso, $documento] = $proyecto->procesoActivoParaStepper();

                    $rows->push([
                        'kind' => 'proyecto',
                        'nombre' => $proyecto->nombre_proyecto,
                        'categoria' => $proyecto->categoria->pluck('nombre')->implode(', ') ?: null,
                        'fecha_inicio' => $proyecto->fecha_inicio,
                        'fecha_fin' => $proyecto->fecha_finalizacion,
                        'fase' => $this->faseStepperLabel($proceso),
                        'stepper' => $this->stepperEstados($proyecto->etapasParaStepper($proceso, $documento)),
                        'sort_date' => $proyecto->created_at,
                    ]);
                });
        }

        if ($userId) {
            PpsServicioSocial::query()
                ->where('created_by', $userId)
                ->get()
                ->each(function (PpsServicioSocial $registro) use ($rows): void {
                    $rows->push([
                        'kind' => 'pps',
                        'nombre' => $registro->nombre_estudiante ?: $registro->nombre_institucion,
                        'categoria' => 'PPS / Servicio Social',
                        'fecha_inicio' => $registro->created_at,
                        'fecha_fin' => null,
                        'fase' => 'Aprobación',
                        'stepper' => $this->stepperEstados($registro->stepperDeAprobacion()),
                        'sort_date' => $registro->created_at,
                    ]);
                });

            EnfAccion::query()
                ->where('creado_por_usuario_id', $userId)
                ->where(fn (Builder $query): Builder => $this->enfFormsQuery($query))
                ->get()
                ->each(function (EnfAccion $accion) use ($rows): void {
                    $rows->push([
                        'kind' => 'enf',
                        'nombre' => $accion->nombre_accion,
                        'categoria' => 'Educación no formal',
                        'fecha_inicio' => $accion->fecha_solicitud ?: $accion->created_at,
                        'fecha_fin' => null,
                        'fase' => 'Aprobación',
                        'stepper' => $this->enfStepper($accion),
                        'sort_date' => $accion->created_at,
                    ]);
                });
        }

        return $rows->sortByDesc('sort_date')->take(15)->values();
    }

    /**
     * Etiqueta legible de la fase que representa el stepper actual de un
     * Proyecto: aprobación inicial, informe intermedio o informe final. Solo
     * aplica a Proyecto — PPS/ENF tienen un único flujo (siempre "Aprobación").
     */
    private function faseStepperLabel(string $proceso): string
    {
        return match ($proceso) {
            Proyecto::FLUJO_INFORME_INTERMEDIO => 'Informe Intermedio',
            Proyecto::FLUJO_CIERRE_PROYECTO => 'Informe Final',
            default => 'Aprobación',
        };
    }

    /**
     * Convierte una colección [etapa, firma] (Proyecto::firmasParaFicha /
     * TieneFlujoPorEtapas::stepperDeAprobacion) en la forma plana que usa el
     * partial del stepper: ['nombre' => ..., 'estado' => aprobado|actual|pendiente|rechazado].
     */
    private function stepperEstados(Collection $filas): array
    {
        return ProyectoFlujoStepper::desdeFilas($filas);
    }

    private function enfStepper(EnfAccion $accion): array
    {
        $proceso = EnfRevision::where('enf_accion_id', $accion->id)
            ->orderByDesc('id')
            ->value('proceso');

        if (! $proceso) {
            return [];
        }

        $actualMarcado = false;

        return EnfRevision::where('enf_accion_id', $accion->id)
            ->where('proceso', $proceso)
            ->orderByDesc('revision_ciclo')
            ->orderByDesc('id')
            ->get()
            ->unique('flujo_aprobacion_etapa_id')
            ->sortBy('orden')
            ->values()
            ->map(function (EnfRevision $revision) use (&$actualMarcado): array {
                $estado = 'pendiente';

                if ($revision->estado === 'APROBADO') {
                    $estado = 'aprobado';
                } elseif ($revision->estado === 'SUBSANACION') {
                    $estado = 'rechazado';
                } elseif (! $actualMarcado) {
                    $estado = 'actual';
                    $actualMarcado = true;
                }

                return ['nombre' => $revision->etapa_nombre, 'estado' => $estado];
            })
            ->all();
    }

    /**
     * Obtiene las últimas actividades del sistema.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getLatestActivities($limit = 4)
    {
        return Activity::query()
            ->latest()
            ->limit($limit)
            ->get();
    }

/**
 * Obtiene los últimos cambios de estado de proyectos del usuario autenticado.
 *
 * @param int $limit
 * @return \Illuminate\Support\Collection
 */
public function getLatestActivitiesUser($limit = 3)
{
    $user = auth()->user();
    $empleadoId = $user?->empleado?->id;

    // Solo se muestran los formularios del usuario autenticado (proyectos donde participa).
    $proyectosIds = $empleadoId
        ? Proyecto::query()
            ->join('empleado_proyecto', 'empleado_proyecto.proyecto_id', '=', 'proyecto.id')
            ->where('empleado_proyecto.empleado_id', $empleadoId)
            ->pluck('proyecto.id')
            ->toArray()
        : [];


    // Obtener IDs de los documentos asociados a estos proyectos
    $documentosIds = DocumentoProyecto::whereIn('proyecto_id', $proyectosIds)
        ->pluck('id')
        ->toArray();
    
    // Obtener todos los estados asociados a los proyectos y sus documentos
    // (se excluye "Borrador": solo debe verse la actividad una vez enviado a revisión o subsanación)
    $actividadesProyecto = EstadoProyecto::where(function ($query) use ($proyectosIds, $documentosIds) {
            // Estados de los proyectos
            if (! empty($proyectosIds)) {
                $query->where(function ($q) use ($proyectosIds) {
                    $q->where('estadoable_type', Proyecto::class)
                      ->whereIn('estadoable_id', $proyectosIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }

            // Estados de los documentos (si existen)
            if (!empty($documentosIds)) {
                $query->orWhere(function ($q) use ($documentosIds) {
                    $q->where('estadoable_type', DocumentoProyecto::class)
                      ->whereIn('estadoable_id', $documentosIds);
                });
            }
        })
        ->whereHas('tipoestado', fn ($q) => $q->where('nombre', '!=', 'Borrador'))
        ->with(['tipoestado', 'estadoable']) // Cargar relaciones para evitar múltiples consultas
        ->orderByDesc('created_at')
        ->limit($limit)
        ->get()
        ->map(function ($estado) {
            // Añadir información útil para la vista
            $estado->fecha_cambio = $estado->created_at->format('d/m/Y H:i');
            $estado->sort_timestamp = $estado->created_at;
            
            // Determinar el nombre del elemento (proyecto o documento)
            if ($estado->estadoable_type === Proyecto::class) {
                $estado->nombre_elemento = $estado->estadoable->nombre_proyecto ?? 'Proyecto';
                $estado->tipo_elemento = 'Proyecto';
            } else {
                $estado->nombre_elemento = $estado->estadoable->nombre ?? 'Documento';
                $estado->tipo_elemento = 'Documento';
            }
            
            return $estado;
        });

    return $actividadesProyecto
        ->concat($this->enfActividadesUser($user, $limit))
        ->sortByDesc('sort_timestamp')
        ->take($limit)
        ->values();
}

    private function enfActividadesUser($user, int $limit): Collection
    {
        if (! $user) {
            return collect();
        }

        // Solo actividad de formularios propios que ya salieron de Borrador (enviados a
        // revisión/subsanación). Quien solo revisa o aprueba no debe verlos aquí.
        return EnfRevision::query()
            ->with('accion')
            ->whereHas('accion', fn (Builder $query): Builder => $this->enfFormsQuery($query)
                ->where('creado_por_usuario_id', $user->id)
                ->where('estado_flujo', '!=', 'BORRADOR'))
            ->latest()
            ->limit($limit * 2)
            ->get()
            ->map(function (EnfRevision $revision): object {
                $estado = new \stdClass();
                $estado->nombre = $this->enfEstadoLabel($revision->accion?->estado_flujo ?: $revision->estado);

                return (object) [
                    'es_actual' => in_array($revision->estado, ['ASIGNADO', 'EN_PROCESO', 'PENDIENTE'], true),
                    'tipo_elemento' => 'Proyecto',
                    'nombre_elemento' => $revision->accion?->nombre_accion ?: 'Educacion no formal',
                    'tipoestado' => $estado,
                    'comentario' => $this->enfActividadComentario($revision),
                    'fecha_cambio' => $revision->updated_at?->format('d/m/Y H:i') ?: $revision->created_at?->format('d/m/Y H:i'),
                    'sort_timestamp' => $revision->updated_at ?: $revision->created_at,
                ];
            });
    }

    private function enfActividadComentario(EnfRevision $revision): string
    {
        if (strtoupper((string) $revision->accion?->estado_flujo) === 'APROBADO') {
            return 'Todas las etapas del flujo de inscripcion ENF fueron aprobadas.';
        }

        return match ($revision->proceso) {
            EnfAccion::PROCESO_INFORME_INTERMEDIO => 'Informe intermedio enviado al flujo de revision.',
            EnfAccion::PROCESO_INFORME_FINAL => 'Informe final enviado al flujo de revision.',
            default => 'Formulario ENF enviado al flujo de revision.',
        };
    }

    private function enfAccionesUser(int $userId): Collection
    {
        return EnfAccion::query()
            ->where('creado_por_usuario_id', $userId)
            ->where(fn (Builder $query): Builder => $this->enfFormsQuery($query))
            ->get();
    }

    private function enfFormsQuery(Builder $query): Builder
    {
        return $query->whereIn('codigo_formulario', ['FORM-DVUS-016', 'FORM-DVUS-018']);
    }

    private function enfEstadoLabel(?string $estado): string
    {
        return match (strtoupper((string) $estado)) {
            'BORRADOR' => 'Borrador',
            'EN_REVISION' => 'En revision',
            'APROBADO' => 'En curso',
            'FINALIZADO' => 'Finalizado',
            'SUBSANACION', 'SUBSANACIÓN' => 'Subsanar',
            default => $estado ?: 'Educacion no formal',
        };
    }

    private function enfRevisionesDisponiblesQuery(): Builder
    {
        $user = Auth::user();
        $activeRoleName = $user?->activeRole?->name;

        if (! $user || ! $activeRoleName) {
            return EnfRevision::query()->whereRaw('1 = 0');
        }

        $pendingStates = ['PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO'];

        return EnfRevision::query()
            ->whereHas('accion', fn (Builder $query): Builder => $this->enfFormsQuery($query))
            ->whereIn('estado', $pendingStates)
            ->whereNotExists(function ($previousQuery) use ($pendingStates): void {
                $previousQuery
                    ->selectRaw('1')
                    ->from('enf_revisiones as enf_revisiones_anteriores')
                    ->whereColumn('enf_revisiones_anteriores.enf_accion_id', 'enf_revisiones.enf_accion_id')
                    ->whereColumn('enf_revisiones_anteriores.revision_ciclo', 'enf_revisiones.revision_ciclo')
                    ->whereColumn('enf_revisiones_anteriores.orden', '<', 'enf_revisiones.orden')
                    ->whereIn('enf_revisiones_anteriores.estado', $pendingStates);
            })
            ->whereNotExists(function ($newerCycleQuery): void {
                $newerCycleQuery
                    ->selectRaw('1')
                    ->from('enf_revisiones as enf_revisiones_ciclo_nuevo')
                    ->whereColumn('enf_revisiones_ciclo_nuevo.enf_accion_id', 'enf_revisiones.enf_accion_id')
                    ->whereColumn('enf_revisiones_ciclo_nuevo.revision_ciclo', '>', 'enf_revisiones.revision_ciclo');
            })
            ->where(function (Builder $responsableQuery) use ($user, $activeRoleName): void {
                $responsableQuery
                    ->where(function (Builder $assignedQuery) use ($user, $activeRoleName): void {
                        $assignedQuery
                            ->where('asignado_usuario_id', $user->id)
                            ->where(function (Builder $roleQuery) use ($activeRoleName): void {
                                $roleQuery
                                    ->whereNull('rol_requerido')
                                    ->orWhere('rol_requerido', $activeRoleName);
                            });
                    })
                    ->orWhere(function (Builder $roleQuery) use ($activeRoleName): void {
                        $roleQuery
                            ->whereNull('asignado_usuario_id')
                            ->where('rol_requerido', $activeRoleName);
                    })
                    ->orWhere(function (Builder $assignmentQuery) use ($user, $activeRoleName): void {
                        $assignmentQuery
                            ->where('responsable_usuario_id', $user->id)
                            ->where(function (Builder $roleQuery) use ($activeRoleName): void {
                                $roleQuery
                                    ->whereNull('rol_requerido')
                                    ->orWhere('rol_requerido', $activeRoleName);
                            });
                    });
            });
    }

    /**
     * Obtiene los proyectos según el nombre del estado y los pagina.
     *
     * @param string $stateName 
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getProjectsByState($stateName)
{
    // Obtiene el objeto TipoEstado según el nombre
    $tipoEstado = TipoEstado::where('nombre', $stateName)->first();
    if (!$tipoEstado) {
        // Crear un paginador vacío en lugar de intentar paginar una colección
        return new \Illuminate\Pagination\LengthAwarePaginator(
            [], // Array vacío de elementos
            0,  // Total de elementos (0 ya que no hay ninguno)
            $this->perPage, // Elementos por página
            1   // Página actual
=======
        $this->dispatch(
            'panel-grafico-actualizado',
            id: self::ID_GRAFICO,
            config: $panel->serieMensual($panel->ambito(), $this->mesesGrafico),
>>>>>>> efrain
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
        $porSubsanar = $mis->filter(
            fn (array $fila): bool => in_array(
                mb_strtolower(trim((string) ($fila['estado'] ?? ''))),
                ['subsanacion', 'subsanación', 'rechazado', 'subsanar documento'],
                true
            )
        )->values();

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
            'hayMasFormularios' => $mis->count() >= $this->formulariosVisibles,
        ]);
    }
}

<?php

namespace App\Services\Proyecto;

use App\Mail\EtapaFlujoPendiente;
use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Proyecto\CargoFirma;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Models\Proyecto\Proyecto;
use App\Models\User;
use App\Models\Workflow\LegacyWorkflowAdoption;
use App\Services\Workflow\WorkflowResumptionPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class ProyectoLegacyWorkflowAdoptionService
{
    public const MODO_BORRADOR = 'BORRADOR';

    public const ESTADOS_SIN_ENVIO = [
        'Borrador', 'Autoguardado', 'PendienteInformacion',
        'Pendiente informacion', 'Pendiente información', 'Pendiente_informacion',
        'Pendiente de informacion', 'Pendiente de información',
    ];

    public const MODO_EN_REVISION = 'EN_REVISION';

    public const MODO_SUBSANACION = 'SUBSANACION';

    public const MODO_COMPLETADO = 'COMPLETADO';

    public function __construct(private readonly WorkflowResumptionPolicy $resumptionPolicy) {}

    public function permiteAdaptacion(Proyecto $proyecto): bool
    {
        $estado = $proyecto->estado?->tipoestado?->nombre;

        return filled($estado) && $this->modoSugerido($estado) !== self::MODO_BORRADOR;
    }

    public function requiereAdopcion(Proyecto $proyecto): bool
    {
        if ($proyecto->adopcionFlujoLegacy()->exists() || $this->tieneRevisionesConfigurables($proyecto)) {
            return false;
        }

        $estado = $this->normalizar($proyecto->estado?->tipoestado?->nombre);

        return ! $proyecto->flujo_aprobacion_id
            || $proyecto->firma_proyecto()->whereNull('flujo_aprobacion_etapa_id')->exists()
            || ! in_array($estado, ['BORRADOR'], true);
    }

    /**
     * Devuelve solo datos serializables para que Livewire pueda presentar y
     * confirmar el puente de adopción sin guardar nada durante el diagnóstico.
     */
    public function diagnosticar(
        Proyecto $proyecto,
        FlujoAprobacion $flujo,
        ?int $etapaManualId = null,
        array $etapasRegularizadas = []
    ): array {
        $proyecto->loadMissing('adopcionFlujoLegacy');
        $flujo->loadMissing([
            'etapas.cargoFirma.tipoCargoFirma',
            'etapas.cargoFirma.estadoProyectoActual',
            'etapas.rolRevisor',
            'etapas.usuarioResponsable.empleado',
        ]);

        $estado = $proyecto->estado;
        $estadoNombre = $estado?->tipoestado?->nombre;
        $legacy = $this->firmasLegacy($proyecto);
        $legacyVigente = $legacy->whereIn('estado_revision', ['Aprobado', 'Pendiente', 'Rechazado'])
            ->sortByDesc('id')->unique(fn (FirmaProyecto $firma): string => $this->claveCargoFirma($firma).'|rol:'.$this->normalizar($firma->rol_requerido))->values();
        $etapas = $this->etapasInscripcion($flujo);
        $modoSugerido = $this->modoSugerido($estadoNombre);
        $modo = $modoSugerido;
        $cargoEstadoActual = $this->cargoEsperadoPorEstadoActual($proyecto);
        [$etapaSugerida, $razonEtapa] = $this->detectarEtapaActual($proyecto, $etapas, $legacyVigente, $modo);
        $etapaInicio = in_array($modo, [self::MODO_EN_REVISION, self::MODO_SUBSANACION], true)
            ? $etapaSugerida
            : null;
        $conciliacion = app(LegacyStageReconciler::class)->reconcile(
            $etapas, $legacy, fn (FirmaProyecto $firma, FlujoAprobacionEtapa $etapa): bool => $this->firmaCorrespondeEtapa($firma, $etapa)
        );
        $seleccionManual = $etapaManualId ? $etapas->firstWhere('id', $etapaManualId) : null;
        $bloqueos = collect($conciliacion['bloqueos']);
        if ($etapaManualId && (! $seleccionManual || ! in_array($modo, [self::MODO_EN_REVISION, self::MODO_SUBSANACION], true))) {
            $bloqueos->push('La etapa seleccionada no es una etapa activa de inscripción válida para esta adaptación.');
        }
        if ($seleccionManual && $conciliacion['completadas']->contains('id', $seleccionManual->id)) {
            $bloqueos->push('La etapa seleccionada ya tiene una aprobación comprobada y no debe repetirse.');
        }

        $regularizadas = collect($etapasRegularizadas)->map(fn ($id): int => (int) $id)->unique();
        if ($regularizadas->count() !== count($etapasRegularizadas)) {
            $bloqueos->push('No puede registrar más de una firma histórica para la misma etapa.');
        }
        foreach ($regularizadas as $id) {
            $etapa = $etapas->firstWhere('id', $id);
            $estadoFirma = $conciliacion['clasificacion']->get($id)['estado'] ?? null;
            if (! $seleccionManual || ! $etapa || $etapa->tipo_etapa !== 'APROBACION'
                || (int) $etapa->orden >= (int) $seleccionManual->orden
                || in_array($estadoFirma, ['Aprobado', 'Rechazado'], true)) {
                $bloqueos->push('Solo se pueden regularizar etapas de aprobación anteriores a la etapa de retorno, sin aprobación conservada ni rechazo vigente.');
            }
        }
        $conciliacion['pendientes'] = $conciliacion['pendientes']->reject(fn ($etapa) => $regularizadas->contains((int) $etapa->id))->values();

        if (! $this->permiteAdaptacion($proyecto)) {
            $bloqueos->push('No se pueden adaptar proyectos que no han sido enviados a revisión. El borrador utilizará el flujo configurado al enviarse.');
        }

        if ($proyecto->adopcionFlujoLegacy) {
            $bloqueos->push('Este proyecto ya fue adoptado por un flujo configurable.');
        }

        if ($this->tieneRevisionesConfigurables($proyecto)) {
            $bloqueos->push('El proyecto ya tiene un ciclo de revisión configurable y no se puede adoptar nuevamente.');
        }

        if ($flujo->proceso !== 'PROYECTO' || ! $flujo->activo) {
            $bloqueos->push('El flujo seleccionado no es un flujo activo de proyectos.');
        }

        if ($flujo->tipo_accion_id && $proyecto->tipo_accion_id
            && (int) $flujo->tipo_accion_id !== (int) $proyecto->tipo_accion_id
        ) {
            $bloqueos->push('El flujo seleccionado pertenece a otro tipo de acción.');
        }

        $codigoFormulario = $proyecto->codigoFormularioFlujo();

        if ($flujo->codigo_formulario && $codigoFormulario
            && $flujo->codigo_formulario !== $codigoFormulario
        ) {
            $bloqueos->push('El flujo seleccionado pertenece a otro formulario.');
        }

        if ($etapas->isEmpty()) {
            $bloqueos->push('El flujo no tiene etapas activas de inscripción con cargo de firma.');
        }

        if (in_array($modo, [self::MODO_EN_REVISION, self::MODO_SUBSANACION], true) && ! $etapaInicio && ! $seleccionManual) {
            $bloqueos->push('El sistema no pudo identificar automáticamente una etapa única. Revise el historial legacy y la configuración del flujo.');
        }

        if ($modo === self::MODO_BORRADOR
            && $legacy->contains(fn (FirmaProyecto $firma): bool => in_array($firma->estado_revision, ['Pendiente', 'Rechazado'], true))
        ) {
            $bloqueos->push('El proyecto tiene revisiones legacy activas; no es seguro adoptarlo como borrador.');
        }

        if ($modo === self::MODO_SUBSANACION && $legacyVigente->where('estado_revision', 'Rechazado')->count() > 1) {
            $bloqueos->push('El historial legacy contiene más de una revisión rechazada; debe depurarse antes de adoptar el proyecto.');
        }

        $etapasDesdeInicio = in_array($modo, [self::MODO_EN_REVISION, self::MODO_SUBSANACION], true)
            ? $conciliacion['pendientes'] : collect();

        if ($legacy->contains(fn (FirmaProyecto $firma): bool => ! $firma->esFirmaLegacy())) {
            $bloqueos->push('Existen firmas de un flujo cuya etapa ya no está disponible. Revise el historial antes de adaptar nuevamente el proyecto.');
        }

        if (! $seleccionManual && $etapaSugerida && $conciliacion['completadas']->contains('id', $etapaSugerida->id)) {
            $bloqueos->push('La etapa detectada por el estado repetiría una aprobación histórica. Revise la correspondencia entre el estado y las etapas antes de adoptar.');
        }
        if ($modo === self::MODO_EN_REVISION && $legacyVigente->contains('estado_revision', 'Rechazado')) {
            $bloqueos->push('El historial contiene un rechazo pero el estado no indica subsanación. Revise el estado de origen.');
        }

        // El estado de origen se conserva por separado del primer trabajo pendiente del nuevo orden.
        $etapaInicio = $etapaSugerida ? $etapasDesdeInicio->first() : null;
        if ($modo !== self::MODO_SUBSANACION && $etapaInicio && (int) $etapaInicio->id !== (int) $etapaSugerida->id) {
            $razonEtapa .= ' El nuevo recorrido comienza en "'.$etapaInicio->nombre.'" porque no tiene una aprobación vigente comprobada.';
        }

        $priorizarRetorno = $modo === self::MODO_SUBSANACION && $etapaSugerida
            && $conciliacion['pendientes']->contains('id', $etapaSugerida->id)
            && (int) $etapasDesdeInicio->first()?->id !== (int) $etapaSugerida->id;
        if ($priorizarRetorno) {
            $etapaInicio = $etapaSugerida;
            $etapasDesdeInicio = collect([$etapaSugerida])->concat(
                $etapasDesdeInicio->reject(fn ($etapa) => (int) $etapa->id === (int) $etapaSugerida->id)
            )->values();
            $razonEtapa .= ' Al reenviar, esta etapa recibirá primero; las demás revisiones pendientes se conservarán después.';
        }
        $inicioAutomaticoId = $etapaInicio?->id;
        if ($seleccionManual) {
            $etapaInicio = $seleccionManual;
            $etapasDesdeInicio = collect([$seleccionManual])->concat(
                $conciliacion['pendientes']->reject(fn ($etapa) => (int) $etapa->id === (int) $seleccionManual->id)
            )->values();
        }

        $etapasUi = $etapas->map(function (FlujoAprobacionEtapa $etapa) use (
            $legacy,
            $etapaInicio,
            $etapaSugerida,
            $etapasDesdeInicio,
            $modo,
            $bloqueos,
            $conciliacion,
            $regularizadas
        ): array {
            $estaEnNuevoRecorrido = $etapasDesdeInicio->contains(fn (FlujoAprobacionEtapa $item): bool => (int) $item->id === (int) $etapa->id);
            $candidatos = $estaEnNuevoRecorrido ? $this->candidatosParaEtapa($etapa) : collect();
            $propuesto = $estaEnNuevoRecorrido
                ? $this->revisorPropuesto($etapa, $legacy, $modo, (int) $etapaSugerida?->id === (int) $etapa->id)
                : null;

            if ($estaEnNuevoRecorrido && $etapa->responsableFijoPendiente()) {
                $bloqueos->push(sprintf('Configure el responsable fijo de la etapa "%s" en Configuración → Flujos antes de adaptar.', $etapa->nombre));
            } elseif ($estaEnNuevoRecorrido && $candidatos->isEmpty()) {
                $bloqueos->push(sprintf(
                    'La etapa "%s" no tiene usuarios elegibles con empleado activo, rol y correo válido.',
                    $etapa->nombre
                ));
            }

            return [
                'id' => (int) $etapa->id,
                'orden' => (int) $etapa->orden,
                'codigo' => $etapa->codigo,
                'nombre' => $etapa->nombre,
                'rol' => $etapa->rolRevisor?->name,
                'cargo' => $etapa->cargoFirma?->tipoCargoFirma?->nombre,
                'estado' => $etapa->cargoFirma?->estadoProyectoActual?->nombre,
                'es_inicio' => (int) $etapaInicio?->id === (int) $etapa->id,
                'es_anterior' => $conciliacion['clasificacion']->get((int) $etapa->id)['completada'],
                'regularizacion_solicitada' => $regularizadas->contains((int) $etapa->id),
                'tipo_etapa' => $etapa->tipo_etapa,
                'evidencia_legacy' => $conciliacion['clasificacion']->get((int) $etapa->id),
                'en_nuevo_recorrido' => $estaEnNuevoRecorrido,
                'responsable_fijo' => ! $etapa->emisor_define_destinatario && ($etapa->requiere_asignacion || filled($etapa->usuario_responsable_id)),
                'propuesto_usuario_id' => $propuesto?->id,
                'propuesto' => $propuesto ? [
                    'id' => (int) $propuesto->id,
                    'nombre' => $propuesto->empleado?->nombre_completo ?: $propuesto->name,
                    'email' => $propuesto->email,
                ] : null,
                'candidatos' => $candidatos->map(fn (User $user): array => [
                    'id' => (int) $user->id,
                    'nombre' => $user->empleado?->nombre_completo ?: $user->name,
                    'email' => $user->email,
                ])->all(),
            ];
        })->all();

        return [
            'requiere_adopcion' => $this->requiereAdopcion($proyecto),
            'estado_id' => $estado?->tipo_estado_id,
            'estado' => $estadoNombre ?: 'Sin estado',
            'cargo_estado_actual' => $cargoEstadoActual,
            'estado_comentario' => trim((string) $estado?->comentario),
            'modo_sugerido' => $modoSugerido,
            'modo' => $modo,
            'etapa_sugerida_id' => $etapaSugerida?->id,
            'etapa_origen' => $etapaSugerida?->nombre,
            'etapa_inicio_id' => $etapaInicio?->id,
            'inicio_automatico_id' => $inicioAutomaticoId,
            'seleccion_manual' => (bool) $seleccionManual,
            'orden_ejecucion_propio' => (bool) $seleccionManual || (bool) $priorizarRetorno,
            'recorrido_ids' => $etapasDesdeInicio->pluck('id')->all(),
            'razon_etapa' => $razonEtapa,
            'etapas' => $etapasUi,
            'bloqueos' => $bloqueos->unique()->values()->all(),
            'legacy_pendientes' => $legacy->where('estado_revision', 'Pendiente')->count(),
            'legacy_rechazadas' => $legacy->where('estado_revision', 'Rechazado')->count(),
        ];
    }

    public function adoptar(
        Proyecto $proyecto,
        FlujoAprobacion $flujo,
        string $modo,
        ?int $etapaInicioId,
        array $revisoresPorEtapa,
        User $actor,
        ?string $motivoSubsanacion = null,
        ?string $motivoEtapaManual = null,
        array $firmasHistoricas = []
    ): LegacyWorkflowAdoption {
        if (! $this->modoValido($modo)) {
            throw new \RuntimeException('El modo de adopción indicado no es válido.');
        }

        $resultado = DB::transaction(function () use (
            $proyecto,
            $flujo,
            $modo,
            $etapaInicioId,
            $revisoresPorEtapa,
            $actor,
            $motivoSubsanacion,
            $motivoEtapaManual,
            $firmasHistoricas
        ): array {
            $proyectoBloqueado = Proyecto::query()
                ->whereKey($proyecto->id)
                ->lockForUpdate()
                ->firstOrFail();
            $flujoBloqueado = FlujoAprobacion::query()
                ->whereKey($flujo->id)
                ->lockForUpdate()
                ->firstOrFail();

            $proyectoBloqueado->firma_proyecto()->lockForUpdate()->get();

            if ($proyectoBloqueado->adopcionFlujoLegacy()->exists()) {
                throw new \RuntimeException('Este proyecto ya fue adoptado por un flujo configurable.');
            }

            if ($this->tieneRevisionesConfigurables($proyectoBloqueado)) {
                throw new \RuntimeException('El proyecto ya tiene un ciclo de revisión configurable.');
            }

            $manual = $motivoEtapaManual !== null;
            if ($manual && (! $actor->hasRole(['admin', 'Director/Enlace']) || ! $actor->can('proyectos.historial'))) {
                throw new \RuntimeException('No tiene autorización para seleccionar manualmente la etapa de adaptación.');
            }
            if ($manual && (mb_strlen($motivoEtapaManual) > 2000 || ! $etapaInicioId)) {
                throw new \RuntimeException('Seleccione una etapa válida. Si proporciona una justificación, no debe superar 2000 caracteres.');
            }
            if ($firmasHistoricas !== [] && (! $manual || ! $actor->hasRole('admin') || ! $actor->can('proyectos.historial'))) {
                throw new \RuntimeException('Solo un administrador puede regularizar firmas históricas durante la selección manual de etapa.');
            }
            $diagnostico = $this->diagnosticar($proyectoBloqueado, $flujoBloqueado, $manual ? $etapaInicioId : null, array_keys($firmasHistoricas));

            if ($diagnostico['bloqueos'] !== []) {
                throw new \RuntimeException(implode(' ', $diagnostico['bloqueos']));
            }

            $modoDetectado = (string) $diagnostico['modo'];
            $etapaDetectadaId = $diagnostico['etapa_inicio_id'] ? (int) $diagnostico['etapa_inicio_id'] : null;

            if ($modo !== $modoDetectado) {
                throw new \RuntimeException('La situación del proyecto cambió o no coincide con el diagnóstico automático. Actualice el expediente antes de adoptar.');
            }

            if (in_array($modoDetectado, [self::MODO_EN_REVISION, self::MODO_SUBSANACION], true)
                && (int) $etapaInicioId !== (int) $etapaDetectadaId
            ) {
                throw new \RuntimeException('La etapa indicada no coincide con la etapa detectada automáticamente por el sistema.');
            }

            $modo = $modoDetectado;
            $etapaInicioId = $etapaDetectadaId;

            $etapas = $this->etapasInscripcion($flujoBloqueado);
            $etapaInicio = $etapas->firstWhere('id', $etapaInicioId);
            $etapasNuevoRecorrido = $etapaInicio
                ? collect($diagnostico['recorrido_ids'])->map(fn ($id) => $etapas->firstWhere('id', $id))->values()
                : collect();
            $asignaciones = $this->validarRevisores(
                $etapasNuevoRecorrido,
                $revisoresPorEtapa,
                in_array($modo, [self::MODO_EN_REVISION, self::MODO_SUBSANACION], true)
            );
            $regularizaciones = $this->validarFirmasHistoricas($firmasHistoricas, $etapas)
                ->sortBy(fn ($datos, $id) => $etapas->firstWhere('id', $id)->orden);
            $estadoOrigen = $proyectoBloqueado->estado;
            $historialOrigen = $proyectoBloqueado->estado_proyecto()->orderBy('id')->get(['id', 'tipo_estado_id', 'comentario', 'fecha', 'es_actual'])->toArray();
            $legacy = $this->firmasLegacy($proyectoBloqueado);
            $estadoNormalizadoId = null;

            if ($modo === self::MODO_EN_REVISION && $etapaInicio) {
                $estadoNormalizadoId = $this->asegurarEstadoDeEtapa(
                    $proyectoBloqueado,
                    $etapaInicio,
                    $actor,
                    $estadoOrigen?->tipoestado?->nombre
                );
            }

            if ($modo === self::MODO_SUBSANACION) {
                $motivoSubsanacion = trim((string) ($estadoOrigen?->comentario ?: $motivoSubsanacion));

                $estadoNormalizadoId = $this->asegurarEstadoSubsanacion(
                    $proyectoBloqueado,
                    $actor,
                    $motivoSubsanacion
                );
            }

            $proyectoBloqueado->forceFill(['flujo_aprobacion_id' => $flujoBloqueado->id])->saveQuietly();

            $firmasCreadas = collect();

            if (in_array($modo, [self::MODO_EN_REVISION, self::MODO_SUBSANACION], true)) {
                $firmasCreadas = $etapasNuevoRecorrido->map(function (FlujoAprobacionEtapa $etapa, int $indice) use (
                    $proyectoBloqueado,
                    $flujoBloqueado,
                    $asignaciones,
                    $modo,
                    $diagnostico,
                    $regularizaciones
                ): FirmaProyecto {
                    $usuario = $asignaciones->get((int) $etapa->id);

                    return $proyectoBloqueado->firma_proyecto()->create([
                        'empleado_id' => $usuario->empleado->id,
                        'cargo_firma_id' => $etapa->cargo_firma_id,
                        'flujo_aprobacion_id' => $flujoBloqueado->id,
                        'flujo_aprobacion_etapa_id' => $etapa->id,
                        'orden_revision' => $diagnostico['orden_ejecucion_propio'] ? $regularizaciones->count() + $indice + 1 : $etapa->orden,
                        'etapa_codigo' => $etapa->codigo,
                        'etapa_nombre' => $etapa->nombre,
                        'rol_requerido' => $etapa->rolRevisor?->name,
                        'responsable_usuario_id' => $usuario->id,
                        'requiere_asignacion' => (bool) $etapa->requiere_asignacion,
                        'revision_ciclo' => 1,
                        'estado_revision' => $modo === self::MODO_SUBSANACION && (int) $etapa->id === (int) ($diagnostico['seleccion_manual'] ? $diagnostico['etapa_inicio_id'] : $diagnostico['etapa_sugerida_id'])
                            ? 'Rechazado'
                            : 'Pendiente',
                        'firma_id' => null,
                        'sello_id' => null,
                        'fecha_firma' => null,
                        'hash' => (string) Str::uuid(),
                    ]);
                })->values();
            }

            $firmasRegularizadas = collect();
            foreach ($regularizaciones as $etapaId => $datos) {
                $etapa = $etapas->firstWhere('id', $etapaId);
                $empleado = $datos['empleado'];
                $firma = $proyectoBloqueado->firma_proyecto()->create([
                    'empleado_id' => $empleado->id,
                    'cargo_firma_id' => $etapa->cargo_firma_id,
                    'flujo_aprobacion_id' => $flujoBloqueado->id,
                    'flujo_aprobacion_etapa_id' => $etapa->id,
                    'orden_revision' => $firmasRegularizadas->count() + 1,
                    'etapa_codigo' => $etapa->codigo,
                    'etapa_nombre' => $etapa->nombre,
                    'rol_requerido' => $etapa->rolRevisor?->name,
                    'responsable_usuario_id' => $empleado->user_id,
                    'requiere_asignacion' => true,
                    'revision_ciclo' => 1,
                    'estado_revision' => 'Aprobado',
                    'firma_id' => $datos['firma']->id,
                    'sello_id' => null,
                    'fecha_firma' => $datos['fecha'],
                    'hash' => (string) Str::uuid(),
                ]);
                $firmasRegularizadas->push([
                    'firma_proyecto_id' => $firma->id,
                    'etapa_id' => $etapa->id,
                    'empleado_id' => $empleado->id,
                    'nombre_firmante' => $empleado->nombre_completo,
                    'firma_id' => $datos['firma']->id,
                    'fecha_historica' => $datos['fecha'],
                    'registrada_por' => $actor->id,
                    'registrada_en' => now()->toIso8601String(),
                    'motivo' => trim($motivoEtapaManual),
                ]);
            }

            $legacyPendientes = $legacy->where('estado_revision', 'Pendiente')->pluck('id')->values();

            if ($modo !== self::MODO_BORRADOR && $legacyPendientes->isNotEmpty()) {
                FirmaProyecto::query()
                    ->whereIn('id', $legacyPendientes)
                    ->whereNull('flujo_aprobacion_etapa_id')
                    ->where('estado_revision', 'Pendiente')
                    ->update(['estado_revision' => 'Anulado']);
            }

            $primeraFirma = $firmasCreadas->first();
            $revisorActual = $primeraFirma
                ? $asignaciones->get((int) $primeraFirma->flujo_aprobacion_etapa_id)
                : null;
            $etapasAnteriores = $etapas->whereIn('id', collect($diagnostico['etapas'])->where('es_anterior', true)->pluck('id'));

            $adopcion = $proyectoBloqueado->adopcionFlujoLegacy()->create([
                'flujo_aprobacion_id' => $flujoBloqueado->id,
                'etapa_inicio_id' => $etapaInicio?->id,
                'orden_inicio' => $etapaInicio?->orden,
                'proceso' => Proyecto::FLUJO_INSCRIPCION,
                'modo' => $modo,
                'estado_origen_id' => $estadoOrigen?->tipo_estado_id,
                'estado_origen' => $estadoOrigen?->tipoestado?->nombre,
                'revisor_usuario_id' => $revisorActual?->id,
                'adoptado_por_usuario_id' => $actor->id,
                'adoptado_en' => now(),
                'evidencia' => [
                    'version' => 3,
                    'firmas_regularizadas' => $firmasRegularizadas->all(),
                    'seleccion_etapa' => [
                        'manual' => $manual,
                        'sugerida_id' => $diagnostico['inicio_automatico_id'],
                        'elegida_id' => $etapaInicioId,
                        'motivo' => $manual ? trim($motivoEtapaManual) : null,
                        'usuario_id' => $actor->id,
                    ],
                    'conciliacion' => collect($diagnostico['etapas'])->map(fn (array $etapa): array => [
                        'etapa_id' => $etapa['id'],
                        'nombre' => $etapa['nombre'],
                        'evidencia' => $etapa['evidencia_legacy'],
                        'en_nuevo_recorrido' => $etapa['en_nuevo_recorrido'],
                    ])->all(),
                    'historial_estados' => $historialOrigen,
                    'diagnostico' => [
                        'estado_id' => $estadoOrigen?->tipo_estado_id,
                        'estado' => $estadoOrigen?->tipoestado?->nombre,
                        'comentario' => $estadoOrigen?->comentario,
                        'razon_etapa' => $diagnostico['razon_etapa'],
                        'etapa_origen_id' => $diagnostico['etapa_sugerida_id'],
                    ],
                    'etapas_anteriores' => $etapasAnteriores->map(fn (FlujoAprobacionEtapa $etapa): array => $this->snapshotEtapa($etapa))->values()->all(),
                    'nuevo_recorrido' => $etapasNuevoRecorrido->map(fn (FlujoAprobacionEtapa $etapa): array => $this->snapshotEtapa($etapa))->values()->all(),
                    'revisores' => $asignaciones->map(fn (User $user): int => (int) $user->id)->all(),
                    'firmas_legacy' => $legacy->map(fn (FirmaProyecto $firma): array => [
                        'id' => (int) $firma->id,
                        'cargo_firma_id' => (int) $firma->cargo_firma_id,
                        'empleado_id' => (int) $firma->empleado_id,
                        'estado' => $firma->estado_revision,
                        'rol_requerido' => $firma->rol_requerido,
                        'firma_id' => $firma->firma_id,
                        'sello_id' => $firma->sello_id,
                        'fecha_firma' => $firma->fecha_firma,
                        'hash' => $firma->hash,
                    ])->values()->all(),
                    'firmas_legacy_anuladas' => $legacyPendientes->all(),
                    'firmas_configurables_creadas' => $firmasCreadas->pluck('id')->concat($firmasRegularizadas->pluck('firma_proyecto_id'))->all(),
                    'estado_normalizado_id' => $estadoNormalizadoId,
                    'motivo_subsanacion' => $modo === self::MODO_SUBSANACION ? $motivoSubsanacion : null,
                ],
            ]);

            return [
                'adopcion' => $adopcion,
                'notificar_usuario_id' => $modo === self::MODO_EN_REVISION ? $revisorActual?->id : null,
                'notificar_etapa_id' => $modo === self::MODO_EN_REVISION ? $etapaInicio?->id : null,
            ];
        }, 3);

        /** @var LegacyWorkflowAdoption $adopcion */
        $adopcion = $resultado['adopcion'];

        Log::info('Proyecto legacy adoptado al flujo configurable', [
            'proceso' => Proyecto::FLUJO_INSCRIPCION,
            'proyecto_id' => $proyecto->id,
            'flujo_id' => $adopcion->flujo_aprobacion_id,
            'ciclo_anterior' => null,
            'ciclo_nuevo' => in_array($adopcion->modo, [self::MODO_EN_REVISION, self::MODO_SUBSANACION], true) ? 1 : null,
            'modo' => $adopcion->modo,
            'etapa_inicio_id' => $adopcion->etapa_inicio_id,
            'revisor_usuario_id' => $adopcion->revisor_usuario_id,
            'adoptado_por_usuario_id' => $actor->id,
        ]);

        $this->notificarRevisionAdoptada(
            $proyecto->fresh(),
            $resultado['notificar_usuario_id'],
            $resultado['notificar_etapa_id']
        );

        return $adopcion->fresh();
    }

    public function asignarFlujoSinAdopcion(Proyecto $proyecto, FlujoAprobacion $flujo, User $actor): Proyecto
    {
        $actualizado = DB::transaction(function () use ($proyecto, $flujo): Proyecto {
            $proyectoBloqueado = Proyecto::query()->whereKey($proyecto->id)->lockForUpdate()->firstOrFail();

            if (! $this->permiteAdaptacion($proyectoBloqueado)) {
                throw new \RuntimeException('No se pueden adaptar proyectos que no han sido enviados a revisión.');
            }

            if ($this->tieneRevisionesConfigurables($proyectoBloqueado)) {
                throw new \RuntimeException('No se puede cambiar el flujo porque el proyecto ya inició una revisión.');
            }

            if ($proyectoBloqueado->adopcionFlujoLegacy()->exists()) {
                throw new \RuntimeException('No se puede cambiar el flujo de un proyecto legacy ya adoptado.');
            }

            $diagnostico = $this->diagnosticar($proyectoBloqueado, $flujo);
            $bloqueosFlujo = collect($diagnostico['bloqueos'])
                ->reject(fn (string $mensaje): bool => str_contains($mensaje, 'revisiones legacy activas'));

            if ($bloqueosFlujo->isNotEmpty()) {
                throw new \RuntimeException($bloqueosFlujo->implode(' '));
            }

            $proyectoBloqueado->forceFill(['flujo_aprobacion_id' => $flujo->id])->saveQuietly();

            return $proyectoBloqueado->fresh();
        }, 3);

        Log::info('Flujo configurable asignado a proyecto sin iniciar ciclo', [
            'proyecto_id' => $proyecto->id,
            'flujo_id' => $flujo->id,
            'usuario_id' => $actor->id,
        ]);

        return $actualizado;
    }

    public function modos(): array
    {
        return [
            self::MODO_BORRADOR => 'Borrador o autoguardado',
            self::MODO_EN_REVISION => 'En revisión',
            self::MODO_SUBSANACION => 'En subsanación',
            self::MODO_COMPLETADO => 'Aprobado o completado antes del sistema',
        ];
    }

    private function validarFirmasHistoricas(array $firmas, Collection $etapas): Collection
    {
        return collect($firmas)->map(function ($datos, $etapaId) use ($etapas): array {
            if (! is_array($datos)) {
                throw new \RuntimeException('Los datos de la firma histórica no son válidos.');
            }
            $empleado = \App\Models\Personal\Empleado::with(['firma', 'user'])->find((int) ($datos['empleado_id'] ?? 0));
            $rol = $etapas->firstWhere('id', $etapaId)?->rolRevisor?->name;
            if (! $rol || ! $empleado?->user || ! $empleado->user->hasRole($rol)) {
                throw new \RuntimeException('El firmante histórico debe tener el rol configurado para esa etapa.');
            }
            $firma = $empleado?->firma;
            $imagen = $firma ? \App\Support\Fichas\FirmaImagen::resolver($firma->ruta_storage, true) : null;
            if (! $empleado || ! $firma || empty($imagen['path']) || ! @getimagesize($imagen['path'])) {
                throw new \RuntimeException('Cada firmante histórico debe tener una firma guardada válida y disponible para el PDF.');
            }
            $fecha = filled($datos['fecha'] ?? null) ? $datos['fecha'] : null;
            if (\Illuminate\Support\Facades\Validator::make(['fecha' => $fecha], ['fecha' => 'nullable|date_format:Y-m-d|before_or_equal:today'])->fails()) {
                throw new \RuntimeException('La fecha histórica debe ser válida y no puede estar en el futuro.');
            }
            return ['empleado' => $empleado, 'firma' => $firma, 'fecha' => $fecha];
        });
    }

    private function validarRevisores(Collection $etapas, array $revisoresPorEtapa, bool $sonRequeridos): Collection
    {
        if (! $sonRequeridos) {
            return collect();
        }

        $etapaIds = $etapas->pluck('id')->map(fn ($id): int => (int) $id)->values();
        $asignaciones = collect($revisoresPorEtapa)
            ->filter(fn ($usuarioId): bool => filled($usuarioId))
            ->mapWithKeys(fn ($usuarioId, $etapaId): array => [(int) $etapaId => (int) $usuarioId]);

        if ($asignaciones->keys()->contains(fn (int $etapaId): bool => ! $etapaIds->contains($etapaId))) {
            throw new \RuntimeException('Se indicó un revisor para una etapa que no pertenece al recorrido adoptado.');
        }

        $usuarios = collect();

        foreach ($etapas as $etapa) {
            if ($etapa->responsableFijoPendiente()) {
                throw new \RuntimeException(sprintf('Configure el responsable fijo de la etapa "%s" antes de adaptar.', $etapa->nombre));
            }
            $rol = $etapa->rolRevisor?->name;

            if (! $etapa->emisor_define_destinatario && $etapa->usuario_responsable_id) {
                $usuarioConfigurado = User::withTrashed()
                    ->with('empleado')
                    ->find((int) $etapa->usuario_responsable_id);
                $elegible = $this->resumptionPolicy->eligibleRecipient($usuarioConfigurado, $rol, true);

                if (! $elegible) {
                    throw new \RuntimeException(sprintf(
                        'El responsable fijo configurado para "%s" no tiene cuenta, empleado, rol o correo válido.',
                        $etapa->nombre
                    ));
                }

                $usuarios->put((int) $etapa->id, $elegible);

                continue;
            }

            $usuarioId = $asignaciones->get((int) $etapa->id);

            if (! $usuarioId) {
                throw new \RuntimeException(sprintf('Debe seleccionar el revisor de la etapa "%s".', $etapa->nombre));
            }

            $usuario = User::withTrashed()->with('empleado')->find($usuarioId);
            $elegible = $this->resumptionPolicy->eligibleRecipient($usuario, $rol, true);

            if (! $elegible) {
                throw new \RuntimeException(sprintf(
                    'El revisor seleccionado para "%s" no tiene cuenta, empleado, rol o correo válido.',
                    $etapa->nombre
                ));
            }

            $usuarios->put((int) $etapa->id, $elegible);
        }

        return $usuarios;
    }

    private function asegurarEstadoDeEtapa(
        Proyecto $proyecto,
        FlujoAprobacionEtapa $etapa,
        User $actor,
        ?string $estadoOrigen
    ): ?int {
        $tipoEstadoId = \App\Support\Proyecto\EstadoGeneralProyecto::id('En revision');

        if ((int) $proyecto->estado?->tipo_estado_id === (int) $tipoEstadoId) {
            return null;
        }

        $empleadoId = $actor->empleado?->id;

        if (! $empleadoId) {
            throw new \RuntimeException('Para normalizar el estado legacy, la cuenta administradora debe tener un empleado activo asociado.');
        }

        return $this->crearEstadoSinNotificacion($proyecto, (int) $tipoEstadoId, (int) $empleadoId, sprintf(
            'Adopción legacy: el estado "%s" se ubicó en la etapa "%s".',
            $estadoOrigen ?: 'sin estado',
            $etapa->nombre
        ));
    }

    private function asegurarEstadoSubsanacion(Proyecto $proyecto, User $actor, string $motivo): ?int
    {
        $estado = $proyecto->estado;

        if ($this->normalizar($estado?->tipoestado?->nombre) === self::MODO_SUBSANACION
            && trim((string) $estado?->comentario) !== ''
        ) {
            return null;
        }

        $tipoEstadoId = TipoEstado::query()->where('nombre', 'Subsanacion')->value('id');
        $empleadoId = $actor->empleado?->id;

        if (! $tipoEstadoId) {
            throw new \RuntimeException('No existe el estado Subsanacion requerido por el motor.');
        }

        if (! $empleadoId) {
            throw new \RuntimeException('Para normalizar la subsanación legacy, la cuenta administradora debe tener un empleado activo asociado.');
        }

        return $this->crearEstadoSinNotificacion(
            $proyecto,
            (int) $tipoEstadoId,
            (int) $empleadoId,
            $motivo
        );
    }

    private function crearEstadoSinNotificacion(
        Proyecto $proyecto,
        int $tipoEstadoId,
        int $empleadoId,
        string $comentario
    ): int {
        $proyecto->estado_proyecto()->update(['es_actual' => false]);

        $estado = EstadoProyecto::withoutEvents(fn (): EstadoProyecto => $proyecto->estado_proyecto()->create([
            'empleado_id' => $empleadoId,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'comentario' => $comentario,
            'es_actual' => true,
        ]));

        $proyecto->unsetRelation('estado');

        return (int) $estado->id;
    }

    private function detectarEtapaActual(
        Proyecto $proyecto,
        Collection $etapas,
        Collection $legacy,
        string $modo
    ): array {
        if (! in_array($modo, [self::MODO_EN_REVISION, self::MODO_SUBSANACION], true)) {
            return [null, 'El estado seleccionado no necesita crear un ciclo de revisión.'];
        }

        if ($modo === self::MODO_SUBSANACION) {
            $rechazadas = $legacy->where('estado_revision', 'Rechazado');
            $coincidencias = $etapas->filter(fn (FlujoAprobacionEtapa $etapa): bool => $rechazadas
                ->contains(fn (FirmaProyecto $firma): bool => $this->firmaCorrespondeEtapa($firma, $etapa)));

            if ($coincidencias->count() === 1) {
                return [$coincidencias->first(), 'Se identificó por la firma legacy que solicitó la subsanación.'];
            }

            if ($rechazadas->isNotEmpty()) {
                return [null, 'La etapa que solicitó la subsanación no tiene una correspondencia única en el flujo seleccionado.'];
            }

            // Solo interpretar el estado previo de este episodio; no retroceder a otro ciclo.
            $anterior = $proyecto->estado_proyecto()
                ->where('id', '<', $proyecto->estado?->id)
                ->whereHas('tipoestado', fn ($q) => $q->whereNotIn('nombre', ['Subsanacion', 'Subsanación']))
                ->with('tipoestado')->latest('id')->first();
            if (! $anterior) {
                return [null, 'No existe un estado anterior que identifique el origen de la subsanación.'];
            }

            $estadoAnterior = $anterior->tipoestado?->nombre;
            $cargoLegacy = collect(config('nexo.firmas_cargos.revisores_documento_proyecto', []))
                ->first(fn (array $regla): bool => $this->normalizar($regla['estado'] ?? '') === $this->normalizar($estadoAnterior));
            if ($cargoLegacy) {
                $rolEsperado = $this->normalizar($cargoLegacy['cargo']);
                $coincidencias = $etapas->filter(fn (FlujoAprobacionEtapa $etapa): bool =>
                    $this->normalizar($etapa->rolRevisor?->name) === $rolEsperado);
                // Compatibilidad con etapas antiguas sin rol explícito: usar el cargo semántico.
                if ($coincidencias->isEmpty()) {
                    $coincidencias = $etapas->filter(fn (FlujoAprobacionEtapa $etapa): bool =>
                        blank($etapa->rol_revisor_id) && $this->normalizar($etapa->cargoFirma?->tipoCargoFirma?->nombre) === $rolEsperado);
                }
            } else {
                $coincidencias = $etapas->where('cargoFirma.tipo_estado_id', $anterior->tipo_estado_id);
            }
            if ($coincidencias->count() === 1) {
                return [$coincidencias->first(), sprintf('El estado anterior "%s" (registro #%d) identifica la etapa de retorno "%s" según el recorrido legacy.',
                    $estadoAnterior, $anterior->id, $coincidencias->first()->nombre)];
            }
            return [null, sprintf('El estado anterior "%s" no tiene una correspondencia única en el flujo seleccionado. Seleccione la etapa con una justificación.', $estadoAnterior)];
        }

        $tipoEstadoActualId = $proyecto->estado?->tipo_estado_id;
        $coincidenciasEstado = $tipoEstadoActualId
            ? $etapas->filter(fn (FlujoAprobacionEtapa $etapa): bool => (int) $etapa->cargoFirma?->tipo_estado_id === (int) $tipoEstadoActualId)
            : collect();

        if ($coincidenciasEstado->count() === 1) {
            return [$coincidenciasEstado->first(), 'Se identificó por el estado actual y el cargo configurado de la etapa.'];
        }

        if ($coincidenciasEstado->count() > 1) {
            $pendientesDelEstado = $coincidenciasEstado->filter(fn (FlujoAprobacionEtapa $etapa): bool => $legacy
                ->where('estado_revision', 'Pendiente')
                ->contains(fn (FirmaProyecto $firma): bool => $this->firmaCorrespondeEtapa($firma, $etapa)));
            if ($pendientesDelEstado->count() === 1) {
                return [$pendientesDelEstado->first(), 'Se identificó por el estado y el rol de la revisión legacy pendiente.'];
            }
        }

        $cargoEstadoActual = $this->cargoEsperadoPorEstadoActual($proyecto);

        if ($cargoEstadoActual && $coincidenciasEstado->isEmpty()) {
            return [
                null,
                sprintf(
                    'El estado actual "%s" corresponde a la etapa "%s", pero esa etapa no existe en el flujo seleccionado.',
                    $proyecto->estado?->tipoestado?->nombre ?: 'desconocido',
                    $cargoEstadoActual
                ),
            ];
        }

        $pendientes = $legacy->where('estado_revision', 'Pendiente');

        if ($pendientes->count() === 1) {
            $firma = $pendientes->first();
            $coincidenciasFirma = $etapas->filter(
                fn (FlujoAprobacionEtapa $etapa): bool => $this->firmaCorrespondeEtapa($firma, $etapa)
            );

            if ($coincidenciasFirma->count() === 1) {
                return [$coincidenciasFirma->first(), 'Se identificó por la única firma legacy pendiente.'];
            }
        }

        $clavesEtapas = $etapas->map(fn (FlujoAprobacionEtapa $etapa): string => $this->claveCargoEtapa($etapa));
        $cargosAprobados = $legacy
            ->where('estado_revision', 'Aprobado')
            ->map(fn (FirmaProyecto $firma): string => $this->claveCargoFirma($firma))
            ->unique()
            ->values();

        if ($cargosAprobados->isNotEmpty() && $clavesEtapas->duplicates()->isEmpty()) {
            $primeraNoAprobada = $etapas->first(
                fn (FlujoAprobacionEtapa $etapa): bool => ! $cargosAprobados->contains($this->claveCargoEtapa($etapa))
            );

            if ($primeraNoAprobada) {
                $anterioresCompletadas = $etapas
                    ->filter(fn (FlujoAprobacionEtapa $etapa): bool => (int) $etapa->orden < (int) $primeraNoAprobada->orden)
                    ->every(fn (FlujoAprobacionEtapa $etapa): bool => $cargosAprobados->contains($this->claveCargoEtapa($etapa)));
                $posterioresSinAprobacion = $etapas
                    ->filter(fn (FlujoAprobacionEtapa $etapa): bool => (int) $etapa->orden > (int) $primeraNoAprobada->orden)
                    ->every(fn (FlujoAprobacionEtapa $etapa): bool => ! $cargosAprobados->contains($this->claveCargoEtapa($etapa)));

                if ($anterioresCompletadas && $posterioresSinAprobacion) {
                    return [$primeraNoAprobada, 'Se identificó como la primera etapa pendiente después de las aprobaciones legacy consecutivas.'];
                }
            } elseif ($etapas->isNotEmpty() && $clavesEtapas->every(
                fn (string $claveCargo): bool => $cargosAprobados->contains($claveCargo)
            )) {
                return [
                    null,
                    sprintf(
                        'Todas las etapas configuradas ya aparecen aprobadas, pero el proyecto continúa en estado "%s". El flujo debe incluir la etapa que corresponde a ese estado.',
                        $proyecto->estado?->tipoestado?->nombre ?: 'desconocido'
                    ),
                ];
            }
        }

        return [null, 'No existe evidencia suficiente para identificar automáticamente una etapa única. La adopción permanecerá bloqueada hasta corregir el historial o la configuración del flujo.'];
    }

    private function revisorPropuesto(
        FlujoAprobacionEtapa $etapa,
        Collection $legacy,
        string $modo,
        bool $esEtapaInicio
    ): ?User {
        $rol = $etapa->rolRevisor?->name;

        if ($etapa->responsableFijoPendiente()) {
            return null;
        }

        if (! $etapa->emisor_define_destinatario && $etapa->usuario_responsable_id) {
            return $this->resumptionPolicy->eligibleRecipient(
                $etapa->usuarioResponsable,
                $rol,
                true
            );
        }

        $estadoPreferido = $modo === self::MODO_SUBSANACION && $esEtapaInicio ? 'Rechazado' : 'Pendiente';
        $firmaHistorica = $legacy
            ->where('estado_revision', $estadoPreferido)
            ->filter(fn (FirmaProyecto $firma): bool => $this->firmaCorrespondeEtapa($firma, $etapa))
            ->sortByDesc('id')
            ->first();
        $usuarioHistorico = $firmaHistorica?->empleado?->user;
        if ($this->resumptionPolicy->eligibleRecipient($usuarioHistorico, $rol, true)) {
            return $usuarioHistorico;
        }

        if ($this->resumptionPolicy->eligibleRecipient($etapa->usuarioResponsable, $rol, true)) {
            return $etapa->usuarioResponsable;
        }

        return null;
    }

    private function candidatosParaEtapa(FlujoAprobacionEtapa $etapa): Collection
    {
        $rol = $etapa->rolRevisor?->name;

        if ($etapa->responsableFijoPendiente()) {
            return collect();
        }

        if (! $etapa->emisor_define_destinatario && $etapa->usuario_responsable_id) {
            $responsable = $this->resumptionPolicy->eligibleRecipient(
                $etapa->usuarioResponsable,
                $rol,
                true
            );

            return $responsable ? collect([$responsable]) : collect();
        }

        $query = User::query()->whereHas('empleado')->with('empleado')->orderBy('name');

        if ($rol) {
            $query->role($rol);
        }

        return $query->get()
            ->filter(fn (User $user): bool => $this->resumptionPolicy->eligibleRecipient($user, $rol, true) !== null)
            ->values();
    }

    private function firmasLegacy(Proyecto $proyecto): Collection
    {
        return $proyecto->firma_proyecto()
            ->whereNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->whereDoesntHave('cargo_firma.tipoCargoFirma', fn ($q) => $q->where('nombre', 'Coordinador Proyecto'))
            ->with(['empleado.user', 'cargo_firma.tipoCargoFirma'])
            ->orderBy('id')
            ->get();
    }

    private function firmaCorrespondeEtapa(FirmaProyecto $firma, FlujoAprobacionEtapa $etapa): bool
    {
        return $this->claveCargoFirma($firma) === $this->claveCargoEtapa($etapa)
            && (blank($firma->rol_requerido)
                || $this->normalizar($firma->rol_requerido) === $this->normalizar($etapa->rolRevisor?->name));
    }

    private function claveCargoFirma(FirmaProyecto $firma): string
    {
        $cargo = $firma->cargo_firma;

        if (! $cargo) {
            return 'cargo-id:'.(int) $firma->cargo_firma_id;
        }

        return $this->claveCargoSemantica(
            $cargo->tipo_estado_id,
            $cargo->tipoCargoFirma?->nombre,
            (int) $cargo->id
        );
    }

    private function claveCargoEtapa(FlujoAprobacionEtapa $etapa): string
    {
        $cargo = $etapa->cargoFirma;

        if (! $cargo) {
            return 'cargo-id:'.(int) $etapa->cargo_firma_id;
        }

        return $this->claveCargoSemantica(
            $cargo->tipo_estado_id,
            $cargo->tipoCargoFirma?->nombre,
            (int) $cargo->id
        );
    }

    private function claveCargoSemantica(mixed $tipoEstadoId, ?string $nombreCargo, int $cargoId): string
    {
        if ($tipoEstadoId || filled($nombreCargo)) {
            return 'estado:'.(int) $tipoEstadoId.'|cargo:'.$this->normalizar($nombreCargo);
        }

        return 'cargo-id:'.$cargoId;
    }

    private function cargoEsperadoPorEstadoActual(Proyecto $proyecto): ?string
    {
        $tipoEstadoId = $proyecto->estado?->tipo_estado_id;

        if (! $tipoEstadoId) {
            return null;
        }

        $nombres = CargoFirma::query()
            ->join('tipo_cargo_firma', 'tipo_cargo_firma.id', '=', 'cargo_firma.tipo_cargo_firma_id')
            ->where('cargo_firma.descripcion', 'Proyecto')
            ->where('cargo_firma.tipo_estado_id', $tipoEstadoId)
            ->pluck('tipo_cargo_firma.nombre')
            ->filter()
            ->unique()
            ->values();

        return $nombres->count() === 1 ? (string) $nombres->first() : null;
    }

    private function etapasInscripcion(FlujoAprobacion $flujo): Collection
    {
        return $flujo->etapas
            ->filter(fn (FlujoAprobacionEtapa $etapa): bool => (bool) $etapa->activo
                && (bool) $etapa->aplica_inscripcion
                && filled($etapa->cargo_firma_id))
            ->sortBy([['orden', 'asc'], ['id', 'asc']])
            ->values();
    }

    private function modoSugerido(?string $estadoNombre): string
    {
        $estado = $this->normalizar($estadoNombre);

        if (str_contains($estado, 'SUBSAN')) {
            return self::MODO_SUBSANACION;
        }

        if (in_array($estado, array_map(fn (string $nombre): string => $this->normalizar($nombre), self::ESTADOS_SIN_ENVIO), true)) {
            return self::MODO_BORRADOR;
        }

        if (in_array($estado, ['APROBADO', 'FINALIZADO', 'EN_CURSO', 'REGISTRADO', 'INSCRITO', 'CANCELADO'], true)) {
            return self::MODO_COMPLETADO;
        }

        return self::MODO_EN_REVISION;
    }

    private function modoValido(?string $modo): bool
    {
        return in_array($modo, array_keys($this->modos()), true);
    }

    private function normalizar(?string $valor): string
    {
        return Str::of((string) $valor)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '_')
            ->trim('_')
            ->value();
    }

    private function tieneRevisionesConfigurables(Proyecto $proyecto): bool
    {
        return $proyecto->firma_proyecto()->whereNotNull('flujo_aprobacion_etapa_id')->exists();
    }

    private function snapshotEtapa(FlujoAprobacionEtapa $etapa): array
    {
        return [
            'id' => (int) $etapa->id,
            'orden' => (int) $etapa->orden,
            'codigo' => $etapa->codigo,
            'nombre' => $etapa->nombre,
            'tipo_etapa' => $etapa->tipo_etapa,
            'cargo_firma_id' => (int) $etapa->cargo_firma_id,
            'rol_requerido' => $etapa->rolRevisor?->name,
            'usuario_responsable_id' => $etapa->usuario_responsable_id,
        ];
    }

    private function notificarRevisionAdoptada(Proyecto $proyecto, ?int $usuarioId, ?int $etapaId): void
    {
        if (! $usuarioId || ! $etapaId) {
            return;
        }

        $usuario = User::with('empleado')->find($usuarioId);
        $etapa = FlujoAprobacionEtapa::find($etapaId);

        if (! $usuario || ! $etapa || ! $this->resumptionPolicy->eligibleRecipient($usuario, $etapa->rolRevisor?->name, true)) {
            Log::error('No se pudo notificar la revisión de un proyecto legacy adoptado.', [
                'proyecto_id' => $proyecto->id,
                'usuario_id' => $usuarioId,
                'etapa_id' => $etapaId,
            ]);

            return;
        }

        try {
            Mail::to($usuario->email)->queue(new EtapaFlujoPendiente($proyecto, $usuario, $etapa));
        } catch (\Throwable $exception) {
            Log::error('No se pudo encolar la notificación del proyecto legacy adoptado.', [
                'proyecto_id' => $proyecto->id,
                'usuario_id' => $usuarioId,
                'etapa_id' => $etapaId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

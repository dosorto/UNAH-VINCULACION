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

    public const MODO_EN_REVISION = 'EN_REVISION';

    public const MODO_SUBSANACION = 'SUBSANACION';

    public const MODO_COMPLETADO = 'COMPLETADO';

    public function __construct(private readonly WorkflowResumptionPolicy $resumptionPolicy) {}

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
        FlujoAprobacion $flujo
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
        $etapas = $this->etapasInscripcion($flujo);
        $modoSugerido = $this->modoSugerido($estadoNombre);
        $modo = $modoSugerido;
        $cargoEstadoActual = $this->cargoEsperadoPorEstadoActual($proyecto);
        [$etapaSugerida, $razonEtapa] = $this->detectarEtapaActual($proyecto, $etapas, $legacy, $modo);
        $etapaInicio = in_array($modo, [self::MODO_EN_REVISION, self::MODO_SUBSANACION], true)
            ? $etapaSugerida
            : null;
        $bloqueos = collect();

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

        if (in_array($modo, [self::MODO_EN_REVISION, self::MODO_SUBSANACION], true) && ! $etapaInicio) {
            $bloqueos->push('El sistema no pudo identificar automáticamente una etapa única. Revise el historial legacy y la configuración del flujo.');
        }

        if ($modo === self::MODO_BORRADOR
            && $legacy->contains(fn (FirmaProyecto $firma): bool => in_array($firma->estado_revision, ['Pendiente', 'Rechazado'], true))
        ) {
            $bloqueos->push('El proyecto tiene revisiones legacy activas; no es seguro adoptarlo como borrador.');
        }

        if ($modo === self::MODO_SUBSANACION && $legacy->where('estado_revision', 'Rechazado')->count() > 1) {
            $bloqueos->push('El historial legacy contiene más de una revisión rechazada; debe depurarse antes de adoptar el proyecto.');
        }

        $etapasDesdeInicio = $etapaInicio
            ? $etapas->filter(fn (FlujoAprobacionEtapa $etapa): bool => (int) $etapa->orden >= (int) $etapaInicio->orden)->values()
            : collect();

        $etapasUi = $etapas->map(function (FlujoAprobacionEtapa $etapa) use (
            $legacy,
            $etapaInicio,
            $etapasDesdeInicio,
            $modo,
            $bloqueos
        ): array {
            $estaEnNuevoRecorrido = $etapasDesdeInicio->contains(fn (FlujoAprobacionEtapa $item): bool => (int) $item->id === (int) $etapa->id);
            $candidatos = $estaEnNuevoRecorrido ? $this->candidatosParaEtapa($etapa) : collect();
            $propuesto = $estaEnNuevoRecorrido
                ? $this->revisorPropuesto($etapa, $legacy, $modo, (int) $etapaInicio?->id === (int) $etapa->id)
                : null;

            if ($estaEnNuevoRecorrido && $candidatos->isEmpty()) {
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
                'es_anterior' => $etapaInicio && (int) $etapa->orden < (int) $etapaInicio->orden,
                'en_nuevo_recorrido' => $estaEnNuevoRecorrido,
                'responsable_fijo' => ! $etapa->emisor_define_destinatario && filled($etapa->usuario_responsable_id),
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
            'etapa_inicio_id' => $etapaInicio?->id,
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
        ?string $motivoSubsanacion = null
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
            $motivoSubsanacion
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

            $diagnostico = $this->diagnosticar($proyectoBloqueado, $flujoBloqueado);

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
                ? $etapas->filter(fn (FlujoAprobacionEtapa $etapa): bool => (int) $etapa->orden >= (int) $etapaInicio->orden)->values()
                : collect();
            $asignaciones = $this->validarRevisores(
                $etapasNuevoRecorrido,
                $revisoresPorEtapa,
                in_array($modo, [self::MODO_EN_REVISION, self::MODO_SUBSANACION], true)
            );
            $estadoOrigen = $proyectoBloqueado->estado;
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
                $motivoSubsanacion = trim((string) $motivoSubsanacion);

                if ($motivoSubsanacion === '') {
                    throw new \RuntimeException('Debe indicar el motivo histórico de la subsanación.');
                }

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
                    $modo
                ): FirmaProyecto {
                    $usuario = $asignaciones->get((int) $etapa->id);

                    return $proyectoBloqueado->firma_proyecto()->create([
                        'empleado_id' => $usuario->empleado->id,
                        'cargo_firma_id' => $etapa->cargo_firma_id,
                        'flujo_aprobacion_id' => $flujoBloqueado->id,
                        'flujo_aprobacion_etapa_id' => $etapa->id,
                        'orden_revision' => $etapa->orden,
                        'etapa_codigo' => $etapa->codigo,
                        'etapa_nombre' => $etapa->nombre,
                        'rol_requerido' => $etapa->rolRevisor?->name,
                        'responsable_usuario_id' => $usuario->id,
                        'revision_ciclo' => 1,
                        'estado_revision' => $modo === self::MODO_SUBSANACION && $indice === 0
                            ? 'Rechazado'
                            : 'Pendiente',
                        'firma_id' => null,
                        'sello_id' => null,
                        'fecha_firma' => null,
                        'hash' => (string) Str::uuid(),
                    ]);
                })->values();
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
            $etapasAnteriores = $modo === self::MODO_COMPLETADO
                ? $etapas
                : ($etapaInicio
                    ? $etapas->filter(fn (FlujoAprobacionEtapa $etapa): bool => (int) $etapa->orden < (int) $etapaInicio->orden)
                    : collect());

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
                    'version' => 1,
                    'diagnostico' => [
                        'estado_id' => $estadoOrigen?->tipo_estado_id,
                        'estado' => $estadoOrigen?->tipoestado?->nombre,
                        'comentario' => $estadoOrigen?->comentario,
                        'razon_etapa' => $diagnostico['razon_etapa'],
                    ],
                    'etapas_anteriores' => $etapasAnteriores->map(fn (FlujoAprobacionEtapa $etapa): array => $this->snapshotEtapa($etapa))->values()->all(),
                    'nuevo_recorrido' => $etapasNuevoRecorrido->map(fn (FlujoAprobacionEtapa $etapa): array => $this->snapshotEtapa($etapa))->values()->all(),
                    'revisores' => $asignaciones->map(fn (User $user): int => (int) $user->id)->all(),
                    'firmas_legacy' => $legacy->map(fn (FirmaProyecto $firma): array => [
                        'id' => (int) $firma->id,
                        'cargo_firma_id' => (int) $firma->cargo_firma_id,
                        'empleado_id' => (int) $firma->empleado_id,
                        'estado' => $firma->estado_revision,
                    ])->values()->all(),
                    'firmas_legacy_anuladas' => $legacyPendientes->all(),
                    'firmas_configurables_creadas' => $firmasCreadas->pluck('id')->all(),
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
        $tipoEstadoId = $etapa->cargoFirma?->tipo_estado_id;

        if (! $tipoEstadoId) {
            throw new \RuntimeException(sprintf('La etapa "%s" no tiene un estado de proyecto configurado.', $etapa->nombre));
        }

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

            $estadosAnteriores = $proyecto->estado_proyecto()
                ->where('id', '!=', $proyecto->estado?->id)
                ->latest('id')
                ->pluck('tipo_estado_id');

            foreach ($estadosAnteriores as $tipoEstadoId) {
                $coincidencias = $etapas->where('cargoFirma.tipo_estado_id', $tipoEstadoId);

                if ($coincidencias->count() === 1) {
                    return [$coincidencias->first(), 'Se identificó por el estado inmediatamente anterior a la subsanación.'];
                }
            }
        }

        $tipoEstadoActualId = $proyecto->estado?->tipo_estado_id;
        $coincidenciasEstado = $tipoEstadoActualId
            ? $etapas->filter(fn (FlujoAprobacionEtapa $etapa): bool => (int) $etapa->cargoFirma?->tipo_estado_id === (int) $tipoEstadoActualId)
            : collect();

        if ($coincidenciasEstado->count() === 1) {
            return [$coincidenciasEstado->first(), 'Se identificó por el estado actual y el cargo configurado de la etapa.'];
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
            ->with(['empleado.user', 'cargo_firma.tipoCargoFirma'])
            ->orderBy('id')
            ->get();
    }

    private function firmaCorrespondeEtapa(FirmaProyecto $firma, FlujoAprobacionEtapa $etapa): bool
    {
        return $this->claveCargoFirma($firma) === $this->claveCargoEtapa($etapa);
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

        if (in_array($estado, ['BORRADOR', 'AUTOGUARDADO', 'PENDIENTEINFORMACION', 'PENDIENTE_INFORMACION'], true)) {
            return self::MODO_BORRADOR;
        }

        if (in_array($estado, ['APROBADO', 'FINALIZADO', 'EN_CURSO', 'INSCRITO', 'CANCELADO'], true)) {
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

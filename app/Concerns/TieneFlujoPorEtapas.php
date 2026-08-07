<?php

namespace App\Concerns;

use App\Models\Estado\EstadoProyecto;
use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Proyecto\FirmaProyecto;
use App\Models\Proyecto\FlujoAprobacion;
use App\Models\Proyecto\FlujoAprobacionEtapa;
use App\Services\Workflow\WorkflowResumptionPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Motor genérico de aprobación por etapas, compartido por cualquier modelo
 * que necesite un flujo configurable (Proyecto, PpsServicioSocial, futuros
 * formularios). Se apoya en las tablas ya polimórficas `firma_proyecto`
 * (firmable_type/firmable_id) y `estado_proyecto` (estadoable_type/estadoable_id)
 * — no crea tablas nuevas.
 *
 * El modelo host debe definir:
 * - firmasDeEtapa(): MorphMany hacia FirmaProyecto ('firmable')
 * - historialEstados(): MorphMany hacia EstadoProyecto ('estadoable')
 * - estadoActual(): HasOne hacia EstadoProyecto ('estadoable') con es_actual=true
 */
trait TieneFlujoPorEtapas
{
    public function resolveFlujoAprobacionPorProceso(string $proceso, ?string $codigoFormulario = null): ?FlujoAprobacion
    {
        return FlujoAprobacion::query()
            ->with('etapas.cargoFirma.tipoCargoFirma')
            ->where('proceso', $proceso)
            ->where('activo', true)
            ->when($codigoFormulario, fn ($query) => $query->where('codigo_formulario', $codigoFormulario))
            ->orderBy('id')
            ->first();
    }

    public function etapasActivasDelFlujo(FlujoAprobacion $flujo): Collection
    {
        return $flujo->etapas
            ->filter(fn (FlujoAprobacionEtapa $etapa) => (bool) $etapa->activo)
            ->sortBy('orden')
            ->values();
    }

    public function guardarFirmaDeEtapa(
        FlujoAprobacionEtapa $etapa,
        Empleado $empleado,
        array $attributes = [],
        int $revisionCiclo = 1
    ): FirmaProyecto {
        $this->validarFirmaDeEtapa($etapa, $revisionCiclo);

        $etapa->loadMissing('rolRevisor');

        $firma = $this->firmasDeEtapa()->getRelated()->newQuery()
            ->where('firmable_type', static::class)
            ->where('firmable_id', $this->getKey())
            ->updateOrCreate(
                [
                    'flujo_aprobacion_etapa_id' => $etapa->id,
                    'revision_ciclo' => $revisionCiclo,
                ],
                array_merge($attributes, [
                    'firmable_type' => static::class,
                    'firmable_id' => $this->getKey(),
                    'empleado_id' => $empleado->id,
                    'cargo_firma_id' => $etapa->cargo_firma_id,
                    'flujo_aprobacion_id' => $etapa->flujo_aprobacion_id,
                    'flujo_aprobacion_etapa_id' => $etapa->id,
                    'orden_revision' => $etapa->orden,
                    'etapa_codigo' => $etapa->codigo,
                    'etapa_nombre' => $etapa->nombre,
                    'rol_requerido' => $etapa->rolRevisor?->name,
                    'responsable_usuario_id' => $etapa->usuario_responsable_id,
                    'revision_ciclo' => $revisionCiclo,
                    'hash' => $attributes['hash'] ?? 'hash',
                ])
            );

        $this->anularFirmasPendientesDuplicadasDeEtapa($etapa->id, $revisionCiclo, $firma->id);

        return $firma;
    }

    public function anularFirmasPendientesDuplicadasDeEtapa(
        int $flujoEtapaId,
        int $revisionCiclo = 1,
        ?int $firmaPrincipalId = null
    ): void {
        $this->firmasDeEtapa()
            ->where('flujo_aprobacion_etapa_id', $flujoEtapaId)
            ->where('revision_ciclo', $revisionCiclo)
            ->when($firmaPrincipalId, fn ($query) => $query->where('id', '!=', $firmaPrincipalId))
            ->where('estado_revision', 'Pendiente')
            ->update(['estado_revision' => 'Anulado']);
    }

    public function sincronizarFirmasDeEtapasDelFlujo(
        array $empleadosPorEtapa,
        FlujoAprobacion $flujo,
        int $revisionCiclo = 1
    ): Collection {
        if ($revisionCiclo < 1) {
            throw new \RuntimeException('El ciclo de revisión debe ser mayor o igual a 1.');
        }

        $etapas = $this->etapasActivasDelFlujo($flujo);

        if ($etapas->isEmpty()) {
            throw new \RuntimeException('No hay etapas activas configuradas para este flujo.');
        }

        $empleadosNormalizados = collect($empleadosPorEtapa)
            ->mapWithKeys(fn ($empleadoId, $etapaId) => [(int) $etapaId => (int) $empleadoId])
            ->all();

        $etapaIds = $etapas->pluck('id')->map(fn ($id) => (int) $id);

        foreach (array_keys($empleadosNormalizados) as $etapaId) {
            if (! $etapaIds->contains((int) $etapaId)) {
                throw new \RuntimeException('La etapa indicada no pertenece al flujo.');
            }
        }

        $empleados = Empleado::whereIn('id', array_values($empleadosNormalizados))
            ->get()
            ->keyBy('id');

        foreach ($etapas as $etapa) {
            if (! $etapa->cargo_firma_id) {
                throw new \RuntimeException(sprintf('La etapa "%s" no tiene cargo de firma.', $etapa->nombre));
            }

            if (! array_key_exists((int) $etapa->id, $empleadosNormalizados)) {
                throw new \RuntimeException(sprintf('No se indicó un empleado para la etapa "%s".', $etapa->nombre));
            }

            if (! $empleados->has($empleadosNormalizados[(int) $etapa->id])) {
                throw new \RuntimeException(sprintf('El empleado indicado para la etapa "%s" no existe.', $etapa->nombre));
            }
        }

        return DB::transaction(function () use ($etapas, $empleadosNormalizados, $empleados, $revisionCiclo): Collection {
            return $etapas
                ->map(fn (FlujoAprobacionEtapa $etapa) => $this->guardarFirmaDeEtapa(
                    $etapa,
                    $empleados->get($empleadosNormalizados[(int) $etapa->id]),
                    [
                        'estado_revision' => 'Pendiente',
                        'firma_id' => null,
                        'sello_id' => null,
                        'fecha_firma' => null,
                    ],
                    $revisionCiclo
                ))
                ->values();
        });
    }

    /**
     * Etapas de tipo "APROBACION" del flujo de este registro, junto con la
     * firma vigente (del ciclo de revisión actual) para cada una, si existe.
     * Fuente de verdad para pintar steppers de progreso en listados/paneles.
     *
     * @return Collection<int, array{etapa: FlujoAprobacionEtapa, firma: ?FirmaProyecto}>
     */
    public function stepperDeAprobacion(): Collection
    {
        $flujo = $this->resolveFlujoAprobacion();

        if (! $flujo) {
            return collect();
        }

        $etapasFirmantes = $this->etapasActivasDelFlujo($flujo)
            ->filter(fn (FlujoAprobacionEtapa $etapa) => $etapa->tipo_etapa === 'APROBACION' && $etapa->cargo_firma_id)
            ->values();

        if ($etapasFirmantes->isEmpty()) {
            return collect();
        }

        $firmasPorEtapa = $this->firmasDeEtapa()
            ->where('flujo_aprobacion_id', $flujo->id)
            ->whereIn('flujo_aprobacion_etapa_id', $etapasFirmantes->pluck('id'))
            ->whereNull('deleted_at')
            ->where('estado_revision', '!=', 'Anulado')
            ->orderByDesc('revision_ciclo')
            ->orderByDesc('id')
            ->get()
            ->groupBy('flujo_aprobacion_etapa_id');

        return $etapasFirmantes->map(fn (FlujoAprobacionEtapa $etapa) => [
            'etapa' => $etapa,
            'firma' => $firmasPorEtapa->get($etapa->id)?->first(),
        ]);
    }

    public function firmasDeEtapasDelFlujo(int $flujoAprobacionId, int $revisionCiclo = 1): Collection
    {
        $this->validarParametrosFirmasDeEtapas($flujoAprobacionId, $revisionCiclo);

        return $this->firmasDeEtapa()
            ->where('flujo_aprobacion_id', $flujoAprobacionId)
            ->where('revision_ciclo', $revisionCiclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->orderBy('orden_revision')
            ->orderBy('id')
            ->get();
    }

    public function firmaActualDeEtapasDelFlujo(int $flujoAprobacionId, int $revisionCiclo = 1): ?FirmaProyecto
    {
        foreach ($this->firmasDeEtapasDelFlujo($flujoAprobacionId, $revisionCiclo) as $firma) {
            if (in_array($firma->estado_revision, ['Aprobado', 'Anulado'], true)) {
                continue;
            }

            if ($firma->estado_revision === 'Pendiente') {
                return $firma;
            }

            if ($firma->estado_revision === 'Rechazado') {
                return null;
            }
        }

        return null;
    }

    public function firmaEsActualEnFlujoPorEtapa(FirmaProyecto $firma): bool
    {
        if (! $this->firmaPerteneceAlFlujoPorEtapaDe($firma) || $firma->estado_revision !== 'Pendiente') {
            return false;
        }

        $firmaActual = $this->firmaActualDeEtapasDelFlujo((int) $firma->flujo_aprobacion_id, (int) $firma->revision_ciclo);

        return (int) $firmaActual?->id === (int) $firma->id;
    }

    public function siguienteFirmaDeEtapa(FirmaProyecto $firma): ?FirmaProyecto
    {
        if (! $this->firmaPerteneceAlFlujoPorEtapaDe($firma) || blank($firma->orden_revision)) {
            return null;
        }

        return $this->firmasDeEtapa()
            ->where('flujo_aprobacion_id', $firma->flujo_aprobacion_id)
            ->where('revision_ciclo', $firma->revision_ciclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->where('estado_revision', '!=', 'Anulado')
            ->where('orden_revision', '>', $firma->orden_revision)
            ->orderBy('orden_revision')
            ->orderBy('id')
            ->first();
    }

    public function firmasDeEtapasCompletadas(int $flujoAprobacionId, int $revisionCiclo = 1): bool
    {
        $firmas = $this->firmasDeEtapasDelFlujo($flujoAprobacionId, $revisionCiclo);

        if ($firmas->isEmpty()) {
            return false;
        }

        if ($firmas->contains(fn (FirmaProyecto $firma) => in_array($firma->estado_revision, ['Pendiente', 'Rechazado'], true))) {
            return false;
        }

        return $firmas->contains(fn (FirmaProyecto $firma) => $firma->estado_revision === 'Aprobado')
            && $firmas->every(fn (FirmaProyecto $firma) => in_array($firma->estado_revision, ['Aprobado', 'Anulado'], true));
    }

    public function crearNuevoCicloDesdeFirmaRechazada(FirmaProyecto $firmaRechazada, array $empleadosPorEtapa): Collection
    {
        return DB::transaction(function () use ($firmaRechazada, $empleadosPorEtapa): Collection {
            static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            $firmaBloqueada = FirmaProyecto::query()->whereKey($firmaRechazada->id)->lockForUpdate()->first();

            if (! $firmaBloqueada) {
                throw new \RuntimeException('La firma indicada no corresponde a una etapa rechazada.');
            }

            $this->validarFirmaRechazadaParaNuevoCiclo($firmaBloqueada);

            $firmasCiclo = $this->firmasDeEtapa()
                ->where('flujo_aprobacion_id', $firmaBloqueada->flujo_aprobacion_id)
                ->where('revision_ciclo', $firmaBloqueada->revision_ciclo)
                ->whereNotNull('flujo_aprobacion_etapa_id')
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->orderBy('orden_revision')
                ->orderBy('id')
                ->get();

            $this->validarFirmaRechazadaParaNuevoCiclo($firmaBloqueada->fresh(), $firmasCiclo);

            $nuevoCiclo = (int) $firmaBloqueada->revision_ciclo + 1;

            if ($this->existenFirmasDeCicloPorEtapa((int) $firmaBloqueada->flujo_aprobacion_id, $nuevoCiclo)) {
                throw new \RuntimeException('Ya existe el siguiente ciclo de revisión para este registro.');
            }

            $plan = app(WorkflowResumptionPolicy::class)->plan(
                $firmasCiclo
                    ->reject(fn (FirmaProyecto $firma): bool => $firma->estado_revision === 'Anulado')
                    ->map(fn (FirmaProyecto $firma): array => [
                        'stage_id' => (int) $firma->flujo_aprobacion_etapa_id,
                        'order' => (int) $firma->orden_revision,
                        'status' => match ($firma->estado_revision) {
                            'Aprobado' => 'APPROVED',
                            'Rechazado' => 'REJECTED',
                            'Pendiente' => 'PENDING',
                            default => 'INVALID',
                        },
                        'source' => $firma,
                    ])
                    ->values()
            );

            if ((int) $plan->rejectedStage['source']->id !== (int) $firmaBloqueada->id) {
                throw new \RuntimeException('No se pudo preparar de forma segura el nuevo ciclo de revisión.');
            }

            $firmasBase = $plan->stages->pluck('source')->values();
            $empleados = $this->validarAsignacionesParaNuevoCiclo($firmasBase, $empleadosPorEtapa);

            return $firmasBase
                ->map(fn (FirmaProyecto $firmaBase) => $this->firmasDeEtapa()->create([
                    'empleado_id' => $empleados->get((int) $firmaBase->flujo_aprobacion_etapa_id)->id,
                    'cargo_firma_id' => $firmaBase->cargo_firma_id,
                    'flujo_aprobacion_id' => $firmaBase->flujo_aprobacion_id,
                    'flujo_aprobacion_etapa_id' => $firmaBase->flujo_aprobacion_etapa_id,
                    'orden_revision' => $firmaBase->orden_revision,
                    'etapa_codigo' => $firmaBase->etapa_codigo,
                    'etapa_nombre' => $firmaBase->etapa_nombre,
                    'rol_requerido' => $firmaBase->rol_requerido,
                    'responsable_usuario_id' => $empleados->get((int) $firmaBase->flujo_aprobacion_etapa_id)->user_id,
                    'revision_ciclo' => $nuevoCiclo,
                    'estado_revision' => 'Pendiente',
                    'firma_id' => null,
                    'sello_id' => null,
                    'fecha_firma' => null,
                    'hash' => (string) Str::uuid(),
                ]))
                ->sortBy([['orden_revision', 'asc'], ['id', 'asc']])
                ->values();
        });
    }

    public function agregarEstado(Empleado $empleado, int $tipoEstadoId, string $comentario = 'Comentario'): EstadoProyecto
    {
        return $this->historialEstados()->create([
            'empleado_id' => $empleado->id,
            'tipo_estado_id' => $tipoEstadoId,
            'fecha' => now(),
            'comentario' => $comentario,
        ]);
    }

    public function estaEnAlgunEstado(array $estadoNombres): bool
    {
        $tipoEstadoId = $this->estadoActual?->tipo_estado_id;

        if (! $tipoEstadoId) {
            return false;
        }

        return in_array(
            $tipoEstadoId,
            TipoEstado::whereIn('nombre', $estadoNombres)->pluck('id')->all(),
            true
        );
    }

    private function validarFirmaDeEtapa(FlujoAprobacionEtapa $etapa, int $revisionCiclo): void
    {
        if ($revisionCiclo < 1) {
            throw new \RuntimeException('El ciclo de revisión debe ser mayor o igual a 1.');
        }

        if (! $etapa->cargo_firma_id) {
            throw new \RuntimeException(sprintf('La etapa "%s" no tiene cargo de firma.', $etapa->nombre));
        }
    }

    private function validarParametrosFirmasDeEtapas(int $flujoAprobacionId, int $revisionCiclo): void
    {
        if ($flujoAprobacionId < 1) {
            throw new \RuntimeException('El flujo de aprobación indicado no es válido.');
        }

        if ($revisionCiclo < 1) {
            throw new \RuntimeException('El ciclo de revisión debe ser mayor o igual a 1.');
        }
    }

    private function firmaPerteneceAlFlujoPorEtapaDe(FirmaProyecto $firma): bool
    {
        if (! $firma->exists || filled($firma->deleted_at) || ! $firma->usaFlujoPorEtapa()) {
            return false;
        }

        if (! $firma->flujo_aprobacion_id || ! $firma->revision_ciclo) {
            return false;
        }

        return $firma->firmable_type === static::class && (int) $firma->firmable_id === (int) $this->getKey();
    }

    private function existenFirmasDeCicloPorEtapa(int $flujoAprobacionId, int $revisionCiclo): bool
    {
        return $this->firmasDeEtapa()
            ->where('flujo_aprobacion_id', $flujoAprobacionId)
            ->where('revision_ciclo', $revisionCiclo)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->exists();
    }

    private function ultimoCicloDeFirmasPorEtapa(int $flujoAprobacionId): int
    {
        return (int) $this->firmasDeEtapa()
            ->where('flujo_aprobacion_id', $flujoAprobacionId)
            ->whereNotNull('flujo_aprobacion_etapa_id')
            ->whereNull('deleted_at')
            ->max('revision_ciclo');
    }

    private function validarFirmaRechazadaParaNuevoCiclo(FirmaProyecto $firmaRechazada, ?Collection $firmasCiclo = null): void
    {
        if (! $firmaRechazada->exists
            || filled($firmaRechazada->deleted_at)
            || ! $firmaRechazada->usaFlujoPorEtapa()
            || $firmaRechazada->estado_revision !== 'Rechazado'
            || ! $firmaRechazada->flujo_aprobacion_id
            || ! $firmaRechazada->flujo_aprobacion_etapa_id
            || (int) $firmaRechazada->revision_ciclo < 1
            || blank($firmaRechazada->orden_revision)
            || $firmaRechazada->firmable_type !== static::class
        ) {
            throw new \RuntimeException('La firma indicada no corresponde a una etapa rechazada.');
        }

        if (! $this->firmaPerteneceAlFlujoPorEtapaDe($firmaRechazada)) {
            throw new \RuntimeException('La firma no pertenece al registro indicado.');
        }

        if ($this->existenFirmasDeCicloPorEtapa((int) $firmaRechazada->flujo_aprobacion_id, (int) $firmaRechazada->revision_ciclo + 1)) {
            throw new \RuntimeException('Ya existe el siguiente ciclo de revisión para este registro.');
        }

        $ultimoCiclo = $this->ultimoCicloDeFirmasPorEtapa((int) $firmaRechazada->flujo_aprobacion_id);

        if ((int) $firmaRechazada->revision_ciclo !== $ultimoCiclo) {
            throw new \RuntimeException('La firma rechazada no pertenece al último ciclo de revisión.');
        }

        $firmasCiclo = $firmasCiclo ?: $this->firmasDeEtapasDelFlujo(
            (int) $firmaRechazada->flujo_aprobacion_id,
            (int) $firmaRechazada->revision_ciclo
        );

        if ($firmasCiclo->where('estado_revision', 'Rechazado')->count() !== 1) {
            throw new \RuntimeException('El ciclo de revisión contiene más de una etapa rechazada.');
        }
    }

    private function validarAsignacionesParaNuevoCiclo(Collection $firmasBase, array $empleadosPorEtapa): Collection
    {
        $empleadosNormalizados = collect($empleadosPorEtapa)
            ->mapWithKeys(fn ($empleadoId, $etapaId) => [(int) $etapaId => (int) $empleadoId]);

        $empleados = Empleado::withTrashed()
            ->with('user')
            ->whereIn('id', $empleadosNormalizados->values()->all())
            ->get()
            ->keyBy('id');

        foreach ($firmasBase as $firmaBase) {
            $etapaId = (int) $firmaBase->flujo_aprobacion_etapa_id;
            $etapaNombre = $firmaBase->etapa_nombre ?: $firmaBase->etapa_codigo ?: $etapaId;

            if (! $empleadosNormalizados->has($etapaId)) {
                throw new \RuntimeException(sprintf('No se indicó un empleado para la etapa "%s".', $firmaBase->etapa_nombre));
            }

            $empleado = $empleados->get($empleadosNormalizados->get($etapaId));
            $usuarioElegible = $empleado && ! $empleado->trashed()
                ? app(WorkflowResumptionPolicy::class)->eligibleRecipient($empleado->user, $firmaBase->rol_requerido, true)
                : null;

            if (! $usuarioElegible || (int) $usuarioElegible->empleado?->id !== (int) $empleado?->id) {
                throw new \RuntimeException(sprintf(
                    'El revisor anterior de la etapa "%s" ya no es elegible; seleccione un reemplazo válido.',
                    $etapaNombre
                ));
            }

        }

        return $empleados;
    }
}

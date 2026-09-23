<?php

namespace App\Support\Dashboard\Formularios;

use App\Models\ENF\EnfAccion;
use App\Models\ENF\EnfRevision;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadoGeneral;
use App\Support\Dashboard\TipoAmbito;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Educación no formal (FORM-DVUS-016, FORM-DVUS-018).
 *
 * Tiene motor propio: el estado vive en `enf_acciones.estado_flujo` y las
 * revisiones en `enf_revisiones`, con un ciclo completo por cada proceso
 * (inscripción, informe intermedio, informe final). Por eso necesita clase
 * propia; el panel la trata igual que al resto.
 */
final class FormularioEnf implements FormularioPanel
{
    use LeeEtapasDelFlujo;

    private const PROCESOS_ENF = [
        EnfAccion::PROCESO_INSCRIPCION => self::INSCRIPCION,
        EnfAccion::PROCESO_INFORME_INTERMEDIO => self::INFORME_INTERMEDIO,
        EnfAccion::PROCESO_INFORME_FINAL => self::CIERRE,
    ];

    public function __construct(
        private readonly string $codigo,
        private readonly string $nombre,
        private readonly ?string $tipoAccion = 'EDUCACION_NO_FORMAL',
    ) {}

    public function codigo(): string
    {
        return $this->codigo;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function tipoAccion(): ?string
    {
        return $this->tipoAccion;
    }

    public function admiteAmbito(AmbitoPanel $ambito): bool
    {
        // enf_acciones guarda centro_facultad_id y departamento_academico_id.
        return true;
    }

    public function conteos(AmbitoPanel $ambito): array
    {
        $conteos = EstadoGeneral::vacio();

        $this->acciones($ambito)
            ->selectRaw("UPPER(COALESCE(estado_flujo, '')) as estado, COUNT(*) as cantidad")
            ->groupByRaw("UPPER(COALESCE(estado_flujo, ''))")
            ->toBase()
            ->get()
            ->each(function ($fila) use (&$conteos): void {
                $clave = match ($fila->estado) {
                    '', 'BORRADOR' => EstadoGeneral::BORRADOR,
                    'EN_REVISION' => EstadoGeneral::EN_REVISION,
                    'SUBSANACION', 'SUBSANACIÓN' => EstadoGeneral::SUBSANACION,
                    'APROBADO' => EstadoGeneral::APROBADO,
                    'FINALIZADO' => EstadoGeneral::FINALIZADO,
                    default => EstadoGeneral::OTROS,
                };
                $conteos[$clave] += (int) $fila->cantidad;
            });

        return $conteos;
    }

    public function esperando(AmbitoPanel $ambito): Collection
    {
        return EnfRevision::query()
            ->esperandoDecision()
            ->whereIn('enf_revisiones.enf_accion_id', $this->acciones($ambito)->select('enf_acciones.id'))
            ->selectRaw('enf_revisiones.*, DATEDIFF(NOW(), '.EnfRevision::llegadaSql().') as dias')
            ->get()
            ->unique(fn (EnfRevision $revision): string => $revision->enf_accion_id.'#'.$revision->proceso)
            ->map(fn (EnfRevision $revision): array => [
                'tramite' => EnfAccion::class.'#'.$revision->enf_accion_id.'#'.$revision->proceso,
                'proceso' => self::PROCESOS_ENF[$revision->proceso] ?? self::INSCRIPCION,
                'etapa_id' => $revision->flujo_aprobacion_etapa_id !== null ? (int) $revision->flujo_aprobacion_etapa_id : null,
                'etapa' => (string) ($revision->etapa_nombre ?: 'Sin etapa'),
                'orden' => (int) $revision->orden,
                'rol' => $revision->rol_requerido,
                'heredado' => false,
                'dias' => max(0, (int) $revision->dias),
            ])
            ->values();
    }

    /** @return Builder<EnfAccion> */
    private function acciones(AmbitoPanel $ambito): Builder
    {
        $query = EnfAccion::query()->where('enf_acciones.codigo_formulario', $this->codigo);

        return match ($ambito->tipo) {
            TipoAmbito::Ninguno => $query->whereRaw('1 = 0'),
            TipoAmbito::Centro => $query->where('enf_acciones.centro_facultad_id', $ambito->centroFacultadId),
            TipoAmbito::Departamento => $query->where('enf_acciones.departamento_academico_id', $ambito->departamentoAcademicoId),
            TipoAmbito::Personal => $query->perteneceA($ambito->userId, $ambito->empleadoId),
            TipoAmbito::Revision => $query->whereHas('revisiones', fn (Builder $revision) => $revision
                ->where('asignado_usuario_id', $ambito->userId)
                ->orWhere('responsable_usuario_id', $ambito->userId)),
            TipoAmbito::Global => $query,
        };
    }
}

<?php

namespace App\Support\Dashboard\Formularios;

use App\Models\Proyecto\DocumentoProyecto;
use App\Models\Proyecto\Proyecto;
use App\Support\Dashboard\AmbitoPanel;
use App\Support\Dashboard\EstadoGeneral;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Proyectos de vinculación de un tipo de acción (FORM-DVUS-001, 015...).
 *
 * Comparten la tabla `proyecto` y se distinguen por `tipo_accion_id`. Con
 * tipo de acción null agrupa los proyectos heredados que no tienen ninguno:
 * sin esa entrada desaparecerían del panel.
 *
 * Además de la inscripción, esperan firmas sus informes intermedio y final
 * (DocumentoProyecto), que se cuentan dentro del mismo formulario en su propio
 * proceso.
 */
final class FormularioProyecto extends FormularioConFirmas
{
    /** @var array<string,?int> */
    private static array $tiposAccion = [];

    public function admiteAmbito(AmbitoPanel $ambito): bool
    {
        return true;
    }

    protected function modelo(): string
    {
        return Proyecto::class;
    }

    protected function idsQuery(AmbitoPanel $ambito): Builder
    {
        $query = $ambito->aplicarA(Proyecto::query());

        if ($this->tipoAccion === null) {
            $query->whereNull('proyecto.tipo_accion_id');
        } elseif ($tipoAccionId = $this->tipoAccionId()) {
            $query->where('proyecto.tipo_accion_id', $tipoAccionId);
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query->select('proyecto.id')->toBase();
    }

    /**
     * Además de las firmas que esperan, los proyectos en revisión sin ninguna
     * firma pendiente. En el recorrido anterior por cargos, un proyecto queda
     * «En revision» tras la última firma y nadie lo tiene en su bandeja; sin
     * estas filas el panel lo contaría en revisión sin decir dónde espera.
     */
    public function esperando(AmbitoPanel $ambito): Collection
    {
        $filas = parent::esperando($ambito);
        $conEtapa = $filas->pluck('tramite')->flip();
        $idsEnRevision = EstadoGeneral::idsDe(EstadoGeneral::EN_REVISION);

        if ($idsEnRevision === []) {
            return $filas;
        }

        $sinFirma = DB::table('estado_proyecto as ep')
            ->join('tipo_estado as te', 'te.id', '=', 'ep.tipo_estado_id')
            ->join('proyecto', 'proyecto.id', '=', 'ep.estadoable_id')
            ->where('ep.estadoable_type', Proyecto::class)
            ->where('ep.es_actual', true)
            ->whereIn('ep.tipo_estado_id', $idsEnRevision)
            ->whereIn('ep.estadoable_id', $this->idsQuery($ambito))
            ->get(['proyecto.id', 'proyecto.flujo_aprobacion_id', 'te.nombre', 'ep.created_at'])
            ->reject(fn ($fila): bool => $conEtapa->has(Proyecto::class.'#'.$fila->id))
            ->unique('id')
            ->map(fn ($fila): array => [
                'tramite' => Proyecto::class.'#'.$fila->id,
                'proceso' => self::INSCRIPCION,
                'etapa_id' => null,
                'etapa' => $fila->nombre.', sin firma pendiente',
                'orden' => null,
                'rol' => null,
                'heredado' => $fila->flujo_aprobacion_id === null,
                'dias' => max(0, (int) Carbon::parse($fila->created_at)->startOfDay()->diffInDays(now()->startOfDay())),
            ]);

        return $filas->concat($sinFirma)->values();
    }

    protected function consultaFirmas(AmbitoPanel $ambito): Builder
    {
        return DB::table('firma_proyecto')
            ->leftJoin('proyecto_documento as pd', function ($join): void {
                $join->on('pd.id', '=', 'firma_proyecto.firmable_id')
                    ->where('firma_proyecto.firmable_type', '=', DocumentoProyecto::class);
            })
            ->where(fn ($firmable) => $firmable
                ->where('firma_proyecto.firmable_type', Proyecto::class)
                ->orWhereNotNull('pd.id'))
            ->whereNull('pd.deleted_at')
            ->whereIn(DB::raw('COALESCE(pd.proyecto_id, firma_proyecto.firmable_id)'), $this->idsQuery($ambito));
    }

    protected function procesoSql(): string
    {
        return "CASE WHEN pd.id IS NULL THEN '".self::INSCRIPCION."'
                     WHEN pd.tipo_documento = 'Informe Intermedio' THEN '".self::INFORME_INTERMEDIO."'
                     ELSE '".self::CIERRE."' END";
    }

    private function tipoAccionId(): ?int
    {
        if (! array_key_exists($this->tipoAccion, self::$tiposAccion)) {
            $id = DB::table('vinculacion_tipos_accion')->where('codigo', $this->tipoAccion)->value('id');
            self::$tiposAccion[$this->tipoAccion] = $id !== null ? (int) $id : null;
        }

        return self::$tiposAccion[$this->tipoAccion];
    }
}

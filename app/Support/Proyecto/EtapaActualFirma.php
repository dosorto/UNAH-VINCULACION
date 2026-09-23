<?php

namespace App\Support\Proyecto;

use App\Models\Proyecto\Proyecto;
use App\Support\Dashboard\EstadosProyecto;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Qué firma pendiente es la etapa que un expediente está esperando AHORA, y
 * desde cuándo la espera. Versión SQL del criterio de
 * Proyecto::firmaEsActualEnFlujoPorEtapa().
 *
 * La usan las métricas del panel y, como prefiltro, la bandeja de tareas
 * (ResolvesFirmasPendientes). Es un superconjunto de ese criterio: no mira el
 * estado de un documento ni el rol del usuario, así que quien decide si se
 * puede actuar sigue siendo canActOnWorkflowStageFirma().
 *
 * Al enviar un expediente se crean las firmas de todo el recorrido, todas
 * Pendiente y con la misma fecha. Por eso ni "tiene una firma pendiente de
 * esta etapa" significa que la etapa ya le llegó, ni la fecha de creación de
 * la firma dice desde cuándo la espera.
 *
 * Antes el panel lo resolvía comparando el estado del proyecto con el estado
 * del cargo de la firma. Desde que un proyecto con flujo queda "En revision"
 * durante toda la inscripción, esa comparación ya no dice nada: un proyecto
 * FORM-DVUS-015 nunca coincidía y uno FORM-DVUS-001 coincidía por accidente con
 * las etapas cuyo cargo atiende "En revision".
 */
final class EtapaActualFirma
{
    /**
     * Estados de proyecto en los que ninguna firma de inscripción está
     * esperando, aunque quede alguna Pendiente. Mismo criterio que
     * ResolvesFirmasPendientes::estadoActualCoincideConCargoDeFirma().
     */
    private const ESTADOS_PROYECTO_SIN_REVISION = [
        'Borrador', 'Autoguardado', 'Subsanacion', 'Subsanación',
        'Registrado', 'En curso', 'Finalizado', 'Cancelado',
    ];

    /**
     * Deja solo las firmas pendientes que son la etapa actual de su expediente.
     *
     * - Firma por etapa: es del ciclo más reciente de su flujo, no hay ninguna
     *   etapa anterior del mismo ciclo pendiente o rechazada, ni firmas del
     *   ciclo que perdieron su etapa.
     * - Firma legacy (sin flujo): el estado actual del expediente es el que
     *   atiende su cargo, como hasta ahora.
     *
     * @template T of QueryBuilder|EloquentBuilder
     *
     * @param  T  $query
     * @return T
     */
    public static function filtrar(QueryBuilder|EloquentBuilder $query, string $tabla = 'firma_proyecto'): QueryBuilder|EloquentBuilder
    {
        $idsSinRevision = EstadosProyecto::ids(self::ESTADOS_PROYECTO_SIN_REVISION);

        return $query
            ->where("{$tabla}.estado_revision", 'Pendiente')
            ->whereNull("{$tabla}.deleted_at")
            ->where(function ($query) use ($tabla, $idsSinRevision): void {
                $query
                    ->where(fn ($legacy) => self::legacyActual($legacy, $tabla))
                    ->orWhere(fn ($etapa) => self::etapaActual($etapa, $tabla, $idsSinRevision));
            });
    }

    /**
     * Expresión SQL con la fecha en que la firma empezó a esperar a su revisor.
     *
     * Para una firma por etapa es la aprobación de la etapa anterior del mismo
     * ciclo, o la creación de la firma si es la primera etapa (envío o reenvío).
     * Para una firma legacy, con $legacyDesdeEstado, la fecha en que el
     * expediente entró a su estado actual; si no, su creación.
     *
     * Sirve tanto para firmas pendientes (cuánto llevan esperando) como para
     * aprobadas (cuánto tardó la etapa en resolverse).
     */
    public static function llegadaSql(string $tabla = 'firma_proyecto', bool $legacyDesdeEstado = true): string
    {
        $legacy = $legacyDesdeEstado
            ? "(SELECT MAX(ep_llegada.created_at) FROM estado_proyecto ep_llegada
                WHERE ep_llegada.estadoable_type = {$tabla}.firmable_type
                  AND ep_llegada.estadoable_id = {$tabla}.firmable_id
                  AND ep_llegada.es_actual = 1)"
            : 'NULL';

        return "GREATEST(COALESCE(CASE WHEN {$tabla}.flujo_aprobacion_etapa_id IS NULL
                THEN {$legacy}
                ELSE (SELECT MAX(fp_previa.fecha_firma) FROM firma_proyecto fp_previa
                      WHERE fp_previa.firmable_type = {$tabla}.firmable_type
                        AND fp_previa.firmable_id = {$tabla}.firmable_id
                        AND fp_previa.flujo_aprobacion_id = {$tabla}.flujo_aprobacion_id
                        AND fp_previa.revision_ciclo = {$tabla}.revision_ciclo
                        AND fp_previa.orden_revision < {$tabla}.orden_revision
                        AND fp_previa.estado_revision = 'Aprobado'
                        AND fp_previa.deleted_at IS NULL)
            END, {$tabla}.created_at), {$tabla}.created_at)";
    }

    private static function legacyActual($query, string $tabla): void
    {
        $query
            ->whereNull("{$tabla}.flujo_aprobacion_id")
            ->whereNull("{$tabla}.flujo_aprobacion_etapa_id")
            ->whereNull("{$tabla}.revision_ciclo")
            ->whereNull("{$tabla}.orden_revision")
            ->whereNull("{$tabla}.etapa_codigo")
            ->whereNull("{$tabla}.etapa_nombre")
            ->whereExists(fn ($estado) => $estado->selectRaw('1')
                ->from('estado_proyecto as ep_legacy')
                ->join('cargo_firma as cf_legacy', 'cf_legacy.tipo_estado_id', '=', 'ep_legacy.tipo_estado_id')
                ->whereColumn('cf_legacy.id', "{$tabla}.cargo_firma_id")
                ->whereColumn('ep_legacy.estadoable_type', "{$tabla}.firmable_type")
                ->whereColumn('ep_legacy.estadoable_id', "{$tabla}.firmable_id")
                ->where('ep_legacy.es_actual', true));
    }

    /** @param  list<int>  $idsSinRevision */
    private static function etapaActual($query, string $tabla, array $idsSinRevision): void
    {
        $query
            ->whereNotNull("{$tabla}.flujo_aprobacion_id")
            ->whereNotNull("{$tabla}.flujo_aprobacion_etapa_id")
            ->whereNotNull("{$tabla}.orden_revision")
            ->where("{$tabla}.revision_ciclo", '>=', 1)
            // Un reenvío abre un ciclo nuevo: lo pendiente del anterior ya no espera a nadie.
            ->whereNotExists(fn ($posterior) => self::mismoFlujo($posterior, 'fp_posterior', $tabla)
                ->whereNotNull('fp_posterior.flujo_aprobacion_etapa_id')
                ->whereColumn('fp_posterior.revision_ciclo', '>', "{$tabla}.revision_ciclo"))
            // Mientras una etapa anterior no apruebe, esta todavía no ha llegado.
            ->whereNotExists(fn ($anterior) => self::mismoFlujo($anterior, 'fp_anterior', $tabla)
                ->whereNotNull('fp_anterior.flujo_aprobacion_etapa_id')
                ->whereColumn('fp_anterior.revision_ciclo', "{$tabla}.revision_ciclo")
                ->whereColumn('fp_anterior.orden_revision', '<', "{$tabla}.orden_revision")
                ->whereIn('fp_anterior.estado_revision', ['Pendiente', 'Rechazado']))
            // Una firma del ciclo que perdió su etapa bloquea todo el ciclo.
            ->whereNotExists(fn ($huerfana) => self::mismoFlujo($huerfana, 'fp_huerfana', $tabla)
                ->whereNull('fp_huerfana.flujo_aprobacion_etapa_id')
                ->whereColumn('fp_huerfana.revision_ciclo', "{$tabla}.revision_ciclo")
                ->whereIn('fp_huerfana.estado_revision', ['Pendiente', 'Rechazado']));

        if ($idsSinRevision !== []) {
            $query->whereNotExists(fn ($estado) => $estado->selectRaw('1')
                ->from('estado_proyecto as ep_sin_revision')
                ->where('ep_sin_revision.estadoable_type', Proyecto::class)
                ->whereColumn('ep_sin_revision.estadoable_id', "{$tabla}.firmable_id")
                ->whereColumn('ep_sin_revision.estadoable_type', "{$tabla}.firmable_type")
                ->where('ep_sin_revision.es_actual', true)
                ->whereIn('ep_sin_revision.tipo_estado_id', $idsSinRevision));
        }
    }

    private static function mismoFlujo($query, string $alias, string $tabla)
    {
        return $query->selectRaw('1')
            ->from("firma_proyecto as {$alias}")
            ->whereColumn("{$alias}.firmable_type", "{$tabla}.firmable_type")
            ->whereColumn("{$alias}.firmable_id", "{$tabla}.firmable_id")
            ->whereColumn("{$alias}.flujo_aprobacion_id", "{$tabla}.flujo_aprobacion_id")
            ->whereNull("{$alias}.deleted_at");
    }
}

<?php

namespace App\Models\ENF;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnfRevision extends Model
{
    /** Estados en los que una revisión todavía espera decisión. */
    public const ESTADOS_PENDIENTES = ['PENDIENTE', 'PENDIENTE_ASIGNACION', 'ASIGNADO', 'EN_PROCESO'];

    protected $table = 'enf_revisiones';

    protected $guarded = [];

    protected $casts = [
        'revision_ciclo' => 'integer',
        'orden' => 'integer',
        'firmado_en' => 'datetime',
    ];

    public function accion(): BelongsTo
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function flujoEtapa(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Proyecto\FlujoAprobacionEtapa::class, 'flujo_aprobacion_etapa_id');
    }

    public function responsableUsuario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'responsable_usuario_id');
    }

    public function asignadoUsuario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'asignado_usuario_id');
    }

    public function decididoPorUsuario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'decidido_por_usuario_id');
    }

    /**
     * Revisiones ENF que esperan una decisión del usuario con su rol activo.
     * Única fuente de verdad para la bandeja de tareas (ProyectosPorFirmar),
     * el contador de la barra de navegación (DataNavBar) y los paneles.
     *
     * Antes había una consulta en cada uno y no coincidían: el contador exigía
     * que la acción estuviera EN_REVISION —y dejaba fuera los informes, que se
     * revisan con la acción ya aprobada—, el panel solo miraba FORM-DVUS-016 y
     * 018 y la bandeja no separaba los ciclos por proceso.
     *
     * Reproduce EnfWorkflowService::puedeRevisar(): la primera revisión
     * pendiente del ciclo más reciente de su proceso, en un ciclo que no fue
     * devuelto a subsanación, con el responsable que corresponde.
     */
    public static function pendientesParaUsuario(?User $user): Builder
    {
        $rolActivo = $user?->activeRole?->name;

        if (! $user || ! $rolActivo) {
            return self::query()->whereRaw('1 = 0');
        }

        return self::query()
            ->esperandoDecision()
            ->where(fn (Builder $rol) => $rol->whereNull('enf_revisiones.rol_requerido')
                ->orWhere('enf_revisiones.rol_requerido', $rolActivo))
            // Mismo orden de precedencia que puedeRevisar(): la asignación manda
            // sobre el responsable, y el rol solo decide si no hay ninguno.
            ->where(fn (Builder $responsable) => $responsable
                ->where('enf_revisiones.asignado_usuario_id', $user->id)
                ->orWhere(fn (Builder $q) => $q->whereNull('enf_revisiones.asignado_usuario_id')
                    ->where('enf_revisiones.responsable_usuario_id', $user->id))
                ->orWhere(fn (Builder $q) => $q->whereNull('enf_revisiones.asignado_usuario_id')
                    ->whereNull('enf_revisiones.responsable_usuario_id')
                    ->where('enf_revisiones.rol_requerido', $rolActivo)));
    }

    /**
     * Revisiones que su acción está esperando AHORA, sea quien sea el
     * responsable: la primera pendiente del ciclo más reciente de su proceso,
     * en un ciclo que no fue devuelto a subsanación.
     */
    public function scopeEsperandoDecision(Builder $query): Builder
    {
        return $query
            ->whereIn('enf_revisiones.estado', self::ESTADOS_PENDIENTES)
            ->whereHas('accion')
            // Solo la primera etapa pendiente de cada ciclo: las siguientes aún
            // no le tocan a nadie.
            ->whereNotExists(fn ($anterior) => self::mismoCiclo($anterior, 'previas')
                ->whereColumn('previas.orden', '<', 'enf_revisiones.orden')
                ->whereIn('previas.estado', self::ESTADOS_PENDIENTES))
            // Un ciclo devuelto a subsanación queda detenido hasta el reenvío.
            ->whereNotExists(fn ($devuelto) => self::mismoCiclo($devuelto, 'devueltas')
                ->where('devueltas.estado', 'SUBSANACION'))
            // Y el reenvío abre un ciclo nuevo que deja atrás al anterior.
            ->whereNotExists(fn ($cicloNuevo) => $cicloNuevo->selectRaw('1')
                ->from('enf_revisiones as posteriores')
                ->whereColumn('posteriores.enf_accion_id', 'enf_revisiones.enf_accion_id')
                ->whereColumn('posteriores.proceso', 'enf_revisiones.proceso')
                ->whereColumn('posteriores.revision_ciclo', '>', 'enf_revisiones.revision_ciclo'));
    }

    /**
     * Expresión SQL con la fecha en que la revisión empezó a esperar: la
     * decisión de la etapa anterior del mismo ciclo, o su creación si es la
     * primera. Las revisiones de todas las etapas se crean al enviar.
     */
    public static function llegadaSql(): string
    {
        return "COALESCE((SELECT MAX(previa.firmado_en) FROM enf_revisiones previa
                  WHERE previa.enf_accion_id = enf_revisiones.enf_accion_id
                    AND previa.proceso = enf_revisiones.proceso
                    AND previa.revision_ciclo = enf_revisiones.revision_ciclo
                    AND previa.orden < enf_revisiones.orden
                    AND previa.estado = 'APROBADO'), enf_revisiones.created_at)";
    }

    private static function mismoCiclo($query, string $alias)
    {
        return $query->selectRaw('1')
            ->from("enf_revisiones as {$alias}")
            ->whereColumn("{$alias}.enf_accion_id", 'enf_revisiones.enf_accion_id')
            ->whereColumn("{$alias}.proceso", 'enf_revisiones.proceso')
            ->whereColumn("{$alias}.revision_ciclo", 'enf_revisiones.revision_ciclo');
    }
}

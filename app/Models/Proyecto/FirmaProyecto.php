<?php

namespace App\Models\Proyecto;

use App\Models\Estado\TipoEstado;
use App\Models\Personal\Empleado;
use App\Models\Personal\FirmaSelloEmpleado;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirmaProyecto extends Model
{
    use HasFactory;

    protected $table = 'firma_proyecto';

    protected $fillable = [
        'proyecto_id',
        'empleado_id',
        'cargo_firma_id',
        'firma_id',
        'sello_id',
        'estado_revision',
        'hash',
        'firmable_type',
        'firmable_id',
        'flujo_aprobacion_id',
        'flujo_aprobacion_etapa_id',
        'orden_revision',
        'etapa_codigo',
        'etapa_nombre',
        'rol_requerido',
        'responsable_usuario_id',
        'revision_ciclo',
        'estado_actual_id',
        'tipo_firma', // proyecto, contrato, acta, etc
        'fecha_firma',
    ];

    protected $casts = [
        'flujo_aprobacion_id' => 'integer',
        'flujo_aprobacion_etapa_id' => 'integer',
        'orden_revision' => 'integer',
        'responsable_usuario_id' => 'integer',
        'revision_ciclo' => 'integer',
    ];

    public function firmable()
    {
        return $this->morphTo();
    }

    // relacion con estado
    public function estado_actual()
    {
        return $this->belongsTo(TipoEstado::class, 'estado_actual_id');
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'firmable_id');
    }

    public function documento_proyecto()
    {
        return $this->belongsTo(DocumentoProyecto::class, 'firmable_id');
    }

    public function ficha_actualizacion()
    {
        return $this->belongsTo(FichaActualizacion::class, 'firmable_id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function cargo_firma()
    {
        return $this->belongsTo(CargoFirma::class, 'cargo_firma_id');
    }

    public function flujoAprobacion(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacion::class, 'flujo_aprobacion_id');
    }

    public function flujoEtapa(): BelongsTo
    {
        return $this->belongsTo(FlujoAprobacionEtapa::class, 'flujo_aprobacion_etapa_id');
    }

    public function responsableUsuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_usuario_id');
    }

    public function usaFlujoPorEtapa(): bool
    {
        return filled($this->flujo_aprobacion_etapa_id);
    }

    public function esFirmaLegacy(): bool
    {
        return blank($this->flujo_aprobacion_etapa_id);
    }

    /**
     * Reasigna esta firma pendiente a otro usuario con el mismo rol ya
     * definido en la etapa (rol_requerido). Solo el responsable actual de
     * la firma puede delegarla. Sirve tanto para Proyecto como para PPS,
     * ya que ambos usan esta misma tabla para sus etapas.
     */
    public function reasignarA(User $nuevoUsuario, User $actor): void
    {
        if (! $this->usaFlujoPorEtapa() || $this->estado_revision !== 'Pendiente') {
            throw new \RuntimeException('Solo se pueden reasignar etapas pendientes de un flujo configurable.');
        }

        if (! $this->responsable_usuario_id || (int) $this->responsable_usuario_id !== (int) $actor->id) {
            throw new \RuntimeException('Solo el responsable actual de esta etapa puede reasignarla.');
        }

        if (blank($this->rol_requerido)) {
            throw new \RuntimeException('Esta etapa no tiene un rol de revisor configurado para reasignar.');
        }

        $tieneRol = $nuevoUsuario->roles()->where('roles.name', $this->rol_requerido)->exists();

        if (! $tieneRol) {
            throw new \RuntimeException("El usuario seleccionado no tiene el rol \"{$this->rol_requerido}\" requerido para esta etapa.");
        }

        $nuevoEmpleado = $nuevoUsuario->empleado;

        if (! $nuevoEmpleado) {
            throw new \RuntimeException('El usuario seleccionado no tiene un empleado activo asociado.');
        }

        $this->update([
            'empleado_id' => $nuevoEmpleado->id,
            'responsable_usuario_id' => $nuevoUsuario->id,
        ]);
    }

    // recuperar la firma del empleado
    public function firma()
    {
        return $this->belongsTo(FirmaSelloEmpleado::class, 'firma_id');
    }

    // recuperar el sello del empleado
    public function sello()
    {
        return $this->belongsTo(FirmaSelloEmpleado::class, 'sello_id');
    }
}

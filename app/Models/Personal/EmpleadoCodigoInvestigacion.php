<?php

namespace App\Models\Personal;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmpleadoCodigoInvestigacion extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'empleado_codigos_investigacion';

    protected $fillable = [
        'empleado_id',
        'codigo_proyecto',
        'nombre_proyecto',
        'rol_docente',
        'descripcion',
        'año',
        'estado_verificacion',
        'observaciones_admin',
        'verificado_por',
        'fecha_verificacion'
    ];

    protected $casts = [
        'fecha_verificacion' => 'datetime',
        'año' => 'integer',
    ];

    protected static $logAttributes = [
        'codigo_proyecto',
        'nombre_proyecto',
        'rol_docente',
        'estado_verificacion',
        'año'
    ];

    protected static $logName = 'EmpleadoCodigoInvestigacion';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['codigo_proyecto', 'nombre_proyecto', 'rol_docente', 'estado_verificacion', 'año'])
            ->setDescriptionForEvent(fn(string $eventName) => "El código de investigación {$this->codigo_proyecto} ha sido {$eventName}");
    }

    // Relaciones
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function verificadoPor()
    {
        return $this->belongsTo(Empleado::class, 'verificado_por');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado_verificacion', 'pendiente');
    }

    public function scopeVerificados($query)
    {
        return $query->where('estado_verificacion', 'verificado');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado_verificacion', 'rechazado');
    }

    // Métodos de utilidad
    public function getEstadoBadgeColor()
    {
        return match ($this->estado_verificacion) {
            'pendiente' => 'warning',
            'verificado' => 'success',
            'rechazado' => 'danger',
            default => 'gray',
        };
    }

    public function getEstadoLabel()
    {
        return match ($this->estado_verificacion) {
            'pendiente' => 'Pendiente',
            'verificado' => 'Verificado',
            'rechazado' => 'Rechazado',
            default => 'Desconocido',
        };
    }
}

<?php

namespace App\Models\Proyecto;

use App\Models\Estado\EstadoProyecto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DocumentoProyecto extends Model
{
    use HasFactory, LogsActivity;

    // tabla documento_proyecto
    protected $table = 'proyecto_documento';

    protected $fillable = [
        'proyecto_id',
        'tipo_documento',
        'documento_url',
    ];

    /**
     * Configuración del registro de actividad
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['proyecto_id', 'tipo_documento', 'documento_url'])
            ->logOnlyDirty() // Solo registra atributos modificados
            ->dontSubmitEmptyLogs() // No crear registros si no hay cambios
            ->setDescriptionForEvent(function(string $eventName) {
                $tipo = $this->tipo_documento ?? 'documento';
                return match($eventName) {
                    'created' => "Se agregó un nuevo {$tipo} al proyecto",
                    'updated' => "Se actualizó el {$tipo} del proyecto",
                    'deleted' => "Se eliminó el {$tipo} del proyecto",
                    default => "Se realizó una acción en el {$tipo} del proyecto",
                };
            });
    }

    /**
     * Personaliza la clave para identificar este modelo en los logs
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        $tipo = $this->tipo_documento ?? 'documento';
        return "Se ha {$eventName} el {$tipo} del proyecto";
    }

    // relacion uno a muchos con el modelo FirmaProyecto
    public function firma_documento()
    {
        return $this->morphMany(FirmaProyecto::class, 'firmable');
    }

    // relacion uno a muchos con el modelo do_proyecto
    public function estado_documento()
    {
        return $this->morphMany(EstadoProyecto::class, 'estadoable');
    }

    // relacion uno a uno inversa con el modelo Proyecto
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id', 'id');
    }

    public function getEstadoAttribute()
    {
        return $this->estado_documento()
            ->where('es_actual', true)
            ->first();
    }
}
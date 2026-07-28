<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EntidadContraparte extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'entidad_contraparte';

    protected $fillable = [
        'rtn',
        'nombre',
        'tipo_entidad',
        'nombre_contacto',
        'cargo_contacto',
        'correo',
        'telefono',
    ];

    protected static $logAttributes = ['id', 'rtn', 'nombre', 'telefono', 'correo', 'nombre_contacto', 'tipo_entidad'];
    protected static $logName = 'EntidadContraparte';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id', 'rtn', 'nombre', 'telefono', 'correo', 'nombre_contacto', 'tipo_entidad'])
            ->setDescriptionForEvent(fn (string $eventName) => "La contraparte {$this->nombre} ha sido {$eventName}");
    }

    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(
            Proyecto::class,
            'entidad_contraparte_proyecto',
            'entidad_contraparte_id',
            'proyecto_id'
        )->using(EntidadContraparteProyecto::class)
         ->withPivot(['rtn', 'descripcion_acuerdos'])
         ->withTimestamps();
    }

    public function getNombreConRtnAttribute(): string
    {
        return $this->rtn
            ? "{$this->nombre} (RTN: {$this->rtn})"
            : $this->nombre;
    }
}

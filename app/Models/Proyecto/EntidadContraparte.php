<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EntidadContraparte extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'proyecto_id', 'nombre', 'telefono', 'correo', 'nombre_contacto', 'es_internacional',
            'aporte'];

    protected static $logName = 'EntidadContraparte';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'proyecto_id', 'nombre', 'telefono', 'correo', 'nombre_contacto', 'es_internacional',
            'aporte'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'proyecto_id',
        'nombre',
        'telefono',
        'correo', 
        'nombre_contacto', 
        'es_internacional',
        'aporte', 
        // 'instrumento_formalizacion'
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id',);
    }

    // Relacion de uno a muchos con el modelo InstrumentoFormalizacion
    public function instrumento_formalizacion()
    {
        return $this->hasMany(InstrumenFormalizacion::class);
    }

    protected $table = 'entidad_contraparte';
}

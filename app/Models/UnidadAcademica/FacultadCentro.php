<?php

namespace App\Models\UnidadAcademica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FacultadCentro extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'nombre','es_facultad','siglas','campus_id'];

    protected static $logName = 'FacultadCentro';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'nombre'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'nombre',
    ];

    // relacion muchos a muchos con la carreras

    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'facultad_centro_carrera', 'facultad_centro_id', 'carrera_id');
    }

    

    protected $table = 'centro_facultad';

}

<?php

namespace App\Models\Unidad_Academica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CarreraFacultadCentro extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'carrera_id', 'facultad_centro_id'];

    protected static $logName = 'CarreraFacultadCentro ';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'carrera_id', 'facultad_centro_id'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'carrera_id',
        'facultad_centro_id',
    ];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function facultadcentro()
    {
        return $this->belongsTo(FacultadCentro::class, 'facultad_centro_id',);
    }

    protected $table = 'carrera_facultad_centro';
}

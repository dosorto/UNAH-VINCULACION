<?php

namespace App\Models\Unidad_Academica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class Carrera extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'nombre', 'facultad_centro_id'];

    protected static $logName = 'Carrera';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'nombre', 'facultad_centro_id'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'nombre',
        'facultad_centro_id',
    ];

    public function facultadcentro()
    {
        return $this->belongsTo(FacultadCentro::class, 'facultad_centro_id',);
    }


    protected $table = 'carrera';

}
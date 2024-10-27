<?php

namespace App\Models\UnidadAcademica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class EntidadAcademica extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'tipo_entidad_academica_id', 'entidad_academica'];

    protected static $logName = 'EntidadAcademica';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'tipo_entidad_academica_id', 'entidad_academica'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->entidad_academica} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'tipo_entidad_academica_id',
        'entidad_academica',
    ];

    public function tipoentidadacademica()
    {
        return $this->belongsTo(TipoEntidadAcademica::class, 'tipo_entidad_academica_id');
    }


    protected $table = 'entidad_academica';
}

<?php

namespace App\Models\Unidad_Academica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class Entidad_academica extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

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

    public function tipo_entidad_academica()
    {
        return $this->belongsTo(Tipo_entidad_academica::class, 'tipo_entidad_academica_id');
    }


    protected $table = 'entidad_academica';
}

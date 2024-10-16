<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Empleado_proyecto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'empleado_id', 'proyecto_id'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->empleado_id} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'empleado_id',
        'proyecto_id',
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id',);
    }

    public function proyecto()
    {
        return $this->belongsTo(proyecto::class, 'proyecto_id',);
    }


    protected $table = 'Empleado_proyecto';
}

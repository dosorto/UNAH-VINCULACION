<?php

namespace App\Models\Estudiante;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Estudiant_proyecto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'estudiante_id', 'proyecto_id', 'tipo_participacion'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->estudiante_id} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'estudiante_id', 
        'proyecto_id', 
        'tipo_participacion',
    ];

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id',);
    }

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id',);
    }

    protected $table = 'estudiante_proyecto';
}
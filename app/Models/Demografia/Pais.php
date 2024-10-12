<?php

namespace App\Models\Demografia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pais extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['codigo_area', 'codigo_iso', 'codigo_iso_numerico', 'codigo_iso_alpha_2', 'nombre', 'gentilicio'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'codigo_area',
        'codigo_iso',
        'codigo_iso_numerico',
        'codigo_iso_alpha_2',
        'nombre',
        'gentilicio',
    ];

    
    protected $table = 'pais';
}

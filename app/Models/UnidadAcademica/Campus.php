<?php

namespace App\Models\UnidadAcademica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Campus extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['nombre_campus', 'siglas', 'direccion', 'telefono', 'url'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre_campus} ha sido {$eventName}");
    }

    protected $table = 'campus';

    protected $fillable = [
        'nombre_campus',
        'siglas',
        'direccion',
        'telefono',
        'url'
    ];

}

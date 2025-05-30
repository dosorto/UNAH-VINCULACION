<?php

namespace App\Models\Demografia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;



class Ciudad extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['municipio_id', 'nombre', 'codigo_postal'];

    protected static $logName = 'Ciudad';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['municipio_id', 'nombre', 'codigo_postal'])
            ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }

    protected $fillable = [
        'municipio_id',
        'nombre',
        'codigo_postal'
    ];

    protected $table = 'ciudad';

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }
}

<?php

namespace App\Models\Demografia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;



class Municipio extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['departamento_id', 'nombre', 'codigo_municipio'];

    protected static $logName = 'Municipio';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['departamento_id', 'nombre', 'codigo_municipio'])
            ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }

    protected $fillable = [
        'departamento_id',
        'nombre',
        'codigo_municipio'
    ];

    protected $table = 'municipio';

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }
}

<?php

namespace App\Models\Demografia;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Departamento extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['pais_id', 'nombre', 'codigo_departamento'];

    protected static $logName = 'Departamento';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['pais_id', 'nombre', 'codigo_departamento'])
            ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }


    protected $fillable = [
        'pais_id',
        'nombre',
        'codigo_departamento'
    ];

    protected $table = 'departamento';


    //

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    /** Los formularios que no preguntan el país trabajan solo con los departamentos de Honduras. */
    public function scopeDeHonduras(Builder $query): Builder
    {
        return $query->whereHas('pais', fn (Builder $pais) => $pais->where('codigo_iso', 'HND'));
    }

}

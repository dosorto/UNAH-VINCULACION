<?php

namespace App\Models\UnidadAcademica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\UnidadAcademica\FacultadCentro as CentroFacultad;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class DepartamentoAcademico extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nombre', 'centro_facultad_id'])
            ->setDescriptionForEvent(fn(string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }


    protected $table = 'departamento_academico';

    protected $fillable = [
        'nombre',
        'centro_facultad_id',
    ];

    public function centroFacultad()
    {
        return $this->belongsTo(CentroFacultad::class, 'centro_facultad_id');
    }
}

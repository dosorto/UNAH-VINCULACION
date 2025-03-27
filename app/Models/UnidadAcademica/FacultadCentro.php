<?php

namespace App\Models\UnidadAcademica;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use App\Models\UnidadAcademica\AdministradorFacultadCentro;

class FacultadCentro extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['id', 'nombre', 'es_facultad', 'siglas', 'campus_id'];

    protected static $logName = 'FacultadCentro';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['id', 'nombre'])
            ->setDescriptionForEvent(fn(string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }

    protected $fillable = [
        'id',
        'nombre',
        'es_facultad',
        'siglas',
        'campus_id'
    ];

    // relacion muchos a muchos con la carreras

    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'facultad_centro_carrera', 'facultad_centro_id', 'carrera_id');
    }


    // relaciion uno a muchos con los administradores
    public function administradores()
    {
        return $this->hasMany(AdministradorFacultadCentro::class, 'centro_facultad_id');
    }

    // obtener solamente los administradores activos
    public function administradoresActivos()
    {
        return $this->hasMany(AdministradorFacultadCentro::class, 'centro_facultad_id')
            ->where('estado', 1);
    }

    // obtener solamente el administrador activo
    public function administradorActivo()
    {
        return $this->hasOne(AdministradorFacultadCentro::class, 'centro_facultad_id')
            ->where('estado', 1);
    }

    // relacion uno a muchos con campus
    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    /*
        relacion uno a muchos con departamentos academicos

    */

    public function departamentosAcademicos()
    {
        return $this->hasMany(DepartamentoAcademico::class, 'centro_facultad_id');
    }

    protected $table = 'centro_facultad';
}

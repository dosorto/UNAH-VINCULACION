<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Proyecto extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logOnly(['id', 'nombre', 'carrera_facultad_centro_id', 'entidad_academica_id', 'coordinador_id', 'ods_id',
            'modalidad_id', 'categoria_id', 'municipio_id', 'departamento_id', 'ciudad_id', 'aldea_id', 'resumen',
            'objetivo_general', 'objetivos_especificos', 'fecha_inicio', 'fecha_finalizacion', 'evaluacion_intermedia',
            'evaluacion_final', 'poblacion_participante', 'modalidad_ejecucion', 'resultados_esperados', 
            'indicadores_medicion_resultados'])
        ->setDescriptionForEvent(fn (string $eventName) => "El registro {$this->nombre} ha sido {$eventName}");
    }
    
    protected $fillable = [
        'id',
        'nombre',
        'carrera_facultad_centro_id', 
        'entidad_academica_id', 
        'coordinador_id',
        'ods_id',
        'modalidad_id', 
        'categoria_id', 
        'municipio_id',
        'departamento_id', 
        'ciudad_id', 
        'aldea_id',
        'resumen',
        'objetivo_general', 
        'objetivos_especificos', 
        'fecha_inicio', 
        'fecha_finalizacion', 
        'evaluacion_intermedia',
        'evaluacion_final', 
        'poblacion_participante', 
        'modalidad_ejecucion', 
        'resultados_esperados', 
        'indicadores_medicion_resultados',
    ];

    public function carrera_facultad_centro()
    {
        return $this->belongsTo(Carrera_facultad_centro::class, 'carrera_facultad_centro_id',);
    }


    public function entidad_academica()
    {
        return $this->belongsTo(Entidad_academica::class, 'entidad_academica_id',);
    }


    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'coordinador_id',);
    }

    public function ods()
    {
        return $this->belongsTo(Ods::class, 'ods_id',);
    }

    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id',);
    }

    public function categoria()
    {
        return $this->belongsTo(Categorias::class, 'categoria_id',);
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id',);
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id',);
    }

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_id',);
    }

    public function aldea()
    {
        return $this->belongsTo(Aldea::class, 'aldea_id',);
    }

    protected $table = 'proyecto';


}
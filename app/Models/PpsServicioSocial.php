<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PpsServicioSocial extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'pps_servicio_social';

    protected $fillable = [
        'codigo_registro',
        'estado',
        'facultad_centro',
        'carrera',
        'numero_cuenta',
        'nombre_estudiante',
        'celular_estudiante',
        'correo_institucional',
        'correo_personal',
        'tipo_pps_ss',
        'fecha_inicio',
        'fecha_finalizacion',
        'tipo_instrumento',
        'territorio_ejecucion',
        'departamento',
        'municipio',
        'aldea_ciudad',
        'caserio',
        'descripcion_tipo_pps',
        'total_horas',
        'area_realizacion',
        'resumen_responsabilidades',
        'modalidad_ejecucion',
        'nombre_institucion',
        'compromisos_institucion',
        'direccion_institucion',
        'representante_legal',
        'telefono_representante',
        'correo_rrhh',
        'tipo_institucion',
        'sector_institucion',
        'nombre_jefe_directo',
        'celular_jefe_directo',
        'correo_jefe_directo',
        'cargo_jefe_directo',
        'grado_academico_jefe_directo',
        'nombre_docente_supervisor',
        'numero_empleado_docente',
        'celular_docente',
        'correo_docente',
        'categoria_docente',
        'departamento_docente',
        'jornada_laboral_docente',
        'ubicacion_cubiculo_docente',
        'adjunta_carta_formalizacion',
        'archivo_carta_formalizacion',
        'adjunta_convenio_marco',
        'archivo_convenio_marco',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_finalizacion' => 'date',
        'total_horas' => 'integer',
        'adjunta_carta_formalizacion' => 'boolean',
        'adjunta_convenio_marco' => 'boolean',
    ];
}

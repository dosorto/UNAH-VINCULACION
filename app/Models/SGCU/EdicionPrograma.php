<?php

namespace App\Models\SGCU;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdicionPrograma extends Model
{
    protected $table = 'ediciones_programa';

    protected $fillable = [
        'programa_certificacion_id',
        'centro_facultad_id',
        'periodo_academico_id',
        'codigo_edicion',
        'numero_edicion',
        'cupo_maximo',
        'inicio',
        'fin',
        'estado',
        'creado_por_usuario_id',
    ];

    protected $casts = [
        'inicio' => 'date',
        'fin' => 'date',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaCertificacion::class, 'programa_certificacion_id');
    }

    public function centroFacultad(): BelongsTo
    {
        return $this->belongsTo(\App\Models\UnidadAcademica\FacultadCentro::class, 'centro_facultad_id');
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PeriodoAcademico::class, 'periodo_academico_id');
    }
}

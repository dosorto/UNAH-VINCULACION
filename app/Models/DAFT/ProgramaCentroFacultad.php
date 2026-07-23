<?php

namespace App\Models\DAFT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramaCentroFacultad extends Model
{
    protected $table = 'programas_centros_facultad';

    protected $fillable = [
        'programa_certificacion_id',
        'centro_facultad_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function programa(): BelongsTo
    {
        return $this->belongsTo(ProgramaCertificacion::class, 'programa_certificacion_id');
    }

    public function centroFacultad(): BelongsTo
    {
        return $this->belongsTo(\App\Models\UnidadAcademica\FacultadCentro::class, 'centro_facultad_id');
    }
}

<?php

namespace App\Models\DAFT;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TipoPrograma extends Model
{
    protected $table = 'tipos_programa';

    protected $fillable = [
        'nombre',
        'horas_minimas',
        'horas_maximas',
        'plantilla_docx_path',
        'activo',
    ];

    protected $casts = [
        'horas_minimas' => 'integer',
        'horas_maximas' => 'integer',
        'activo' => 'boolean',
    ];

    public function programas(): HasMany
    {
        return $this->hasMany(ProgramaCertificacion::class, 'tipo_programa_id');
    }

    public function flujoAprobacion(): HasOne
    {
        return $this->hasOne(\App\Models\Proyecto\FlujoAprobacion::class, 'tipo_programa_id');
    }
}

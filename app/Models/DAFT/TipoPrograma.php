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
        'modalidad_duracion',
        'horas_minimas',
        'horas_maximas',
        'dias_minimos',
        'dias_maximos',
        'horas_minimas_por_dia',
        'dias_consecutivos',
        'plantilla_docx_path',
        'activo',
    ];

    protected $casts = [
        'horas_minimas' => 'integer',
        'horas_maximas' => 'integer',
        'dias_minimos' => 'integer',
        'dias_maximos' => 'integer',
        'horas_minimas_por_dia' => 'integer',
        'dias_consecutivos' => 'boolean',
        'activo' => 'boolean',
    ];

    public function usaDuracionPorDias(): bool
    {
        return $this->modalidad_duracion === 'DIAS';
    }

    public function descripcionDuracion(): string
    {
        if ($this->usaDuracionPorDias()) {
            $descripcion = $this->dias_minimos.'–'.$this->dias_maximos.' días · mín. '.$this->horas_minimas_por_dia.' h/día';

            return $this->dias_consecutivos ? $descripcion.' · consecutivos' : $descripcion;
        }

        return ($this->horas_minimas ?? 0).'–'.($this->horas_maximas ?? 'N/D').' horas';
    }

    public function programas(): HasMany
    {
        return $this->hasMany(ProgramaCertificacion::class, 'tipo_programa_id');
    }

    public function flujoAprobacion(): HasOne
    {
        return $this->hasOne(\App\Models\Proyecto\FlujoAprobacion::class, 'tipo_programa_id');
    }
}

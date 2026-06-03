<?php

namespace App\Models\ENF;

use App\Models\Demografia\Departamento;
use App\Models\Demografia\Municipio;
use App\Models\UnidadAcademica\Campus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfLugarEjecucion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_lugares_ejecucion';

    protected $guarded = [];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }
}

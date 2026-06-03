<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfCertificado extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_certificados';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
        'horas_certificadas' => 'integer',
    ];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function tipoCertificado()
    {
        return $this->belongsTo(EnfCatalogo::class, 'tipo_certificado_id');
    }

    public function figuraAcreditacion()
    {
        return $this->belongsTo(EnfCatalogo::class, 'figura_acreditacion_id');
    }

    public function carreras()
    {
        return $this->hasMany(EnfCertificadoCarrera::class, 'enf_certificado_id');
    }
}

<?php

namespace App\Models\ENF;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfInformeIntermedio extends Model
{
    use SoftDeletes;

    public const ESTADO_BORRADOR = 'BORRADOR';
    public const ESTADO_EN_REVISION = 'EN_REVISION';
    public const ESTADO_SUBSANACION = 'SUBSANACION';
    public const ESTADO_APROBADO = 'APROBADO';

    protected $table = 'enf_informes_intermedios';

    protected $guarded = [];

    protected $casts = [
        'tamano_bytes' => 'integer',
        'revision_ciclo' => 'integer',
        'fecha_carga' => 'datetime',
        'fecha_envio' => 'datetime',
        'fecha_aprobacion' => 'datetime',
    ];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function usuarioCarga()
    {
        return $this->belongsTo(User::class, 'subido_por_usuario_id');
    }

    public function usuarioEnvio()
    {
        return $this->belongsTo(User::class, 'enviado_por_usuario_id');
    }

    public function esEditable(): bool
    {
        return in_array($this->estado, [self::ESTADO_BORRADOR, self::ESTADO_SUBSANACION], true);
    }
}

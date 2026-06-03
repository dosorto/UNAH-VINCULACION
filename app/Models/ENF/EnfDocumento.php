<?php

namespace App\Models\ENF;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfDocumento extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_documentos';

    protected $guarded = [];

    protected $casts = [
        'tamano_bytes' => 'integer',
    ];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function subidoPor()
    {
        return $this->belongsTo(User::class, 'subido_por_usuario_id');
    }

    public function firmas()
    {
        return $this->hasMany(EnfFirma::class, 'enf_documento_id');
    }
}

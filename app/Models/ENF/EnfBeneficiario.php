<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfBeneficiario extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_beneficiarios';

    protected $guarded = [];

    protected $casts = [
        'distribucion' => 'array',
    ];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }
}

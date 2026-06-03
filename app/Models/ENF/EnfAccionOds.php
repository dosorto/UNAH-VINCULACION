<?php

namespace App\Models\ENF;

use App\Models\Proyecto\MetaContribuye;
use App\Models\Proyecto\Od;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfAccionOds extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_accion_ods';

    protected $guarded = [];

    public function accion()
    {
        return $this->belongsTo(EnfAccion::class, 'enf_accion_id');
    }

    public function ods()
    {
        return $this->belongsTo(Od::class, 'ods_id');
    }

    public function metaContribuye()
    {
        return $this->belongsTo(MetaContribuye::class, 'meta_contribuye_id');
    }
}

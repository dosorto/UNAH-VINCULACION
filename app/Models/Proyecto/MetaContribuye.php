<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaContribuye extends Model
{
    use HasFactory;

    protected $table = 'metas_contribuye';

    protected $fillable = [
        'ods_id',
        'numero_meta',
        'descripcion',
    ];

    public function ods()
    {
        return $this->belongsTo(Od::class, 'ods_id');
    }
}

<?php

namespace App\Models\ENF;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfAccionNoEjecutada extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_acciones_no_ejecutadas';

    protected $guarded = [];

    protected $casts = [
        'fecha_reprogramacion' => 'date',
    ];

    public function informeFinal()
    {
        return $this->belongsTo(EnfInformeFinal::class, 'enf_informe_final_id');
    }
}

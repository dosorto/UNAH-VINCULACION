<?php

namespace App\Models\ENF;

use App\Models\Estudiante\Estudiante;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnfParticipanteFinal extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'enf_participantes_finales';

    protected $guarded = [];

    protected $casts = [
        'certificado_emitido' => 'boolean',
        'edad' => 'integer',
    ];

    public function informeFinal()
    {
        return $this->belongsTo(EnfInformeFinal::class, 'enf_informe_final_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

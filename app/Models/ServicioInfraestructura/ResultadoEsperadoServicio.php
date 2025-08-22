<?php

namespace App\Models\ServicioInfraestructura;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResultadoEsperadoServicio extends Model
{
    use SoftDeletes;

    protected $table = 'resultado_esperado_S';

    protected $fillable = [
        'objetivo_especifico_id',
        'nombre_resultado',
        'nombre_indicador',
        'orden'
    ];

    protected $casts = [
        'orden' => 'integer'
    ];

    public function objetivoEspecifico(): BelongsTo
    {
        return $this->belongsTo(ObjetivoEspecificoServicio::class);
    }
}

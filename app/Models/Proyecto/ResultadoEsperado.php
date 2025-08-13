<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResultadoEsperado extends Model
{
    use SoftDeletes;

    protected $table = 'resultado_esperado';

    protected $fillable = [
        'objetivo_especifico_id',
        'nombre_resultado',
        'nombre_indicador',
        'nombre_medio_verificacion',
        'plazo',
        'orden'
    ];

    protected $casts = [
        'orden' => 'integer'
    ];

    public function objetivoEspecifico(): BelongsTo
    {
        return $this->belongsTo(ObjetivoEspecifico::class);
    }
}

<?php

namespace App\Models\ServicioInfraestructura;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObjetivoEspecificoServicio extends Model
{
    use SoftDeletes;

    protected $table = 'objetivo_especifico_S';

    protected $fillable = [
        'servicio_tecnologico_id',
        'descripcion',
        'orden'
    ];

    protected $casts = [
        'orden' => 'integer'
    ];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(ServicioTecnologico::class);
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoEsperadoServicio::class)->orderBy('orden');
    }
}

<?php

namespace App\Models\Proyecto;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObjetivoEspecifico extends Model
{
    use SoftDeletes;

    protected $table = 'objetivo_especifico';

    protected $fillable = [
        'proyecto_id',
        'descripcion',
        'orden'
    ];

    protected $casts = [
        'orden' => 'integer'
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoEsperado::class)->orderBy('orden');
    }
}

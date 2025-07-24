<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntidadContraparte extends Model
{
    protected $table = 'entidad_contraparte';

    protected $fillable = [
        'proyecto_id',
        'tipo',
        'nombre',
        'ubicacion',
        'contacto'
    ];

    protected $casts = [
        'tipo' => 'string'
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function instrumentosFormalizacion(): HasMany
    {
        return $this->hasMany(InstrumentoFormalizacion::class);
    }
}
